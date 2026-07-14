<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\AiSalesAgent;
use App\Models\Conversation;
use App\Services\AiWhatsAppService;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NoReplyChaseCommand extends Command
{
    protected $signature = 'ai-agent:chase-no-reply {--limit=50} {--agent=} {--hours=48} {--max-chases=3} {--dry-run}';
    protected $description = 'Follow up with leads who haven\'t replied to previous messages';

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
        $this->info('📞 Starting No-Reply Chase Campaign');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $agentId = $this->option('agent');
        $hoursThreshold = (int) $this->option('hours');
        $maxChases = (int) $this->option('max-chases');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No messages will be sent');
            $this->newLine();
        }

        try {
            // Get active AI sales agents
            $agents = $this->getActiveAgents($agentId);
            
            if ($agents->isEmpty()) {
                $this->warn('⚠️ No active AI sales agents found');
                return 1;
            }

            $totalSent = 0;

            foreach ($agents as $agent) {
                $this->info("🤖 Processing Agent: {$agent->name}");
                
                // Get leads with no reply for this agent
                $leads = $this->getNoReplyLeads($agent, $hoursThreshold, $maxChases, $limit);
                
                $this->info("📋 Found {$leads->count()} leads with no reply");

                if ($leads->isEmpty()) {
                    $this->warn("📭 No leads requiring follow-up");
                    continue;
                }

                foreach ($leads as $lead) {
                    try {
                        $sent = $this->processChaseFollowup($lead, $agent, $dryRun);
                        if ($sent) {
                            $totalSent++;
                            $this->line("  ✅ Chase sent to: {$lead->name} ({$lead->phone_number})");
                        } else {
                            $this->error("  ❌ Failed to send chase to: {$lead->name}");
                        }

                        // Add delay to avoid overwhelming the API
                        if (!$dryRun) {
                            sleep(2);
                        }

                    } catch (\Exception $e) {
                        $this->error("  💥 Error processing {$lead->name}: " . $e->getMessage());
                        Log::error('Chase follow-up error', [
                            'lead_id' => $lead->id,
                            'agent_id' => $agent->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $this->newLine();
            }

            $this->info("🎉 No-reply chase campaign completed!");
            $this->info("📊 Total follow-up messages sent: {$totalSent}");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("💥 Fatal error in chase campaign: " . $e->getMessage());
            Log::error('Chase campaign fatal error', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    private function getActiveAgents($agentId = null)
    {
        $query = AiSalesAgent::where('is_active', true)
            ->where('allow_outreach', true);

        if ($agentId) {
            $query->where('id', $agentId);
        }

        return $query->get();
    }

    private function getNoReplyLeads(AiSalesAgent $agent, int $hoursThreshold, int $maxChases, int $limit)
    {
        $noReplyThreshold = now()->subHours($hoursThreshold);
        
        return Lead::where('ai_sales_agent_id', $agent->id)
            ->whereIn('status', [
                Lead::STATUS_OUTREACHED,
                Lead::STATUS_ENGAGED,
                Lead::STATUS_QUALIFIED
            ])
            ->whereNotIn('status', [
                Lead::STATUS_DO_NOT_CONTACT,
                Lead::STATUS_CLOSED,
                Lead::STATUS_CONVERTED,
                Lead::STATUS_HANDED_OFF
            ])
            ->where('last_contact_at', '<', $noReplyThreshold)
            ->where(function($query) use ($noReplyThreshold) {
                // No reply received since our last contact
                $query->whereNull('last_reply_at')
                    ->orWhereColumn('last_reply_at', '<', 'last_contact_at');
            })
            ->where(function($query) use ($maxChases) {
                // NULL chase_count means never chased — treat as 0
                $query->whereNull('chase_count')
                    ->orWhere('chase_count', '<', $maxChases);
            })
            ->where(function($query) {
                // Don't chase if recently chased
                $query->whereNull('last_chase_at')
                    ->orWhere('last_chase_at', '<', now()->subHours(24));
            })
            ->where('lead_score', '>', 20) // Only chase leads with some potential
            ->orderByDesc('lead_score')
            ->orderBy('last_contact_at')
            ->limit($limit)
            ->get();
    }

    private function processChaseFollowup(Lead $lead, AiSalesAgent $agent, bool $dryRun): bool
    {
        try {
            // Determine chase strategy based on attempt number and lead behavior
            $chaseCount = $lead->chase_count ?? 0;
            $strategy = $this->determineChaseStrategy($lead, $chaseCount);
            
            // Generate personalized follow-up message
            $message = $this->generateChaseMessage($lead, $agent, $strategy, $chaseCount);
            
            if ($dryRun) {
                $this->line("📝 Would send chase #" . ($chaseCount + 1) . " ({$strategy}): " . substr($message, 0, 100) . '...');
                return true;
            }

            // Send chase message via the standard outreach channel
            $result = $this->aiWhatsAppService->sendOutreachMessage($lead, $message, $agent);

            if (!empty($result['skipped'])) {
                Log::info('No-reply chase skipped', [
                    'lead_id' => $lead->id,
                    'agent_id' => $agent->id,
                    'reason' => $result['reason'] ?? 'unknown',
                ]);
                return true;
            }

            if ($result['success']) {
                // Update lead tracking
                $lead->update([
                    'last_contact_at' => now(),
                    'last_chase_at' => now(),
                    'chase_count' => $chaseCount + 1,
                    'last_interaction_at' => now()
                ]);

                // Update or create conversation record
                $conversation = $lead->conversations()
                    ->where('status', Conversation::STATUS_ACTIVE)
                    ->latest()
                    ->first();

                if ($conversation) {
                    $conversation->update([
                        'last_ai_response' => $message,
                        'updated_at' => now()
                    ]);
                } else {
                    Conversation::create([
                        'lead_id' => $lead->id,
                        'conversation_state' => 'CHASE_FOLLOW_UP',
                        'status' => Conversation::STATUS_ACTIVE,
                        'priority' => 5,
                        'message_content' => $message,
                        'ai_metadata' => [
                            'strategy' => $strategy,
                            'chase_number' => $chaseCount + 1,
                            'agent_id' => $agent->id
                        ]
                    ]);
                }

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Chase follow-up processing error', [
                'lead_id' => $lead->id,
                'agent_id' => $agent->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function determineChaseStrategy(Lead $lead, int $chaseCount): string
    {
        $daysSinceContact = $lead->last_contact_at 
            ? now()->diffInDays($lead->last_contact_at) 
            : 0;

        // Strategy based on chase count and context
        switch ($chaseCount) {
            case 0: // First chase
                if ($lead->lead_score > 70) {
                    return 'GENTLE_REMINDER'; // High value lead
                } else {
                    return 'VALUE_ADDITION'; // Add more value
                }

            case 1: // Second chase
                if ($daysSinceContact > 7) {
                    return 'DIFFERENT_ANGLE'; // Try different approach
                } else {
                    return 'SOCIAL_PROOF'; // Add testimonials/case studies
                }

            case 2: // Final chase
                return 'FINAL_ATTEMPT'; // Last chance message

            default:
                return 'GENTLE_REMINDER';
        }
    }

    private function generateChaseMessage(Lead $lead, AiSalesAgent $agent, string $strategy, int $chaseCount): string
    {
        try {
            // Build context for message generation
            $context = $this->buildChaseContext($lead, $strategy, $chaseCount);

            // Craft an instruction prompt for the sales AI
            $prompt = "Generate a short, friendly WhatsApp follow-up message for a lead who hasn't replied. "
                . "Strategy: {$strategy}. Chase attempt #{$context['chase_number']}. "
                . "Lead name: {$context['lead_name']}. "
                . "Days since last contact: {$context['days_since_contact']}. "
                . "Keep it under 3 sentences. Be warm and conversational.";

            // Generate AI-powered chase message via the standard sales-response API
            $response = $this->openAiService->generateSalesResponse(
                $prompt,
                $agent,
                $lead,
                [],   // no prior conversation history needed for a chase
                null, // no specific product context
                null  // no specific WhatsApp instance context
            );

            return ($response['success'] ?? false)
                ? ($response['response'] ?? $this->getFallbackChaseMessage($lead, $strategy, $chaseCount))
                : $this->getFallbackChaseMessage($lead, $strategy, $chaseCount);

        } catch (\Exception $e) {
            Log::error('Chase message generation error', [
                'lead_id' => $lead->id,
                'strategy' => $strategy,
                'chase_count' => $chaseCount,
                'error' => $e->getMessage()
            ]);
            
            return $this->getFallbackChaseMessage($lead, $strategy, $chaseCount);
        }
    }

    private function buildChaseContext(Lead $lead, string $strategy, int $chaseCount): array
    {
        $daysSinceContact = $lead->last_contact_at 
            ? now()->diffInDays($lead->last_contact_at) 
            : 0;

        $lastConversation = $lead->conversations()
            ->latest()
            ->first();

        return [
            'lead_name' => $lead->name,
            'company_name' => $lead->company_name,
            'lead_score' => $lead->lead_score,
            'days_since_contact' => $daysSinceContact,
            'chase_number' => $chaseCount + 1,
            'strategy' => $strategy,
            'last_conversation_stage' => $lastConversation?->conversation_state,
            'previous_interests' => $lead->interests ?? [],
            'is_final_attempt' => $chaseCount >= 2
        ];
    }

    private function getFallbackChaseMessage(Lead $lead, string $strategy, int $chaseCount): string
    {
        $name = $lead->name ?? 'there';
        $chaseNumber = $chaseCount + 1;
        
        $messages = [
            'GENTLE_REMINDER' => [
                "Hi {$name}! Just wanted to follow up on my previous message. Any thoughts? 😊",
                "Hope you got my last message, {$name}. Would love to hear from you! 👋",
                "Hi {$name}, checking if you had a chance to consider what we discussed? 🤔"
            ],
            'VALUE_ADDITION' => [
                "Hi {$name}! Thought you might find this additional info helpful. Happy to discuss! 💡",
                "Hi {$name}, here's something that might interest you based on our previous chat... 📈",
                "Quick follow-up, {$name} - found something that could be perfect for your needs! ✨"
            ],
            'DIFFERENT_ANGLE' => [
                "Hi {$name}! Let me try a different approach - maybe this angle resonates better? 🎯",
                "Taking a step back, {$name} - perhaps we can explore this from another perspective? 🔄",
                "Hi {$name}, maybe I wasn't clear before. Let me put it this way... 💭"
            ],
            'SOCIAL_PROOF' => [
                "Hi {$name}! Just helped another client with similar needs - thought you'd be interested! 🌟",
                "Quick update, {$name} - seeing great results with clients like you. Worth a chat? 📊",
                "Hi {$name}, our recent success story might interest you... 🏆"
            ],
            'FINAL_ATTEMPT' => [
                "Hi {$name}! This will be my last message - just wanted to ensure I hadn't missed anything? 🤝",
                "Final check-in, {$name}. If now isn't right, I totally understand. Best wishes! 👋",
                "Hi {$name}, wrapping up my outreach - door's always open if you change your mind! 🚪"
            ]
        ];

        $strategyMessages = $messages[$strategy] ?? $messages['GENTLE_REMINDER'];
        $messageIndex = min($chaseNumber - 1, count($strategyMessages) - 1);
        
        return $strategyMessages[$messageIndex];
    }
}