<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessContact;
use App\Models\Lead;
use Illuminate\Support\Facades\Log;

class UpdateContactPrioritiesCommand extends Command
{
    protected $signature = 'contacts:update-priorities {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Update contact priority levels based on lead scores and other factors';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }
        
        $this->info('Updating contact priorities...');
        
        $contacts = BusinessContact::with('leads')->get();
        $updated = 0;
        
        foreach ($contacts as $contact) {
            $oldPriority = $contact->priority_level;
            $newPriority = $this->calculateContactPriority($contact);
            
            if ($oldPriority !== $newPriority) {
                $this->line("Contact {$contact->id} ({$contact->guest_name}): Priority {$oldPriority} → {$newPriority}");
                
                if (!$dryRun) {
                    $contact->update(['priority_level' => $newPriority]);
                }
                $updated++;
            }
        }
        
        if ($dryRun) {
            $this->info("Would update {$updated} contact priorities out of {$contacts->count()} total contacts.");
        } else {
            $this->info("Updated {$updated} contact priorities out of {$contacts->count()} total contacts.");
        }
    }
    
    private function calculateContactPriority(BusinessContact $contact): int
    {
        $baseScore = 50; // Start with neutral score
        
        // Factor in lead scores
        $leadScores = $contact->leads->pluck('lead_score');
        if ($leadScores->isNotEmpty()) {
            $avgLeadScore = $leadScores->avg();
            $maxLeadScore = $leadScores->max();
            
            // Use the better of average or max lead score
            $leadScore = max($avgLeadScore, $maxLeadScore);
            $baseScore += ($leadScore / 100) * 30; // Up to 30 points from lead scores
        }
        
        // Higher priority for contacts with email
        if (!empty($contact->guest_email)) {
            $baseScore += 15;
        }
        
        // Higher priority for contacts with complete names
        if (!empty($contact->guest_name) && 
            !str_starts_with($contact->guest_name, 'Contact_') && 
            $contact->guest_name !== 'Unknown') {
            $baseScore += 10;
        }
        
        // Higher priority for contacts already contacted for sales (engaged)
        if ($contact->contacted_for_sales) {
            $baseScore += 20;
        }
        
        // Higher priority for recent interactions
        if ($contact->last_ai_interaction && $contact->last_ai_interaction->gt(now()->subDays(7))) {
            $baseScore += 15;
        }
        
        if ($contact->last_human_interaction && $contact->last_human_interaction->gt(now()->subDays(3))) {
            $baseScore += 20;
        }
        
        // Factor in handoff status
        switch ($contact->handoff_status) {
            case 'requested':
            case 'pending':
                $baseScore += 25; // Needs immediate attention
                break;
            case 'handed_off':
                $baseScore += 15; // Under human review
                break;
        }
        
        // Factor in lead statuses
        $activeLeads = $contact->leads()->whereNotIn('status', [
            Lead::STATUS_CLOSED, Lead::STATUS_LOST, Lead::STATUS_DO_NOT_CONTACT
        ])->get();
        
        foreach ($activeLeads as $lead) {
            switch ($lead->status) {
                case Lead::STATUS_QUALIFIED:
                case Lead::STATUS_PITCHED:
                case Lead::STATUS_DEMO_SCHEDULED:
                    $baseScore += 20;
                    break;
                case Lead::STATUS_PROPOSAL_SENT:
                case Lead::STATUS_NEGOTIATING:
                    $baseScore += 30;
                    break;
                case Lead::STATUS_REPLIED:
                case Lead::STATUS_ENGAGED:
                    $baseScore += 10;
                    break;
            }
        }
        
        // Convert score to priority level (1=High, 2=Medium, 3=Normal, 4=Low)
        if ($baseScore >= 85) {
            return 1; // High priority (Urgent)
        } elseif ($baseScore >= 70) {
            return 2; // Medium priority (High)  
        } elseif ($baseScore >= 45) {
            return 3; // Normal priority
        } else {
            return 4; // Low priority
        }
    }
}