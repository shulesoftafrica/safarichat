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

class WinBackOutreachCommand extends Command
{
    protected $signature = 'ai-agent:win-back {--limit=30} {--agent=} {--days-inactive=30} {--dry-run}';
    protected $description = 'Execute win-back campaigns for churned or inactive customers';

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
        $this->info('🔄 Starting Win-Back Campaign');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $agentId = $this->option('agent');
        $daysInactive = (int) $this->option('days-inactive');
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
                
                // Get churned/inactive leads for this agent
                $leads = $this->getChurnedLeads($agent, $daysInactive, $limit);
                
                $this->info("📋 Found {$leads->count()} churned/inactive leads");

                if ($leads->isEmpty()) {
                    $this->warn("📭 No churned leads found for win-back");
                    continue;
                }

                foreach ($leads as $lead) {
                    try {
                        $sent = $this->processWinBackOutreach($lead, $agent, $dryRun);
                        if ($sent) {
                            $totalSent++;
                            $this->line("  ✅ Win-back sent to: {$lead->name} ({$lead->phone_number})");
                        } else {
                            $this->error("  ❌ Failed to send win-back to: {$lead->name}");
                        }

                        // Add delay to avoid overwhelming the API
                        if (!$dryRun) {
                            sleep(3);
                        }

                    } catch (\Exception $e) {
                        $this->error("  💥 Error processing {$lead->name}: " . $e->getMessage());
                        Log::error('Win-back outreach error', [
                            'lead_id' => $lead->id,
                            'agent_id' => $agent->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $this->newLine();
            }

            $this->info("🎉 Win-back campaign completed!");
            $this->info("📊 Total win-back messages sent: {$totalSent}");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("💥 Fatal error in win-back campaign: " . $e->getMessage());
            Log::error('Win-back campaign fatal error', ['error' => $e->getMessage()]);
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

    private function getChurnedLeads(AiSalesAgent $agent, int $daysInactive, int $limit)
    {
        $inactiveDate = now()->subDays($daysInactive);
        
        return Lead::where('ai_sales_agent_id', $agent->id)
            ->whereIn('status', [
                Lead::STATUS_INACTIVE,
                Lead::STATUS_INTERESTED,
                Lead::STATUS_NURTURING
            ])
            ->whereNotIn('status', [
                Lead::STATUS_DO_NOT_CONTACT,
                Lead::STATUS_CLOSED,
                Lead::STATUS_CONVERTED
            ])
            ->where(function($query) use ($inactiveDate) {
                $query->where('last_interaction_at', '<', $inactiveDate)
                    ->orWhere(function($q) use ($inactiveDate) {
                        $q->whereNull('last_interaction_at')
                          ->where('created_at', '<', $inactiveDate);
                    });
            })
            ->where(function($query) {
                // Don't contact if recently contacted for win-back
                $query->whereNull('last_winback_at')
                    ->orWhere('last_winback_at', '<', now()->subDays(14));
            })
            ->where('lead_score', '>', 30) // Only target leads with decent scores
            ->orderByDesc('lead_score')
            ->orderByDesc('last_interaction_at')
            ->limit($limit)
            ->get();
    }

    private function processWinBackOutreach(Lead $lead, AiSalesAgent $agent, bool $dryRun): bool
    {
        try {
            // Determine win-back strategy based on lead history
            $strategy = $this->determineWinBackStrategy($lead);
            
            // Generate personalized win-back message
            $message = $this->generateWinBackMessage($lead, $agent, $strategy);
            
            if ($dryRun) {
                $this->line("📝 Would send ({$strategy}): " . substr($message, 0, 100) . '...');
                return true;
            }

            // Send win-back message
            $result = $this->aiWhatsAppService->sendWinBackMessage($lead, $message, $agent, $strategy);

            if ($result['success']) {
                // Update lead status and timestamps
                $lead->update([
                    'status' => Lead::STATUS_WIN_BACK_ATTEMPTED,
                    'last_contact_at' => now(),
                    'last_winback_at' => now(),
                    'winback_attempts' => ($lead->winback_attempts ?? 0) + 1
                ]);

                // Create conversation record for tracking
                Conversation::create([
                    'lead_id' => $lead->id,
                    'conversation_stage' => 'WIN_BACK',
                    'status' => Conversation::STATUS_ACTIVE,
                    'priority' => 6,
                    'last_message_content' => $message,
                    'metadata' => json_encode([
                        'strategy' => $strategy,
                        'agent_id' => $agent->id,
                        'campaign_type' => 'win_back'
                    ])
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Win-back processing error', [
                'lead_id' => $lead->id,
                'agent_id' => $agent->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function determineWinBackStrategy(Lead $lead): string
    {
        // Analyze lead history to determine best approach
        $daysSinceLastContact = $lead->last_interaction_at 
            ? now()->diffInDays($lead->last_interaction_at) 
            : 999;

        $leadScore = $lead->lead_score ?? 0;
        $previousConversations = $lead->conversations()->count();
        $winbackAttempts = $lead->winback_attempts ?? 0;

        // Strategy logic
        if ($daysSinceLastContact > 90 && $leadScore > 70) {
            return 'MISSED_CONNECTION'; // "We miss you" approach
        } elseif ($leadScore > 60 && $previousConversations > 3) {
            return 'VALUE_REMINDER'; // Remind of previous interest/value
        } elseif ($winbackAttempts === 0 && $leadScore > 50) {
            return 'SPECIAL_OFFER'; // First win-back attempt with incentive
        } elseif ($daysSinceLastContact > 60) {
            return 'CHECK_IN'; // Simple check-in approach
        } elseif ($leadScore > 40) {
            return 'UPDATE_SHARE'; // Share updates/new features
        } else {
            return 'LAST_CHANCE'; // Final attempt
        }
    }

    private function generateWinBackMessage(Lead $lead, AiSalesAgent $agent, string $strategy): string
    {
        try {
            // Get lead context for personalization
            $context = $this->buildWinBackContext($lead, $strategy);
            
            // Generate AI-powered win-back message
            $prompt = $this->buildWinBackPrompt($context, $agent, $strategy);
            
            // Get AI response (reusing existing OpenAI service)
            $response = $this->openAiService->generateWinBackMessage($lead, $strategy, $context);

            return $response['message_text'] ?? $this->getFallbackWinBackMessage($lead, $strategy);

        } catch (\Exception $e) {
            Log::error('Win-back message generation error', [
                'lead_id' => $lead->id,
                'strategy' => $strategy,
                'error' => $e->getMessage()
            ]);
            
            return $this->getFallbackWinBackMessage($lead, $strategy);
        }
    }

    private function buildWinBackContext(Lead $lead, string $strategy): array
    {
        $daysSinceContact = $lead->last_interaction_at 
            ? now()->diffInDays($lead->last_interaction_at) 
            : 999;

        return [
            'lead_name' => $lead->name,
            'company_name' => $lead->company_name,
            'industry' => $lead->industry,
            'lead_score' => $lead->lead_score,
            'days_since_contact' => $daysSinceContact,
            'previous_interests' => $lead->interests ?? [],
            'strategy' => $strategy,
            'winback_attempts' => $lead->winback_attempts ?? 0,
            'last_conversation_stage' => $lead->conversations()->latest()->first()?->conversation_stage
        ];
    }

    private function buildWinBackPrompt(array $context, AiSalesAgent $agent, string $strategy): string
    {
        $basePrompt = "Generate a win-back message using the {$strategy} strategy for {$context['lead_name']} " .
                     "who hasn't been in touch for {$context['days_since_contact']} days. ";

        $strategyPrompts = [
            'MISSED_CONNECTION' => 'Express that we miss working with them and value the relationship.',
            'VALUE_REMINDER' => 'Remind them of the value we previously discussed and their interests.',
            'SPECIAL_OFFER' => 'Include a special offer or incentive to re-engage.',
            'CHECK_IN' => 'Simple, friendly check-in to see how they\'re doing.',
            'UPDATE_SHARE' => 'Share exciting updates or new features that might interest them.',
            'LAST_CHANCE' => 'Final attempt with urgency but remain respectful.'
        ];

        $strategyPrompt = $strategyPrompts[$strategy] ?? 'Create a personalized, respectful re-engagement message.';
        
        return $basePrompt . $strategyPrompt . ' Keep it under 200 characters and maintain a ' . 
               ($agent->personality_type ?? 'professional') . ' tone.';
    }

    private function getFallbackWinBackMessage(Lead $lead, string $strategy): string
    {
        $name = $lead->name ?? 'there';
        
        $messages = [
            'MISSED_CONNECTION' => "Hi {$name}! We haven't connected in a while and wanted to reach out. Hope you're doing well! 😊",
            'VALUE_REMINDER' => "Hi {$name}! Remember when we discussed how we could help your business? Still here if you're interested! 💼",
            'SPECIAL_OFFER' => "Hi {$name}! We have something special that might interest you. Would love to reconnect and share details! ✨",
            'CHECK_IN' => "Hi {$name}! Just checking in to see how things are going. Hope all is well! 👋",
            'UPDATE_SHARE' => "Hi {$name}! We've made some exciting updates that might interest you. Would love to share! 🚀",
            'LAST_CHANCE' => "Hi {$name}! One final check - are you still interested in what we discussed? No pressure! 🤝"
        ];

        return $messages[$strategy] ?? "Hi {$name}! Hope you're doing well. Would love to reconnect when you have a moment! 😊";
    }
}