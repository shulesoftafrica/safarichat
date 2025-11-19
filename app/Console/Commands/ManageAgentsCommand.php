<?php

namespace App\Console\Commands;

use App\Models\AiSalesAgent;
use App\Models\Lead;
use App\Models\Conversation;
use App\Models\Handoff;
use App\Services\OpenAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ManageAgentsCommand extends Command
{
    protected $signature = 'ai:manage-agents
                            {--generate-descriptions : Generate AI descriptions for products}
                            {--update-lead-scores : Recalculate lead scores}
                            {--cleanup-old-conversations : Clean up old conversation data}
                            {--agent-health-check : Check agent configuration health}';

    protected $description = 'Manage AI sales agents and perform maintenance tasks';

    private $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        parent::__construct();
        $this->openAiService = $openAiService;
    }

    public function handle()
    {
        $this->info('AI Sales Agent Management');
        $this->newLine();

        if ($this->option('generate-descriptions')) {
            $this->generateProductDescriptions();
        }

        if ($this->option('update-lead-scores')) {
            $this->updateLeadScores();
        }

        if ($this->option('cleanup-old-conversations')) {
            $this->cleanupOldConversations();
        }

        if ($this->option('agent-health-check')) {
            $this->performAgentHealthCheck();
        }

        // If no specific options, show menu
        if (!$this->option('generate-descriptions') && 
            !$this->option('update-lead-scores') && 
            !$this->option('cleanup-old-conversations') && 
            !$this->option('agent-health-check')) {
            $this->showAgentOverview();
        }

        return 0;
    }

    /**
     * Generate AI descriptions for products without them
     */
    private function generateProductDescriptions()
    {
        $this->info('Generating AI product descriptions...');

        $products = \App\Models\Product::whereNull('ai_description')
            ->orWhere('ai_description', '')
            ->active()
            ->get();

        if ($products->isEmpty()) {
            $this->info('All products already have AI descriptions.');
            return;
        }

        $generated = 0;
        $errors = 0;

        foreach ($products as $product) {
            try {
                $this->line("Generating description for: {$product->name}");

                $aiDescription = $this->openAiService->generateProductDescription($product);

                if ($aiDescription) {
                    $product->update(['ai_description' => $aiDescription]);
                    $this->info("  ✓ Generated description");
                    $generated++;
                } else {
                    $this->warn("  ⚠ Failed to generate description");
                    $errors++;
                }

                // Rate limiting
                sleep(1);

            } catch (\Exception $e) {
                $this->error("  ✗ Error: " . $e->getMessage());
                $errors++;
            }
        }

        $this->newLine();
        $this->info("Product description generation complete:");
        $this->info("  Generated: {$generated}");
        $this->info("  Errors: {$errors}");
    }

    /**
     * Recalculate lead scores
     */
    private function updateLeadScores()
    {
        $this->info('Updating lead scores...');

        $leads = Lead::whereNotNull('phone_number')->get();
        $updated = 0;

        foreach ($leads as $lead) {
            $oldScore = $lead->lead_score;
            $newScore = $lead->calculateLeadScore();
            
            if ($oldScore !== $newScore) {
                $lead->update(['lead_score' => $newScore]);
                $this->line("Lead {$lead->id}: {$oldScore} → {$newScore}");
                $updated++;
            }
        }

        $this->info("Updated {$updated} lead scores out of {$leads->count()} total leads.");
    }

    /**
     * Clean up old conversation data
     */
    private function cleanupOldConversations()
    {
        $this->info('Cleaning up old conversations...');

        $cutoffDate = now()->subDays(30); // Keep 30 days of data
        
        // Clean up old conversations
        $deletedConversations = Conversation::where('created_at', '<', $cutoffDate)
            ->where('state', '!=', 'important') // Keep important conversations
            ->delete();

        // Clean up very old handoffs that are resolved
        $deletedHandoffs = Handoff::where('created_at', '<', $cutoffDate)
            ->where('status', 'resolved')
            ->delete();

        $this->info("Cleanup complete:");
        $this->info("  Deleted conversations: {$deletedConversations}");
        $this->info("  Deleted handoffs: {$deletedHandoffs}");
    }

    /**
     * Perform health check on AI agents
     */
    private function performAgentHealthCheck()
    {
        $this->info('Performing AI agent health check...');
        $this->newLine();

        $agents = AiSalesAgent::with('user')->get();
        $healthy = 0;
        $warnings = 0;
        $errors = 0;

        foreach ($agents as $agent) {
            $issues = $this->checkAgentHealth($agent);
            
            if (empty($issues)) {
                $this->info("✓ {$agent->assistant_name} (User: {$agent->user->name}) - Healthy");
                $healthy++;
            } else {
                $hasErrors = false;
                foreach ($issues as $issue) {
                    if ($issue['severity'] === 'error') {
                        $this->error("✗ {$agent->assistant_name}: {$issue['message']}");
                        $hasErrors = true;
                    } else {
                        $this->warn("⚠ {$agent->assistant_name}: {$issue['message']}");
                    }
                }
                
                if ($hasErrors) {
                    $errors++;
                } else {
                    $warnings++;
                }
            }
        }

        $this->newLine();
        $this->info("Health check summary:");
        $this->info("  Healthy agents: {$healthy}");
        $this->info("  Agents with warnings: {$warnings}");
        $this->info("  Agents with errors: {$errors}");
    }

    /**
     * Check individual agent health
     */
    private function checkAgentHealth(AiSalesAgent $agent): array
    {
        $issues = [];

        // Check basic configuration
        if (!$agent->assistant_name) {
            $issues[] = ['severity' => 'error', 'message' => 'Missing assistant name'];
        }

        if ($agent->status !== 'active') {
            $issues[] = ['severity' => 'warning', 'message' => 'Agent is not active'];
        }

        // Check personality configuration
        if (!$agent->personality_description) {
            $issues[] = ['severity' => 'warning', 'message' => 'Missing personality description'];
        }

        if (!$agent->communication_tone) {
            $issues[] = ['severity' => 'warning', 'message' => 'Missing communication tone'];
        }

        // Check working hours configuration
        if (!$agent->always_available) {
            if (!$agent->business_days || !$agent->start_time || !$agent->end_time) {
                $issues[] = ['severity' => 'error', 'message' => 'Incomplete business hours configuration'];
            }
        }

        // Check escalation configuration
        if (!$agent->fallback_person && !$agent->fallback_number) {
            $issues[] = ['severity' => 'warning', 'message' => 'No escalation contact configured'];
        }

        // Check negotiation settings
        if ($agent->allow_negotiation) {
            if ($agent->max_discount_allowed > 50) {
                $issues[] = ['severity' => 'warning', 'message' => 'High discount threshold (>50%)'];
            }
            
            if ($agent->accept_installments && !$agent->min_down_payment) {
                $issues[] = ['severity' => 'warning', 'message' => 'Installments enabled but no minimum down payment'];
            }
        }

        // Check recent activity
        $recentLeads = Lead::where('ai_sales_agent_id', $agent->id)
            ->where('last_activity_at', '>', now()->subDays(7))
            ->count();

        if ($agent->status === 'active' && $recentLeads === 0) {
            $issues[] = ['severity' => 'warning', 'message' => 'No recent activity (7 days)'];
        }

        return $issues;
    }

    /**
     * Show agent overview
     */
    private function showAgentOverview()
    {
        $this->info('AI Sales Agent Overview');
        $this->newLine();

        $agents = AiSalesAgent::withCount(['leads'])
            ->with('user')
            ->get();

        if ($agents->isEmpty()) {
            $this->warn('No AI sales agents configured.');
            return;
        }

        $table = [];
        foreach ($agents as $agent) {
            $table[] = [
                'ID' => $agent->id,
                'Name' => $agent->assistant_name,
                'User' => $agent->user->name,
                'Status' => $agent->status,
                'Leads' => $agent->leads_count,
                'Available' => $agent->isAvailableNow() ? 'Yes' : 'No',
                'Negotiation' => $agent->allow_negotiation ? "Yes ({$agent->max_discount_allowed}%)" : 'No',
            ];
        }

        $this->table([
            'ID', 'Name', 'User', 'Status', 'Leads', 'Available', 'Negotiation'
        ], $table);

        // Show recent activity stats
        $this->newLine();
        $this->showActivityStats();
    }

    /**
     * Show activity statistics
     */
    private function showActivityStats()
    {
        $this->info('Recent Activity (Last 24 hours):');

        // Lead creation stats
        $newLeads = Lead::where('created_at', '>', now()->subDay())->count();
        $this->line("New leads: {$newLeads}");

        // Conversation stats
        $conversations = Conversation::where('created_at', '>', now()->subDay())->count();
        $this->line("Conversations: {$conversations}");

        // Handoff stats
        $handoffs = Handoff::where('created_at', '>', now()->subDay())->count();
        $this->line("Handoffs created: {$handoffs}");

        // Processing stats
        $processedMessages = \App\Models\IncomingMessage::where('created_at', '>', now()->subDay())
            ->where('status', 'replied')
            ->count();
        $this->line("Messages processed: {$processedMessages}");

        // Success rate
        $totalMessages = \App\Models\IncomingMessage::where('created_at', '>', now()->subDay())->count();
        if ($totalMessages > 0) {
            $successRate = round(($processedMessages / $totalMessages) * 100, 1);
            $this->line("Success rate: {$successRate}%");
        }
    }
}