<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\AiSalesAgent;
use App\Exceptions\InsufficientAiCreditsException;
use App\Services\AiWhatsAppService;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ConversationEngineCommand extends Command
{
    protected $signature = 'ai-agent:process-conversations {--limit=100} {--agent=} {--timeout=30}';
    protected $description = 'Process queued conversations and handle fallback scenarios';

    private $aiWhatsAppService;
    private $openAiService;

    /** A conversation that failed this many times is never retried automatically. */
    private const MAX_RETRIES = 3;

    /** Conversations skipped in this run because the owner has no AI credits, keyed by lead id. */
    private array $skippedForCredits = [];

    public function __construct(AiWhatsAppService $aiWhatsAppService, OpenAiService $openAiService)
    {
        parent::__construct();
        $this->aiWhatsAppService = $aiWhatsAppService;
        $this->openAiService = $openAiService;
    }

    public function handle()
    {
        $this->info('🔄 Starting Conversation Engine Processing');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $agentId = $this->option('agent');
        $timeout = (int) $this->option('timeout');

        try {
            // Process pending conversations
            $pending = $this->processPendingConversations($agentId, $limit);
            
            // Handle stuck conversations
            $stuck = $this->handleStuckConversations($timeout);
            
            // Process high priority conversations
            $priority = $this->processHighPriorityConversations($agentId);
            
            // Handle failed conversations
            $failed = $this->retryFailedConversations();

            $this->newLine();
            $this->info('📊 Processing Summary:');
            $this->line("  • Pending conversations processed: {$pending}");
            $this->line("  • Stuck conversations recovered: {$stuck}");
            $this->line("  • Priority conversations handled: {$priority}");
            $this->line("  • Failed conversations retried: {$failed}");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("💥 Fatal error in conversation engine: " . $e->getMessage());
            Log::error('Conversation engine fatal error', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    private function processPendingConversations($agentId, int $limit): int
    {
        $this->info('📋 Processing Pending Conversations...');

        $query = Conversation::where('status', Conversation::STATUS_PENDING)
            ->whereNull('processing_started_at')
            ->orderBy('priority', 'desc')
            ->orderBy('created_at');

        if ($agentId) {
            $query->whereHas('lead', function($q) use ($agentId) {
                $q->where('ai_sales_agent_id', $agentId);
            });
        }

        $conversations = $query->limit($limit)->get();
        $processed = 0;

        foreach ($conversations as $conversation) {
            try {
                $this->processConversation($conversation);
                $processed++;
                $this->line("  ✅ Processed conversation #{$conversation->id}");
                
            } catch (InsufficientAiCreditsException $e) {
                // Permanent until the owner tops up: fail it once, never retry it, log one summary line.
                $this->markConversationFailed($conversation, 'insufficient_ai_credits', false);

            } catch (\Exception $e) {
                $this->error("  ❌ Failed to process conversation #{$conversation->id}: " . $e->getMessage());
                $this->markConversationFailed($conversation, $e->getMessage());
            }
        }

        $this->reportCreditSkips();

        return $processed;
    }

    /**
     * One log line per run instead of one ERROR per conversation (this used to write ~400,000 lines).
     */
    private function reportCreditSkips(): void
    {
        if (empty($this->skippedForCredits)) {
            return;
        }

        $count = array_sum($this->skippedForCredits);
        $this->warn("  ⚠️  Skipped {$count} conversation(s): owner has no AI credits");
        Log::warning('Conversations skipped: insufficient AI credits', [
            'conversations' => $count,
            'leads' => count($this->skippedForCredits),
        ]);

        $this->skippedForCredits = [];
    }

    private function handleStuckConversations(int $timeoutMinutes): int
    {
        $this->info('🔧 Recovering Stuck Conversations...');

        $stuckConversations = Conversation::where('status', Conversation::STATUS_PROCESSING)
            ->where('processing_started_at', '<', now()->subMinutes($timeoutMinutes))
            ->get();

        $recovered = 0;

        foreach ($stuckConversations as $conversation) {
            try {
                $this->line("  🔄 Recovering stuck conversation #{$conversation->id}");
                
                // Reset conversation status
                // There is no `last_error` column (it was silently discarded); keep the note in ai_metadata.
                $conversation->update([
                    'status' => Conversation::STATUS_PENDING,
                    'processing_started_at' => null,
                    'retry_count' => ($conversation->retry_count ?? 0) + 1,
                    'ai_metadata' => $this->withLastError(
                        $conversation,
                        'Recovered from stuck state after ' . $timeoutMinutes . ' minutes'
                    ),
                ]);

                $recovered++;
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to recover conversation #{$conversation->id}: " . $e->getMessage());
            }
        }

        return $recovered;
    }

    private function processHighPriorityConversations($agentId): int
    {
        $this->info('🔥 Processing High Priority Conversations...');

        $query = Conversation::where('priority', '>', 7)
            ->whereIn('status', [Conversation::STATUS_PENDING, Conversation::STATUS_ACTIVE])
            ->orderByDesc('priority')
            ->orderBy('updated_at');

        if ($agentId) {
            $query->whereHas('lead', function($q) use ($agentId) {
                $q->where('ai_sales_agent_id', $agentId);
            });
        }

        $conversations = $query->limit(20)->get();
        $processed = 0;

        foreach ($conversations as $conversation) {
            try {
                $this->processConversation($conversation, true);
                $processed++;
                $this->line("  🔥 Priority conversation #{$conversation->id} processed");
                
            } catch (\Exception $e) {
                $this->error("  ❌ Priority conversation #{$conversation->id} failed: " . $e->getMessage());
                $this->escalateConversation($conversation, $e->getMessage());
            }
        }

        return $processed;
    }

    private function retryFailedConversations(): int
    {
        $this->info('🔁 Retrying Failed Conversations...');

        $failedConversations = Conversation::where('status', Conversation::STATUS_FAILED)
            ->where('retry_count', '<', 3)
            ->where('updated_at', '>', now()->subHours(24)) // Only retry recent failures
            ->orderBy('updated_at')
            ->limit(10)
            ->get();

        $retried = 0;

        foreach ($failedConversations as $conversation) {
            try {
                $this->line("  🔁 Retrying failed conversation #{$conversation->id}");
                
                $conversation->update([
                    'status' => Conversation::STATUS_PENDING,
                    'retry_count' => ($conversation->retry_count ?? 0) + 1,
                    'processing_started_at' => null
                ]);

                $this->processConversation($conversation);
                $retried++;
                
            } catch (InsufficientAiCreditsException $e) {
                $this->markConversationFailed($conversation, 'insufficient_ai_credits', false);

            } catch (\Exception $e) {
                $this->error("  ❌ Retry failed for conversation #{$conversation->id}: " . $e->getMessage());
                $this->markConversationFailed($conversation, 'Retry failed: ' . $e->getMessage());
            }
        }

        $this->reportCreditSkips();

        return $retried;
    }

    private function processConversation(Conversation $conversation, bool $priority = false)
    {
        DB::beginTransaction();

        try {
            // Mark as processing
            $conversation->update([
                'status' => Conversation::STATUS_PROCESSING,
                'processing_started_at' => now()
            ]);

            $lead = $conversation->lead;
            $agent = $lead->aiSalesAgent;

            if (!$agent || !$agent->is_active) {
                throw new \Exception("AI Sales Agent not available");
            }

            // Get conversation context
            $context = $this->buildConversationContext($conversation);
            
            // Generate AI response
            $customerMessage = $conversation->customer_message ?? $conversation->message_content ?? '';
            $conversationState = $conversation->conversation_state ?? 'INTRO';
            
            $response = $this->openAiService->generateResponse(
                $lead,
                $customerMessage,
                $context,
                $conversationState
            );

            if (!$response['success']) {
                if (($response['error'] ?? null) === 'insufficient_ai_credits') {
                    throw new InsufficientAiCreditsException('insufficient_ai_credits');
                }

                throw new \Exception($response['error'] ?? 'Failed to generate AI response');
            }

            // Process the response
            $result = $this->aiWhatsAppService->processConversationResponse(
                $conversation,
                $response,
                $priority
            );

            if ($result['success']) {
                $conversation->update([
                    'status' => Conversation::STATUS_COMPLETED,
                    'processing_started_at' => null,
                    'completed_at' => now(),
                    'last_ai_response' => $response['message_text']
                ]);

                // Update lead interaction timestamp
                $lead->touch('last_interaction_at');
            } else {
                throw new \Exception($result['error'] ?? 'Failed to send response');
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function buildConversationContext(Conversation $conversation): array
    {
        $lead = $conversation->lead;
        
        // Get related conversation messages safely
        $messages = $conversation->messages()->orderBy('created_at')->take(10)->get();
        
        // Build message content array safely
        $messageContents = [];
        foreach ($messages as $message) {
            $content = $message->message_content ?? $message->customer_message ?? $message->ai_response ?? '';
            if (!empty($content)) {
                $messageContents[] = $content;
            }
        }

        return [
            'conversation_id' => $conversation->id,
            'lead_name' => $lead->name ?? '',
            'conversation_stage' => $conversation->conversation_state ?? 'INTRO',
            'lead_score' => $lead->lead_score ?? 0,
            'recent_messages' => $messageContents,
            'lead_interests' => $lead->interests ?? [],
            'conversation_priority' => $conversation->priority ?? 1
        ];
    }

    /**
     * Keep the failure reason on the row. The conversations table has no `last_error` column (writing it
     * was silently discarded, so reasons were only ever visible in the log); ai_metadata is a real JSON column.
     */
    private function withLastError(Conversation $conversation, string $error): array
    {
        $meta = is_array($conversation->ai_metadata) ? $conversation->ai_metadata : [];
        $meta['last_error'] = $error;
        $meta['last_error_at'] = now()->toDateTimeString();

        return $meta;
    }

    /**
     * @param bool $retryable false = permanent failure (e.g. no AI credits): retry_count is set to the
     *                        maximum so retryFailedConversations() never picks it up again.
     */
    private function markConversationFailed(Conversation $conversation, string $error, bool $retryable = true)
    {
        $conversation->update([
            'status' => Conversation::STATUS_FAILED,
            'processing_started_at' => null,
            'ai_metadata' => $this->withLastError($conversation, $error),
            'retry_count' => $retryable
                ? ($conversation->retry_count ?? 0) + 1
                : max(self::MAX_RETRIES, (int) ($conversation->retry_count ?? 0)),
        ]);

        if (!$retryable) {
            // Counted and reported once per run by reportCreditSkips() rather than one ERROR line each.
            $this->skippedForCredits[$conversation->lead_id] = ($this->skippedForCredits[$conversation->lead_id] ?? 0) + 1;
            return;
        }

        Log::error('Conversation processing failed', [
            'conversation_id' => $conversation->id,
            'lead_id' => $conversation->lead_id,
            'error' => $error
        ]);
    }

    /**
     * Take a failed high-priority conversation out of the automatic queue so a person can look at it.
     * (Conversation::STATUS_ESCALATED and the requires_human_handoff / handoff_reason columns do not exist,
     * so the old version of this method crashed with "Undefined constant" instead of escalating.)
     */
    private function escalateConversation(Conversation $conversation, string $error)
    {
        $meta = $this->withLastError($conversation, 'High priority conversation failed: ' . $error);
        $meta['requires_human_handoff'] = true;

        $conversation->update([
            'status' => Conversation::STATUS_FAILED,
            'ai_metadata' => $meta,
            'retry_count' => max(self::MAX_RETRIES, (int) ($conversation->retry_count ?? 0)),
            'processing_started_at' => null
        ]);

        Log::warning('High priority conversation escalated', [
            'conversation_id' => $conversation->id,
            'lead_id' => $conversation->lead_id,
            'error' => $error
        ]);
    }
}