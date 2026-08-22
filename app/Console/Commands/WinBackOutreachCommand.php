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
        // Win-back re-messages inactive leads. Only run it for leads that engaged
        // (replied) before; never cold-message silent contacts.
        if (!config('outreach.enabled', true) || config('outreach.reply_required', true)) {
            $this->warn('Win-back skipped — outreach disabled or reply-required mode is on.');
            \Illuminate\Support\Facades\Log::info('Win-back skipped (anti-spam settings)');
            return 0;
        }

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
                Lead::STATUS_CHURNED,
                Lead::STATUS_LOST
            ])
            ->whereNotIn('status', [
                Lead::STATUS_DO_NOT_CONTACT,
                Lead::STATUS_CLOSED,
                Lead::STATUS_CONVERTED,
                Lead::STATUS_HANDED_OFF,
                Lead::STATUS_OUTREACHED,  // Don't re-contact already contacted leads
                Lead::STATUS_ENGAGED,      // Don't contact engaged customers
                Lead::STATUS_QUALIFIED     // Don't contact qualified prospects
            ])
            ->where(function($query) use ($inactiveDate) {
                // Consider both last interaction and conversation activity
                $query->whereNull('last_interaction_at')
                ->orWhere('last_interaction_at', '<', $inactiveDate)
                    ->orWhere(function($q) use ($inactiveDate) {
                        $q->whereNull('last_interaction_at')
                          ->where('created_at', '<', $inactiveDate);
                    })
                    ->orWhereDoesntHave('conversations', function($q) use ($inactiveDate) {
                        $q->where('updated_at', '>', $inactiveDate);
                    });
            })
            ->where(function($query) {
                // Don't contact if recently contacted for win-back
                $query->whereNull('last_win_back_at')
                    ->orWhere('last_win_back_at', '<', now()->subDays(14));
            })
            ->where('lead_score', '>=', 0) // Lower threshold for churned customers
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

            // Send win-back message using existing outreach method
            $result = $this->aiWhatsAppService->sendOutreachMessage($lead, $message, $agent, 'win_back');

            if (!empty($result['skipped'])) {
                Log::info('Win-back outreach skipped', [
                    'lead_id' => $lead->id,
                    'agent_id' => $agent->id,
                    'reason' => $result['reason'] ?? 'unknown',
                ]);
                return true;
            }

            if ($result['success']) {
                // Update lead status and timestamps
                $lead->update([
                    'status' => Lead::STATUS_OUTREACHED, // Changed to existing status
                    'last_contact_at' => now(),
                    'last_win_back_at' => now(),
                    'win_back_attempts' => ($lead->win_back_attempts ?? 0) + 1
                ]);

                // Create conversation record for tracking
                Conversation::create([
                    'lead_id' => $lead->id,
                    'conversation_state' => 'WIN_BACK',
                    'status' => Conversation::STATUS_ACTIVE,
                    'priority' => 6,
                    'message_content' => $message,
                    'ai_metadata' => [
                        'strategy' => $strategy,
                        'agent_id' => $agent->id,
                        'campaign_type' => 'win_back'
                    ]
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
        // Analyze lead history and conversation patterns to determine best approach
        $daysSinceLastContact = $lead->last_interaction_at 
            ? now()->diffInDays($lead->last_interaction_at) 
            : 999;

        $leadScore = $lead->lead_score ?? 0;
        $winbackAttempts = $lead->win_back_attempts ?? 0;
        
        // Get conversation history for deeper insights
        $conversations = $lead->conversations()
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $totalConversations = $conversations->count();
        $lastConversationStage = $conversations->first()?->conversation_state ?? 'UNKNOWN';
        
        // Analyze conversation patterns
        $hadDeepEngagement = $conversations->where('conversation_state', 'NEGOTIATING')->count() > 0
            || $conversations->where('conversation_state', 'PROPOSAL_SENT')->count() > 0;
            
        $showedHighInterest = $conversations->where('conversation_state', 'INTERESTED')->count() > 2
            || $conversations->where('conversation_state', 'DEMO_REQUESTED')->count() > 0;
            
        $wasCloseToDeal = in_array($lastConversationStage, ['NEGOTIATING', 'PROPOSAL_SENT', 'DEMO_SCHEDULED']);
        
        // Enhanced strategy logic based on conversation history
        if ($lead->status === Lead::STATUS_CHURNED) {
            if ($hadDeepEngagement && $leadScore > 60) {
                return 'RELATIONSHIP_RECOVERY'; // Address what went wrong
            } elseif ($showedHighInterest) {
                return 'REIGNITE_INTEREST'; // Remind of their previous enthusiasm
            } else {
                return 'FRESH_START'; // New approach, clean slate
            }
        }
        
        if ($wasCloseToDeal && $daysSinceLastContact > 30) {
            return 'DEAL_REVIVAL'; // Address the stalled deal
        }
        
        if ($daysSinceLastContact > 90 && $leadScore > 70) {
            return 'MISSED_CONNECTION'; // "We miss you" approach
        } elseif ($leadScore > 60 && $totalConversations > 3) {
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
            
            // Generate AI-powered win-back message using existing OpenAI service
            $prompt = $this->buildWinBackPrompt($context, $agent, $strategy);
            
            // Use the existing generateResponse method
            $response = $this->openAiService->generateResponse($lead, null, [
                'prompt' => $prompt,
                'strategy' => $strategy,
                'context' => $context
            ], 'WIN_BACK');

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

        // Get business information
        $business = $lead->business;
        $businessName = $business ? ($business->business_name ?? $business->name ?? 'our team') : 'our team';
        
        // Get products/services if available
        $products = $business && $business->products ? $business->products()->where('is_active', true)->limit(3)->pluck('product_name')->toArray() : [];

        // ===== ENHANCED: Deep conversation analysis (increased from 3 to 10) =====
        $recentConversations = $lead->conversations()
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
            
        $conversationSummary = [];
        $lastTopics = [];
        $engagementLevel = 'low';
        
        foreach ($recentConversations as $conv) {
            $conversationSummary[] = [
                'stage' => $conv->conversation_state,
                'date' => $conv->updated_at->diffForHumans(),
                'messages_count' => $conv->messages()->count()
            ];
            
            // Extract topics from last message content
            if ($conv->last_message_content) {
                $lastTopics[] = substr($conv->last_message_content, 0, 100);
            }
        }
        
        // Determine engagement level based on conversation history
        $totalMessages = $recentConversations->sum(function($conv) {
            return $conv->messages()->count();
        });
        
        if ($totalMessages > 10) {
            $engagementLevel = 'high';
        } elseif ($totalMessages > 5) {
            $engagementLevel = 'medium';
        }

        // ===== NEW: Intelligent churn analysis =====
        $churnAnalysis = $this->analyzeChurnReason($recentConversations, $lead);
        $productInterests = $this->extractProductInterests($recentConversations);
        $objections = $this->detectObjections($recentConversations);
        $sentimentPattern = $this->analyzeSentimentPattern($recentConversations);
        $dropOffStage = $this->detectDropOffStage($recentConversations);

        return [
            'lead_name' => $lead->name,
            'company_name' => $lead->company_name,
            'industry' => $lead->industry,
            'lead_score' => $lead->lead_score,
            'days_since_contact' => $daysSinceContact,
            'previous_interests' => $lead->interests ?? [],
            'strategy' => $strategy,
            'winback_attempts' => $lead->winback_attempts ?? 0,
            'last_conversation_stage' => $recentConversations->first()?->conversation_state,
            'conversation_history' => $conversationSummary,
            'last_topics' => $lastTopics,
            'engagement_level' => $engagementLevel,
            'total_conversations' => $lead->conversations()->count(),
            'is_churned' => $lead->status === Lead::STATUS_CHURNED,
            'business_name' => $businessName,
            'products' => $products,
            // ===== NEW: Churn intelligence fields =====
            'churn_reason' => $lead->churn_reason ?? $churnAnalysis['reason'],
            'likely_objection' => $churnAnalysis['primary_objection'],
            'product_interests' => $productInterests,
            'objections_raised' => $objections,
            'sentiment_pattern' => $sentimentPattern,
            'drop_off_stage' => $dropOffStage,
            'last_conversation_summary' => $churnAnalysis['last_conversation_summary']
        ];
    }

    private function buildWinBackPrompt(array $context, AiSalesAgent $agent, string $strategy): string
    {
        $businessName = $context['business_name'] ?? 'our company';
        $products = !empty($context['products']) ? implode(', ', $context['products']) : 'our solutions';
        $customerCompany = $context['company_name'] ? " from {$context['company_name']}" : '';
        $industry = $context['industry'] ? " in the {$context['industry']} industry" : '';
        $name = $context['lead_name'];
        
        // Build TARGETED prompt based on churn analysis
        $basePrompt = "Generate a highly personalized win-back WhatsApp message from {$businessName} for {$name}{$customerCompany}{$industry}. ";
        
        // ===== NEW: Address specific churn reason =====
        if ($context['churn_reason'] && $context['churn_reason'] !== 'UNKNOWN' && $context['churn_reason'] !== 'NO_INTERACTION') {
            $basePrompt .= "CRITICAL CONTEXT: They stopped engaging because of {$context['churn_reason']}. ";
            
            // Tailor message based on specific objection
            switch ($context['churn_reason']) {
                case 'PRICE_CONCERN':
                    $basePrompt .= "Address pricing concerns by mentioning flexibility, payment plans, or ROI benefits. ";
                    break;
                case 'TIMING_ISSUE':
                    $basePrompt .= "Acknowledge timing was an issue, ask if now might be better, emphasize quick/easy setup. ";
                    break;
                case 'COMPETITOR':
                    $basePrompt .= "Highlight unique value proposition, recent improvements, what sets {$businessName} apart from competitors. ";
                    break;
                case 'FEATURE_GAP':
                    $basePrompt .= "Mention new features added since last conversation, product updates, or customization options available. ";
                    break;
                case 'COMPLEXITY_CONCERN':
                    $basePrompt .= "Emphasize simplicity and ease of use, offer guided setup/onboarding, mention dedicated support. ";
                    break;
            }
        }
        
        // Add product interest context
        if (!empty($context['product_interests'])) {
            $interests = implode(', ', $context['product_interests']);
            $basePrompt .= "They previously showed interest in: {$interests}. Reference this specifically. ";
        } elseif (!empty($context['products'])) {
            $basePrompt .= "We offer: {$products}. ";
        }
        
        // Add sentiment context
        if ($context['sentiment_pattern'] === 'DECLINED') {
            $basePrompt .= "NOTE: They were initially positive but concerns emerged. Acknowledge this and show how we've addressed issues. ";
        } elseif ($context['sentiment_pattern'] === 'MOSTLY_NEGATIVE') {
            $basePrompt .= "They had concerns. Show empathy and willingness to make things right. ";
        }
        
        // Add engagement level context
        if ($context['engagement_level'] === 'HIGH') {
            $basePrompt .= "They were highly engaged previously - show genuine appreciation for their time and interest. ";
        }
        
        // Add conversation topics if available
        if (!empty($context['last_topics'])) {
            $basePrompt .= "Previous topics discussed: " . implode('; ', array_slice($context['last_topics'], 0, 2)) . ". ";
        }

        $strategyPrompts = [
            'MISSED_CONNECTION' => "Express that {$businessName} misses working with them and values the relationship.",
            'VALUE_REMINDER' => "Remind them of the specific value {$businessName} discussed and their interests.",
            'SPECIAL_OFFER' => "Mention a special offer or incentive from {$businessName} to re-engage.",
            'CHECK_IN' => "Simple, friendly check-in from {$businessName} to see if timing is better now.",
            'UPDATE_SHARE' => "Share exciting updates from {$businessName} that address their previous concerns.",
            'LAST_CHANCE' => "Final respectful attempt from {$businessName}. No pressure.",
            'RELATIONSHIP_RECOVERY' => "Acknowledge past concerns with {$businessName} and show how we can help better now.",
            'REIGNITE_INTEREST' => "Remind them of their previous enthusiasm about {$businessName}'s solutions.",
            'FRESH_START' => "Offer a new approach with {$businessName} that addresses their situation.",
            'DEAL_REVIVAL' => "Revisit the stalled proposal with {$businessName} and offer to move forward."
        ];

        $strategyPrompt = $strategyPrompts[$strategy] ?? "Create a personalized, respectful re-engagement message from {$businessName}.";
        
         return $basePrompt . $strategyPrompt . " CRITICAL: Must mention '{$businessName}'. Keep under 320 characters. Be warm, conversational, and professional. " .
             "No markdown, no separators, no signatures, and no meta phrases like 'Certainly' or 'Here is your message'. " .
             "Output only the final WhatsApp message text. " .
             "Tone: " . ($agent->personality_type ?? 'professional') . ".";
    }

    private function getFallbackWinBackMessage(Lead $lead, string $strategy): string
    {
        $name = $lead->name ?? 'there';
        $business = $lead->business;
        $businessName = $business ? ($business->business_name ?? $business->name ?? 'our team') : 'our team';
        
        // Get main product/service if available
        $mainProduct = '';
        if ($business && $business->products && $business->products()->exists()) {
            $product = $business->products()->where('is_active', true)->first();
            $mainProduct = $product ? " regarding {$product->product_name}" : '';
        }
        
        // ===== ENHANCED: Use churn intelligence even in fallback =====
        $conversations = $lead->conversations()->orderBy('updated_at', 'desc')->limit(10)->get();
        $churnAnalysis = $this->analyzeChurnReason($conversations, $lead);
        $churnReason = $churnAnalysis['reason'];
        
        // Log what we detected for debugging
        Log::info('Fallback win-back message generation', [
            'lead_id' => $lead->id,
            'lead_name' => $name,
            'conversations_count' => $conversations->count(),
            'churn_reason' => $churnReason,
            'strategy' => $strategy,
            'has_stored_churn_reason' => !empty($lead->churn_reason)
        ]);
        
        // Generate targeted message based on churn reason
        if ($churnReason !== 'UNKNOWN' && $churnReason !== 'NO_INTERACTION') {
            return $this->getTargetedFallbackMessage($name, $businessName, $mainProduct, $churnReason, $conversations);
        }
        
        // Check if lead has stored churn reason even without conversations
        if ($lead->churn_reason) {
            $storedReason = strtoupper(str_replace(' ', '_', $lead->churn_reason));
            return $this->getTargetedFallbackMessage($name, $businessName, $mainProduct, $storedReason, $conversations);
        }
        
        $messages = [
            'MISSED_CONNECTION' => "Hi {$name}, this is {$businessName}. We have not connected in a while and wanted to check in. If this is still relevant, I can share the fastest next step.",
            'VALUE_REMINDER' => "Hi {$name}, {$businessName} here{$mainProduct}. We previously discussed how this could support your operations. If useful, I can send a short summary tailored to your current priorities.",
            'SPECIAL_OFFER' => "Hi {$name}, {$businessName} here. We have a limited win-back option{$mainProduct} that may suit your setup. Let me know and I will share the details.",
            'CHECK_IN' => "Hi {$name}, this is {$businessName}{$mainProduct}. Quick check-in to see whether this is still a priority on your side.",
            'UPDATE_SHARE' => "Hi {$name}, {$businessName} here. We have recent updates{$mainProduct} that address common operational gaps. I can share the key improvements in one message.",
            'LAST_CHANCE' => "Hi {$name}, this is {$businessName}{$mainProduct}. Final follow-up from my side for now. If the timing changes, we will be ready to assist.",
            'RELATIONSHIP_RECOVERY' => "Hi {$name}, {$businessName} here. I understand earlier conversations may not have met your expectations. If you are open to it, we can restart with a clearer and more tailored approach.",
            'REIGNITE_INTEREST' => "Hi {$name}, this is {$businessName}{$mainProduct}. You previously showed strong interest, so I wanted to reconnect and see if this is back on your roadmap.",
            'FRESH_START' => "Hi {$name}, {$businessName} here. We can begin with a fresh approach{$mainProduct} based on your current needs. If you want, I will share a concise plan.",
            'DEAL_REVIVAL' => "Hi {$name}, this is {$businessName}. Following up on the proposal we discussed{$mainProduct}. If this is still under consideration, I can send an updated summary and next steps."
        ];

        return $messages[$strategy] ?? "Hi {$name}, this is {$businessName}. We would be glad to reconnect when convenient and support your current priorities.";
    }

    /**
     * Analyze why customer churned based on conversation history
     */
    private function analyzeChurnReason($conversations, $lead): array
    {
        if ($conversations->isEmpty()) {
            return [
                'reason' => 'NO_INTERACTION',
                'primary_objection' => null,
                'last_conversation_summary' => null
            ];
        }
        
        $lastConversation = $conversations->first();
        
        // Collect both customer messages AND AI-generated content (for CRM imports)
        $allMessages = $conversations->pluck('customer_message')->filter()->implode(' ');
        $allAIContent = $conversations->pluck('message_content')->filter()->implode(' ');
        $combinedContent = $allMessages . ' ' . $allAIContent;
        $messageLower = strtolower($combinedContent);
        
        // Detect primary churn reason from conversation content
        $reason = 'UNKNOWN';
        $objection = null;
        
        if (strpos($messageLower, 'expensive') !== false || 
            strpos($messageLower, 'too much') !== false || 
            strpos($messageLower, 'price') !== false ||
            strpos($messageLower, 'cost') !== false ||
            strpos($messageLower, 'budget') !== false ||
            strpos($messageLower, 'afford') !== false) {
            $reason = 'PRICE_CONCERN';
            $objection = 'pricing_too_high';
        }
        elseif (strpos($messageLower, 'later') !== false || 
                strpos($messageLower, 'not now') !== false ||
                strpos($messageLower, 'timing') !== false ||
                strpos($messageLower, 'busy') !== false ||
                strpos($messageLower, 'next month') !== false ||
                strpos($messageLower, 'next year') !== false) {
            $reason = 'TIMING_ISSUE';
            $objection = 'bad_timing';
        }
        elseif (strpos($messageLower, 'already have') !== false || 
                strpos($messageLower, 'competitor') !== false ||
                strpos($messageLower, 'another solution') !== false ||
                strpos($messageLower, 'another system') !== false ||
                strpos($messageLower, 'current system') !== false ||
                strpos($messageLower, 'currently using') !== false ||
                strpos($messageLower, 'existing system') !== false ||
                strpos($messageLower, 'using') !== false) {
            $reason = 'COMPETITOR';
            $objection = 'using_competitor';
        }
        elseif (strpos($messageLower, 'feature') !== false || 
                strpos($messageLower, 'need') !== false ||
                strpos($messageLower, 'missing') !== false ||
                strpos($messageLower, 'doesn\'t have') !== false) {
            $reason = 'FEATURE_GAP';
            $objection = 'missing_features';
        }
        elseif (strpos($messageLower, 'complicated') !== false || 
                strpos($messageLower, 'complex') !== false ||
                strpos($messageLower, 'difficult') !== false ||
                strpos($messageLower, 'hard to') !== false) {
            $reason = 'COMPLEXITY_CONCERN';
            $objection = 'too_complex';
        }
        
        // Use stored churn reason if available and override if it's more specific
        if ($lead->churn_reason && $reason === 'UNKNOWN') {
            $reason = strtoupper(str_replace(' ', '_', $lead->churn_reason));
        }
        
        return [
            'reason' => $reason,
            'primary_objection' => $objection,
            'last_conversation_summary' => substr($lastConversation->customer_message ?? '', 0, 150)
        ];
    }

    /**
     * Extract product interests from conversations
     */
    private function extractProductInterests($conversations): array
    {
        $interests = [];
        
        foreach ($conversations as $conv) {
            if ($conv->product_id && $conv->product) {
                $interests[] = $conv->product->product_name;
            }
            
            // Check ai_actions metadata for product mentions
            if ($conv->ai_actions && is_array($conv->ai_actions)) {
                if (isset($conv->ai_actions['recommended_products'])) {
                    $interests = array_merge($interests, $conv->ai_actions['recommended_products']);
                }
                if (isset($conv->ai_actions['discussed_products'])) {
                    $interests = array_merge($interests, $conv->ai_actions['discussed_products']);
                }
            }
        }
        
        return array_values(array_unique($interests));
    }

    /**
     * Detect objections raised in conversations
     */
    private function detectObjections($conversations): array
    {
        $objections = [];
        
        foreach ($conversations as $conv) {
            $message = strtolower($conv->customer_message ?? '');
            
            if (strpos($message, 'expensive') !== false || strpos($message, 'price') !== false || strpos($message, 'cost') !== false) {
                $objections[] = 'pricing';
            }
            if (strpos($message, 'feature') !== false || strpos($message, 'need') !== false || strpos($message, 'missing') !== false) {
                $objections[] = 'features';
            }
            if (strpos($message, 'time') !== false || strpos($message, 'busy') !== false || strpos($message, 'later') !== false) {
                $objections[] = 'timing';
            }
            if (strpos($message, 'complicated') !== false || strpos($message, 'complex') !== false) {
                $objections[] = 'complexity';
            }
            if ($conv->conversation_state === 'OBJECTION_HANDLING') {
                $objections[] = 'general_objection';
            }
        }
        
        return array_values(array_unique($objections));
    }

    /**
     * Analyze sentiment pattern over conversation history
     */
    private function analyzeSentimentPattern($conversations): string
    {
        if ($conversations->isEmpty()) {
            return 'NEUTRAL';
        }
        
        $sentiments = $conversations->pluck('sentiment')->filter();
        
        if ($sentiments->isEmpty()) {
            return 'NEUTRAL';
        }
        
        $lastSentiment = $sentiments->first();
        
        // Check if sentiment declined over time
        $positive = $sentiments->filter(fn($s) => $s === 'positive')->count();
        $negative = $sentiments->filter(fn($s) => $s === 'negative')->count();
        
        if ($lastSentiment === 'negative' && $positive > 0) {
            return 'DECLINED'; // Started positive but ended negative
        }
        elseif ($negative > $positive) {
            return 'MOSTLY_NEGATIVE';
        }
        elseif ($positive > $negative) {
            return 'MOSTLY_POSITIVE';
        }
        
        return 'NEUTRAL';
    }

    /**
     * Detect at which stage customer dropped off
     */
    private function detectDropOffStage($conversations): string
    {
        if ($conversations->isEmpty()) {
            return 'NO_ENGAGEMENT';
        }
        
        $lastStage = $conversations->first()->conversation_state ?? 'INTRO';
        
        return $lastStage;
    }

    /**
     * Get targeted fallback message based on churn reason
     */
    private function getTargetedFallbackMessage(string $name, string $businessName, string $mainProduct, string $churnReason, $conversations = null): string
    {
        // Try to extract specific context from conversations if available
        $specificContext = '';
        if ($conversations && $conversations->count() > 0) {
            $lastMessage = $conversations->first()->customer_message;
            if ($lastMessage && strlen($lastMessage) > 20) {
                $specificContext = " based on our last conversation";
            }
        }
        
        $messages = [
            'PRICE_CONCERN' => "Hi {$name}, this is {$businessName}{$specificContext}. We now have more flexible commercial options{$mainProduct}. If useful, I can share a concise breakdown.",
            'TIMING_ISSUE' => "Hi {$name}, {$businessName} here{$specificContext}. If timing is better now, we can complete initial setup{$mainProduct} quickly with minimal disruption.",
            'COMPETITOR' => "Hi {$name}, this is {$businessName}{$specificContext}. We have introduced improvements{$mainProduct} that may offer stronger value for your team.",
            'FEATURE_GAP' => "Hi {$name}, {$businessName} here{$specificContext}. The features you previously needed{$mainProduct} are now available, and I can share what changed.",
            'COMPLEXITY_CONCERN' => "Hi {$name}, this is {$businessName}{$specificContext}. We have simplified onboarding{$mainProduct} and added guided support to make rollout straightforward.",
        ];
        
        return $messages[$churnReason] ?? "Hi {$name}, {$businessName} here{$mainProduct}. We would value the chance to reconnect{$specificContext}.";
    }
}