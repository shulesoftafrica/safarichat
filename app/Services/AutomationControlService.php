<?php

namespace App\Services;

use App\Models\User;
use App\Models\MissedAutomation;
use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AutomationControlService
{
    /**
     * Check if user can execute automations
     */
    public function canExecuteAutomation(User $user): bool
    {
        // Subscription check moved to new billing system
        return true; // Default to allow during migration
    }

    /**
     * Log missed automation when subscription is inactive
     */
    public function logMissedAutomation(User $user, string $type, array $data): MissedAutomation
    {
        return MissedAutomation::create([
            'user_id' => $user->id,
            'lead_id' => $data['lead_id'] ?? null,
            'automation_type' => $type,
            'scheduled_at' => $data['scheduled_at'] ?? now(),
            'missed_reason' => 'subscription_inactive',
            'target_data' => [
                'customer_name' => $data['customer_name'] ?? 'Unknown',
                'customer_phone' => $data['customer_phone'] ?? null,
                'product_name' => $data['product_name'] ?? null,
                'message_content' => $data['message_content'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'original_data' => $data
            ],
            'potential_value' => $data['potential_value'] ?? 0
        ]);
    }

    /**
     * Get missed automations for today
     */
    public function getMissedAutomationsToday(User $user): Collection
    {
        return $user->missedAutomations()
            ->whereDate('created_at', today())
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Get missed automations for a date range
     */
    public function getMissedAutomations(User $user, Carbon $from, Carbon $to): Collection
    {
        return $user->missedAutomations()
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('scheduled_at')
            ->get();
    }

    /**
     * Resume pending automations when subscription reactivates
     */
    public function resumePendingAutomations(User $user): int
    {
        $pendingAutomations = $user->missedAutomations()
            ->whereNull('recovered_at')
            ->where('scheduled_at', '>', now()->subDays(3)) // Only recent automations
            ->get();

        $resumedCount = 0;

        foreach ($pendingAutomations as $automation) {
            try {
                $success = $this->executeRecoveredAutomation($automation);
                
                if ($success) {
                    $automation->update(['recovered_at' => now()]);
                    $resumedCount++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to resume automation', [
                    'automation_id' => $automation->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $resumedCount;
    }

    /**
     * Calculate potential missed revenue
     */
    public function calculateMissedRevenue(User $user, Carbon $since): float
    {
        return $user->missedAutomations()
            ->where('created_at', '>=', $since)
            ->sum('potential_value');
    }

    /**
     * Get automation statistics for dashboard
     */
    public function getAutomationStats(User $user, int $days = 30): array
    {
        $since = now()->subDays($days);
        
        $missedAutomations = $user->missedAutomations()
            ->where('created_at', '>=', $since)
            ->get();

        $groupedByType = $missedAutomations->groupBy('automation_type');
        
        return [
            'total_missed' => $missedAutomations->count(),
            'missed_by_type' => $groupedByType->map->count()->toArray(),
            'potential_revenue_lost' => $missedAutomations->sum('potential_value'),
            'unique_customers_affected' => $missedAutomations->unique('lead_id')->count(),
            'recent_missed' => $missedAutomations->where('created_at', '>=', now()->subDays(7))->count(),
            'recovered_automations' => $missedAutomations->whereNotNull('recovered_at')->count()
        ];
    }

    /**
     * Check if automation should be executed or logged as missed
     */
    public function processAutomation(User $user, string $type, array $automationData): bool
    {
        if ($this->canExecuteAutomation($user)) {
            // Execute the automation normally
            return $this->executeAutomation($type, $automationData);
        } else {
            // Log as missed automation
            $this->logMissedAutomation($user, $type, $automationData);
            
            // Send immediate alert for high-priority automations
            if ($this->isHighPriorityAutomation($type, $automationData)) {
                $this->sendImmediateMissedAlert($user, $type, $automationData);
            }
            
            return false;
        }
    }

    /**
     * Execute a recovered automation
     */
    private function executeRecoveredAutomation(MissedAutomation $automation): bool
    {
        $type = $automation->automation_type;
        $data = $automation->target_data;

        switch ($type) {
            case 'followup':
                return $this->executeFollowup($data);
            case 'qualification':
                return $this->executeQualification($data);
            case 'reminder':
                return $this->executeReminder($data);
            case 'cart_recovery':
                return $this->executeCartRecovery($data);
            case 'welcome_sequence':
                return $this->executeWelcomeSequence($data);
            default:
                return false;
        }
    }

    /**
     * Execute automation based on type
     */
    private function executeAutomation(string $type, array $data): bool
    {
        switch ($type) {
            case 'followup':
                return $this->executeFollowup($data);
            case 'qualification':
                return $this->executeQualification($data);
            case 'reminder':
                return $this->executeReminder($data);
            case 'cart_recovery':
                return $this->executeCartRecovery($data);
            case 'welcome_sequence':
                return $this->executeWelcomeSequence($data);
            default:
                return false;
        }
    }

    /**
     * Execute follow-up automation
     */
    private function executeFollowup(array $data): bool
    {
        // Delegate to existing follow-up service
        $aiService = app(AiWhatsAppService::class);
        return $aiService->sendFollowUpMessage($data);
    }

    /**
     * Execute qualification automation
     */
    private function executeQualification(array $data): bool
    {
        // Implement qualification logic
        return true;
    }

    /**
     * Execute reminder automation
     */
    private function executeReminder(array $data): bool
    {
        // Implement reminder logic
        return true;
    }

    /**
     * Execute cart recovery automation
     */
    private function executeCartRecovery(array $data): bool
    {
        // Implement cart recovery logic
        return true;
    }

    /**
     * Execute welcome sequence automation
     */
    private function executeWelcomeSequence(array $data): bool
    {
        // Implement welcome sequence logic
        return true;
    }

    /**
     * Check if automation is high priority
     */
    private function isHighPriorityAutomation(string $type, array $data): bool
    {
        $highPriorityTypes = ['cart_recovery', 'qualification'];
        
        return in_array($type, $highPriorityTypes) || 
               ($data['priority'] ?? 'medium') === 'high' ||
               ($data['potential_value'] ?? 0) > 50; // High value threshold
    }

    /**
     * Send immediate alert for missed high-priority automation
     */
    private function sendImmediateMissedAlert(User $user, string $type, array $data): void
    {
        $customerName = $data['customer_name'] ?? 'Unknown customer';
        
        $message = "⚠️ HIGH PRIORITY MISSED!\n\n";
        $message .= "Automation Type: " . ucfirst($type) . "\n";
        $message .= "Customer: {$customerName}\n";
        $message .= "Your subscription is inactive.\n\n";
        $message .= "Reactivate now to avoid losing this opportunity!";

        app(SubscriptionNotificationService::class)->sendMissedOpportunityAlert($user, [$data]);
    }

    /**
     * Get automation recovery suggestions
     */
    public function getRecoverySuggestions(User $user): array
    {
        $recentMissed = $this->getMissedAutomations($user, now()->subDays(3), now());
        
        $suggestions = [];
        
        foreach ($recentMissed as $missed) {
            $suggestions[] = [
                'type' => $missed->automation_type,
                'customer' => $missed->target_data['customer_name'] ?? 'Unknown',
                'scheduled' => $missed->scheduled_at->diffForHumans(),
                'priority' => $this->getAutomationPriority($missed),
                'can_recover' => $this->canRecoverAutomation($missed),
                'recovery_action' => $this->getRecoveryAction($missed)
            ];
        }
        
        return collect($suggestions)
            ->sortByDesc('priority')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * Get automation priority score
     */
    private function getAutomationPriority(MissedAutomation $missed): int
    {
        $baseScore = [
            'cart_recovery' => 10,
            'qualification' => 8,
            'followup' => 6,
            'reminder' => 4,
            'welcome_sequence' => 2
        ][$missed->automation_type] ?? 1;

        // Boost score for high value or recent automations
        if ($missed->potential_value > 100) $baseScore += 3;
        if ($missed->scheduled_at > now()->subHours(24)) $baseScore += 2;

        return $baseScore;
    }

    /**
     * Check if automation can still be recovered
     */
    private function canRecoverAutomation(MissedAutomation $missed): bool
    {
        // Don't recover very old automations
        return $missed->scheduled_at > now()->subDays(7);
    }

    /**
     * Get suggested recovery action
     */
    private function getRecoveryAction(MissedAutomation $missed): string
    {
        $actions = [
            'cart_recovery' => 'Send abandoned cart reminder',
            'qualification' => 'Ask qualifying questions',
            'followup' => 'Send follow-up message',
            'reminder' => 'Send payment reminder',
            'welcome_sequence' => 'Send welcome message'
        ];

        return $actions[$missed->automation_type] ?? 'Manual follow-up recommended';
    }
}