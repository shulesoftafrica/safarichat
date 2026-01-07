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
        string $reasonCode,
        string $priority = 'medium',
        array $context = []
    ): Handoff {
        try {
            DB::beginTransaction();

            $handoff = $lead->handoffs()->create([
                'reason_code' => $reasonCode,
                'priority_level' => $priority,
                'status' => Handoff::STATUS_PENDING,
                'ai_summary' => $this->generateAiSummary($lead, $agent, $reasonCode),
                'context_data' => array_merge($context, [
                    'lead_score' => $lead->calculateLeadScore(),
                    'agent_name' => $agent->assistant_name,
                    'interested_products' => $lead->leadProducts()->with('product')->get()->pluck('product.name'),
                    'recent_conversations' => $lead->conversations()->latest()->limit(3)->get()->pluck('ai_response'),
                    'escalation_trigger' => $reasonCode,
                    'agent_config' => [
                        'max_discount' => $agent->max_discount_allowed,
                        'negotiation_enabled' => $agent->allow_negotiation,
                        'fallback_person' => $agent->fallback_person,
                    ],
                ]),
                'estimated_resolution_time' => $this->calculateEstimatedResolutionTime($priority, $reasonCode),
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
                'reason_code' => $reasonCode,
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
            ->orderBy('priority_level')
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
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN sla_deadline < NOW() AND status != 'resolved' THEN 1 ELSE 0 END) as overdue,
                AVG(CASE WHEN status = 'resolved' THEN EXTRACT(EPOCH FROM (resolved_at - created_at))/3600 ELSE NULL END) as avg_resolution_hours
            ")
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
            ->orderBy('priority_level')
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
     * Generate AI summary for handoff
     */
    private function generateAiSummary(Lead $lead, AiSalesAgent $agent, string $reasonCode): string
    {
        $summary = "Customer escalation requested via {$agent->assistant_name}. ";
        
        $summary .= match($reasonCode) {
            Handoff::REASON_COMPLEX_QUESTION => "Customer has complex technical questions requiring human expertise.",
            Handoff::REASON_COMPLAINT => "Customer has expressed dissatisfaction and needs immediate attention.",
            Handoff::REASON_LARGE_ORDER => "Customer interested in large order requiring approval and negotiation.",
            Handoff::REASON_PAYMENT_ISSUE => "Customer experiencing payment processing difficulties.",
            Handoff::REASON_ANGRY_CUSTOMER => "Customer is frustrated/angry and needs immediate human intervention.",
            Handoff::REASON_AI_ERROR => "AI system encountered error or limitation in handling customer query.",
            Handoff::REASON_LOW_STOCK => "Customer inquiry relates to low stock items requiring immediate action.",
            Handoff::REASON_CUSTOMER_REQUEST => "Customer specifically requested to speak with human agent.",
            default => "General escalation requiring human attention."
        };

        if ($lead->name) {
            $summary .= " Customer: {$lead->name}";
        }
        
        $summary .= " Phone: {$lead->phone_number}";
        
        return $summary;
    }

    /**
     * Calculate estimated resolution time based on priority and reason
     */
    private function calculateEstimatedResolutionTime(string $priority, string $reasonCode): int
    {
        // Base time in minutes
        $baseTime = match($priority) {
            Handoff::PRIORITY_URGENT => 30,
            Handoff::PRIORITY_HIGH => 120,
            Handoff::PRIORITY_MEDIUM => 240,
            Handoff::PRIORITY_LOW => 480,
            default => 240
        };

        // Adjust based on reason complexity
        $multiplier = match($reasonCode) {
            Handoff::REASON_COMPLEX_QUESTION => 1.5,
            Handoff::REASON_LARGE_ORDER => 2.0,
            Handoff::REASON_PAYMENT_ISSUE => 1.2,
            Handoff::REASON_AI_ERROR => 0.8,
            default => 1.0
        };

        return (int)($baseTime * $multiplier);
    }

    /**
     * Determine escalation type based on reason (kept for backward compatibility)
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
     * Calculate SLA deadline based on priority (kept for backward compatibility)
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