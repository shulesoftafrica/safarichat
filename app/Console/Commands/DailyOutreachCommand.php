<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Models\AiSalesAgent;
use App\Models\EventsGuest;
use App\Models\Product;
use App\Services\AiWhatsAppService;
use App\Services\OpenAiService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DailyOutreachCommand extends Command
{
    protected $signature = 'ai-agent:daily-outreach {--limit=50} {--agent=} {--dry-run}';
    protected $description = 'Execute daily lead outreach campaigns for AI sales agents';

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
        $this->info('🚀 Starting Daily Outreach Campaign');
        $this->newLine();

        $limit = (int) $this->option('limit');
        $agentId = $this->option('agent');
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
                
                // Check if agent is available for outreach
                if (!$agent->isAvailableNow()) {
                    $this->warn("⏰ Agent {$agent->name} is outside business hours - skipping");
                    continue;
                }

                // Get leads ready for outreach for this agent
                $leads = $this->getLeadsForOutreach($agent, $limit);
                
                $this->info("📋 Found {$leads->count()} leads for outreach");

                if ($leads->isEmpty()) {
                    $this->warn("📭 No leads ready for outreach");
                    continue;
                }

                foreach ($leads as $lead) {
                    try {
                        $sent = $this->processLeadOutreach($lead, $agent, $dryRun);
                        if ($sent) {
                            $totalSent++;
                            $this->line("  ✅ Sent to: {$lead->name} ({$lead->contact->guest_phone})");
                        } else {
                            $this->error("  ❌ Failed to send to: {$lead->name}");
                        }
                        
                        // Add small delay to avoid overwhelming the API
                        if (!$dryRun) {
                            sleep(2);
                        }

                    } catch (\Exception $e) {
                        $this->error("  💥 Error processing {$lead->name}: " . $e->getMessage());
                        Log::error('Daily outreach error', [
                            'lead_id' => $lead->id,
                            'agent_id' => $agent->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                $this->newLine();
            }

            $this->info("🎉 Daily outreach completed!");
            $this->info("📊 Total messages sent: {$totalSent}");
            
            return 0;

        } catch (\Exception $e) {
            $this->error("💥 Fatal error in daily outreach: " . $e->getMessage());
            Log::error('Daily outreach fatal error', ['error' => $e->getMessage()]);
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

    private function getLeadsForOutreach(AiSalesAgent $agent, int $limit)
    {
        return Lead::where('ai_sales_agent_id', $agent->id)
            ->whereIn('status', [Lead::STATUS_NEW, Lead::STATUS_OUTREACHED])
            ->whereNotIn('status', [Lead::STATUS_DO_NOT_CONTACT, Lead::STATUS_CLOSED])
            ->where(function($query) {
                $query->whereNull('last_contact_at')
                    ->orWhere('last_contact_at', '<', now()->subDays(1));
            })
            ->where('lead_score', '>', 0)
            ->orderByDesc('lead_score')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();
    }

    private function processLeadOutreach(Lead $lead, AiSalesAgent $agent, bool $dryRun): bool
    {
        try {
            // Get lead's contact information
            $contact = $lead->contact;
            if (!$contact) {
                Log::warning('Lead has no contact information', ['lead_id' => $lead->id]);
                return false;
            }

            // Generate personalized outreach message
            $message = $this->generateOutreachMessage($lead, $agent, $contact);
            
            if ($dryRun) {
                $this->line("📝 Would send: " . substr($message, 0, 100) . '...');
                return true;
            }

            // Send message via AI WhatsApp service
            $result = $this->aiWhatsAppService->sendOutreachMessage($lead, $message, $agent);

            if ($result['success']) {
                // Update lead status
                $lead->update([
                    'status' => Lead::STATUS_OUTREACHED,
                    'last_contact_at' => now(),
                    'last_interaction_at' => now()
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Outreach processing error', [
                'lead_id' => $lead->id,
                'agent_id' => $agent->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function generateOutreachMessage(Lead $lead, AiSalesAgent $agent, $contact): string
    {
        try {
            // Get products associated with this lead
            $products = $lead->products()->take(3)->get();
            $primaryProduct = $products->first();

            // Build context for AI
            $context = [
                'lead_name' => $lead->name,
                'company_name' => $lead->company_name,
                'industry' => $lead->industry,
                'lead_score' => $lead->lead_score,
                'primary_product' => $primaryProduct ? $primaryProduct->name : null,
                'agent_personality' => $agent->personality_type,
                'business_context' => $contact->event ? $contact->event->name : null
            ];

            // Generate personalized message using AI
            $prompt = $this->buildOutreachPrompt($context, $agent);
            $response = $this->openAiService->generateResponse($lead, null, null, 'INTRO');

            return $response['message_text'] ?? $this->getFallbackMessage($lead, $agent);

        } catch (\Exception $e) {
            Log::error('Message generation error', [
                'lead_id' => $lead->id,
                'error' => $e->getMessage()
            ]);
            
            return $this->getFallbackMessage($lead, $agent);
        }
    }

    private function buildOutreachPrompt(array $context, AiSalesAgent $agent): string
    {
        $personality = match($agent->personality_type) {
            'professional' => 'professional and business-focused',
            'friendly' => 'warm, friendly, and approachable',
            'consultative' => 'helpful and advisory',
            'direct' => 'clear, concise, and straightforward',
            default => 'professional and helpful'
        };

        return "Generate a personalized outreach message with a {$personality} tone. " .
               "Lead: {$context['lead_name']} from {$context['company_name']} in {$context['industry']}. " .
               "Primary interest: {$context['primary_product']}. " .
               "Lead score: {$context['lead_score']}/100. " .
               "Keep it under 160 characters for WhatsApp.";
    }

    private function getFallbackMessage(Lead $lead, AiSalesAgent $agent): string
    {
        $name = $lead->name ?? 'there';
        $company = $lead->company_name ? " at {$lead->company_name}" : '';
        
        return "Hi {$name}! I hope this message finds you well{$company}. " .
               "I'd love to share how we can help your business grow. " .
               "Would you be interested in a quick conversation?";
    }
}