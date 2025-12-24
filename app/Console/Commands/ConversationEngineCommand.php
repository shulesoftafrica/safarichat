<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\AiSalesAgent;
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
                
            } catch (\Exception $e) {
                $this->error("  ❌ Failed to process conversation #{$conversation->id}: " . $e->getMessage());
                $this->markConversationFailed($conversation, $e->getMessage());
            }
        }

        return $processed;
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
                $conversation->update([
                    'status' => Conversation::STATUS_PENDING,
                    'processing_started_at' => null,
                    'retry_count' => ($conversation->retry_count ?? 0) + 1,
                    'last_error' => 'Recovered from stuck state after ' . $timeoutMinutes . ' minutes'
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
                
            } catch (\Exception $e) {
                $this->error("  ❌ Retry failed for conversation #{$conversation->id}: " . $e->getMessage());
                $this->markConversationFailed($conversation, 'Retry failed: ' . $e->getMessage());
            }
        }

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

    private function markConversationFailed(Conversation $conversation, string $error)
    {
        $conversation->update([
            'status' => Conversation::STATUS_FAILED,
            'processing_started_at' => null,
            'last_error' => $error,
            'retry_count' => ($conversation->retry_count ?? 0) + 1
        ]);

        Log::error('Conversation processing failed', [
            'conversation_id' => $conversation->id,
            'lead_id' => $conversation->lead_id,
            'error' => $error
        ]);
    }

    private function escalateConversation(Conversation $conversation, string $error)
    {
        $conversation->update([
            'status' => Conversation::STATUS_ESCALATED,
            'requires_human_handoff' => true,
            'handoff_reason' => 'High priority conversation failed: ' . $error,
            'processing_started_at' => null
        ]);

        Log::warning('High priority conversation escalated', [
            'conversation_id' => $conversation->id,
            'lead_id' => $conversation->lead_id,
            'error' => $error
        ]);
    }
}