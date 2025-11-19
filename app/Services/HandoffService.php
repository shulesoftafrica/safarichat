<?php

namespace App\Services;

use App\Models\Handoff;
use App\Models\Lead;
use App\Models\AiSalesAgent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class HandoffService
{
    private $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create a new handoff/escalation
     */
    public function createHandoff(
        Lead $lead,
        AiSalesAgent $agent,
        string $reason,
        string $priority = 'medium',
        array $context = []
    ): Handoff {
        try {
            DB::beginTransaction();

            $handoff = $lead->handoffs()->create([
                'ai_sales_agent_id' => $agent->id,
                'reason' => $reason,
                'priority' => $priority,
                'status' => 'pending',
                'escalation_type' => $this->determineEscalationType($reason),
                'context' => array_merge($context, [
                    'lead_score' => $lead->calculateLeadScore(),
                    'agent_name' => $agent->assistant_name,
                    'interested_products' => $lead->leadProducts()->with('product')->get()->pluck('product.name'),
                    'recent_conversations' => $lead->conversations()->latest()->limit(3)->get()->pluck('summary'),
                ]),
                'sla_deadline' => $this->calculateSlaDeadline($priority),
                'metadata' => [
                    'created_by' => 'ai_agent',
                    'agent_config' => [
                        'max_discount' => $agent->max_discount_allowed,
                        'negotiation_enabled' => $agent->allow_negotiation,
                        'fallback_person' => $agent->fallback_person,
                    ],
                ],
            ]);

            // Update lead status
            $lead->update(['status' => Lead::STATUS_NEEDS_ATTENTION]);

            // Send notifications
            $this->notifyStakeholders($handoff, $agent);

            DB::commit();

            Log::info('Handoff created successfully', [
                'handoff_id' => $handoff->id,
                'lead_id' => $lead->id,
                'agent_id' => $agent->id,
                'reason' => $reason,
                'priority' => $priority,
            ]);

            return $handoff;

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Failed to create handoff', [
                'lead_id' => $lead->id,
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Assign handoff to a human agent
     */
    public function assignToHuman(Handoff $handoff, User $humanAgent, ?string $notes = null): bool
    {
        try {
            $handoff->update([
                'assigned_to' => $humanAgent->id,
                'status' => 'assigned',
                'assigned_at' => now(),
                'assignment_notes' => $notes,
            ]);

            // Notify the assigned agent
            $this->notificationService->notifyHandoffAssigned($handoff, $humanAgent);

            // Update lead assignment
            $handoff->lead->update(['assigned_human_agent' => $humanAgent->id]);

            Log::info('Handoff assigned to human agent', [
                'handoff_id' => $handoff->id,
                'human_agent_id' => $humanAgent->id,
                'lead_id' => $handoff->lead_id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to assign handoff', [
                'handoff_id' => $handoff->id,
                'human_agent_id' => $humanAgent->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Mark handoff as resolved
     */
    public function resolveHandoff(
        Handoff $handoff,
        User $resolvedBy,
        string $resolution,
        array $outcome = []
    ): bool {
        try {
            $handoff->update([
                'status' => 'resolved',
                'resolved_by' => $resolvedBy->id,
                'resolved_at' => now(),
                'resolution' => $resolution,
                'outcome' => $outcome,
            ]);

            // Update lead status based on outcome
            $this->updateLeadStatusFromResolution($handoff, $outcome);

            // Notify stakeholders of resolution
            $this->notificationService->notifyHandoffResolved($handoff, $resolvedBy, $outcome);

            Log::info('Handoff resolved', [
                'handoff_id' => $handoff->id,
                'resolved_by' => $resolvedBy->id,
                'resolution' => $resolution,
                'outcome' => $outcome,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to resolve handoff', [
                'handoff_id' => $handoff->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get overdue handoffs
     */
    public function getOverdueHandoffs(): \Illuminate\Database\Eloquent\Collection
    {
        return Handoff::where('status', '!=', 'resolved')
            ->where('sla_deadline', '<', now())
            ->with(['lead', 'aiSalesAgent', 'assignedUser'])
            ->orderBy('priority')
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get handoff statistics
     */
    public function getHandoffStats(int $days = 30): array
    {
        $since = now()->subDays($days);

        $stats = Handoff::where('created_at', '>=', $since)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "resolved" THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = "assigned" THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN sla_deadline < NOW() AND status != "resolved" THEN 1 ELSE 0 END) as overdue,
                AVG(CASE WHEN status = "resolved" THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) ELSE NULL END) as avg_resolution_hours
            ')
            ->first();

        return [
            'period_days' => $days,
            'total_handoffs' => $stats->total ?? 0,
            'resolved' => $stats->resolved ?? 0,
            'pending' => $stats->pending ?? 0,
            'assigned' => $stats->assigned ?? 0,
            'overdue' => $stats->overdue ?? 0,
            'resolution_rate' => $stats->total > 0 ? round(($stats->resolved / $stats->total) * 100, 1) : 0,
            'avg_resolution_hours' => round($stats->avg_resolution_hours ?? 0, 1),
        ];
    }

    /**
     * Auto-assign handoffs based on availability and expertise
     */
    public function autoAssignHandoffs(): int
    {
        $pendingHandoffs = Handoff::where('status', 'pending')
            ->where('created_at', '>', now()->subHours(24)) // Don't auto-assign very old handoffs
            ->orderBy('priority')
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        $assigned = 0;

        foreach ($pendingHandoffs as $handoff) {
            $availableAgent = $this->findBestAvailableAgent($handoff);
            
            if ($availableAgent && $this->assignToHuman($handoff, $availableAgent, 'Auto-assigned by system')) {
                $assigned++;
            }
        }

        if ($assigned > 0) {
            Log::info("Auto-assigned {$assigned} handoffs");
        }

        return $assigned;
    }

    /**
     * Determine escalation type based on reason
     */
    private function determineEscalationType(string $reason): string
    {
        $reason = strtolower($reason);

        if (str_contains($reason, 'complaint') || str_contains($reason, 'angry')) {
            return 'complaint';
        }

        if (str_contains($reason, 'large order') || str_contains($reason, 'bulk')) {
            return 'large_order';
        }

        if (str_contains($reason, 'technical') || str_contains($reason, 'product issue')) {
            return 'technical_support';
        }

        if (str_contains($reason, 'pricing') || str_contains($reason, 'negotiation')) {
            return 'pricing_negotiation';
        }

        return 'general';
    }

    /**
     * Calculate SLA deadline based on priority
     */
    private function calculateSlaDeadline(string $priority): \Carbon\Carbon
    {
        $hours = match($priority) {
            'urgent' => config('ai_sales_agent.escalation.escalation_sla_hours', 4) / 4, // 1 hour
            'high' => config('ai_sales_agent.escalation.escalation_sla_hours', 4) / 2, // 2 hours
            'medium' => config('ai_sales_agent.escalation.escalation_sla_hours', 4), // 4 hours
            'low' => config('ai_sales_agent.escalation.escalation_sla_hours', 4) * 2, // 8 hours
            default => config('ai_sales_agent.escalation.escalation_sla_hours', 4),
        };

        return now()->addHours($hours);
    }

    /**
     * Notify stakeholders about new handoff
     */
    private function notifyStakeholders(Handoff $handoff, AiSalesAgent $agent): void
    {
        // Notify agent owner
        $this->notificationService->notifyHandoffCreated($handoff, $agent->user);

        // Notify fallback person if specified
        if ($agent->fallback_person) {
            $this->notificationService->notifyFallbackPerson($handoff, $agent);
        }

        // Send to notification queue/channels
        $this->notificationService->broadcastHandoffCreated($handoff);
    }

    /**
     * Update lead status based on resolution outcome
     */
    private function updateLeadStatusFromResolution(Handoff $handoff, array $outcome): void
    {
        $outcomeType = $outcome['type'] ?? 'unknown';

        switch ($outcomeType) {
            case 'sale_completed':
                $handoff->lead->update(['status' => Lead::STATUS_CONVERTED]);
                break;

            case 'issue_resolved':
                $handoff->lead->update(['status' => Lead::STATUS_ENGAGED]);
                break;

            case 'customer_lost':
                $handoff->lead->update(['status' => Lead::STATUS_CHURNED]);
                break;

            default:
                $handoff->lead->update(['status' => Lead::STATUS_ENGAGED]);
        }
    }

    /**
     * Find best available agent for handoff assignment
     */
    private function findBestAvailableAgent(Handoff $handoff): ?User
    {
        // This is a simplified version - you can expand with more sophisticated logic
        $availableAgents = User::whereHas('roles', function ($query) {
                $query->where('name', 'sales_agent');
            })
            ->where('is_active', true)
            ->withCount(['assignedHandoffs' => function ($query) {
                $query->where('status', 'assigned');
            }])
            ->orderBy('assigned_handoffs_count')
            ->limit(5)
            ->get();

        // Return agent with lowest current workload
        return $availableAgents->first();
    }

    /**
     * Get handoff trends for reporting
     */
    public function getHandoffTrends(int $days = 30): array
    {
        $trends = DB::table('handoffs')
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN priority = "urgent" THEN 1 ELSE 0 END) as urgent,
                SUM(CASE WHEN priority = "high" THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN escalation_type = "complaint" THEN 1 ELSE 0 END) as complaints,
                AVG(CASE WHEN status = "resolved" THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) ELSE NULL END) as avg_resolution_time
            ')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return $trends;
    }
}