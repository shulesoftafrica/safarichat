<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Handoff;
use App\Models\AiSalesAgent;
use App\Models\Lead;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SlaMonitorCommand extends Command
{
    protected $signature = 'ai-agent:sla-monitor {--alert-threshold=15} {--escalation-threshold=60} {--check-interval=5}';
    protected $description = 'Monitor handoff response times and SLA compliance';

    public function handle()
    {
        $this->info('⏰ Starting SLA Monitoring');
        $this->newLine();

        $alertThreshold = (int) $this->option('alert-threshold'); // minutes
        $escalationThreshold = (int) $this->option('escalation-threshold'); // minutes
        $checkInterval = (int) $this->option('check-interval'); // minutes

        try {
            // Monitor pending handoffs
            $pendingAlerts = $this->monitorPendingHandoffs($alertThreshold);
            
            // Check for SLA breaches
            $slaBreaches = $this->checkSlaBreaches($escalationThreshold);
            
            // Monitor agent response times
            $agentPerformance = $this->monitorAgentPerformance();
            
            // Check system health
            $systemHealth = $this->checkSystemHealth();
            
            // Generate alerts if needed
            $alertsSent = $this->processAlerts($pendingAlerts, $slaBreaches);

            $this->newLine();
            $this->info('📊 SLA Monitoring Summary:');
            $this->line("  • Pending handoffs monitored: {$pendingAlerts['total']}");
            $this->line("  • SLA breaches detected: {$slaBreaches['count']}");
            $this->line("  • Alerts sent: {$alertsSent}");
            $this->line("  • System health: {$systemHealth['status']}");
            
            if ($slaBreaches['count'] > 0) {
                $this->warn("⚠️ {$slaBreaches['count']} SLA breaches require attention!");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("💥 Fatal error in SLA monitoring: " . $e->getMessage());
            Log::error('SLA monitor fatal error', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    private function monitorPendingHandoffs(int $alertThreshold): array
    {
        $this->info('🔍 Monitoring Pending Handoffs...');

        $pendingHandoffs = Handoff::where('status', Handoff::STATUS_PENDING)
            ->with(['lead', 'conversation'])
            ->get();

        $alerts = [];
        $warningCount = 0;
        $criticalCount = 0;

        foreach ($pendingHandoffs as $handoff) {
            $waitingMinutes = now()->diffInMinutes($handoff->created_at);
            
            if ($waitingMinutes >= $alertThreshold) {
                $severity = $waitingMinutes >= ($alertThreshold * 2) ? 'critical' : 'warning';
                
                $alerts[] = [
                    'handoff_id' => $handoff->id,
                    'lead_name' => $handoff->lead->name,
                    'waiting_minutes' => $waitingMinutes,
                    'severity' => $severity,
                    'reason' => $handoff->handoff_reason
                ];

                if ($severity === 'critical') {
                    $criticalCount++;
                    $this->error("  🚨 CRITICAL: Handoff #{$handoff->id} waiting {$waitingMinutes} min");
                } else {
                    $warningCount++;
                    $this->warn("  ⚠️ WARNING: Handoff #{$handoff->id} waiting {$waitingMinutes} min");
                }
            }
        }

        return [
            'total' => $pendingHandoffs->count(),
            'alerts' => $alerts,
            'warning_count' => $warningCount,
            'critical_count' => $criticalCount
        ];
    }

    private function checkSlaBreaches(int $escalationThreshold): array
    {
        $this->info('🚨 Checking SLA Breaches...');

        $breachedHandoffs = Handoff::where('status', Handoff::STATUS_PENDING)
            ->where('created_at', '<', now()->subMinutes($escalationThreshold))
            ->with(['lead', 'conversation', 'assignedAgent'])
            ->get();

        $breaches = [];

        foreach ($breachedHandoffs as $handoff) {
            $breachMinutes = now()->diffInMinutes($handoff->created_at);
            
            // Auto-escalate if not already escalated
            if (!$handoff->is_escalated) {
                $this->escalateHandoff($handoff);
            }

            $breaches[] = [
                'handoff_id' => $handoff->id,
                'lead_name' => $handoff->lead->name,
                'breach_minutes' => $breachMinutes,
                'assigned_agent' => $handoff->assignedAgent?->name ?? 'Unassigned',
                'priority' => $handoff->priority
            ];

            $this->error("  💥 SLA BREACH: Handoff #{$handoff->id} - {$breachMinutes} minutes overdue");
        }

        return [
            'count' => $breachedHandoffs->count(),
            'breaches' => $breaches
        ];
    }

    private function monitorAgentPerformance(): array
    {
        $this->info('📈 Monitoring Agent Performance...');

        $activeAgents = \App\Models\User::where('role', 'agent')
            ->where('is_active', true)
            ->get();

        $performance = [];

        foreach ($activeAgents as $agent) {
            // Get agent's handoff stats for today
            $todayHandoffs = Handoff::where('assigned_agent_id', $agent->id)
                ->whereDate('created_at', today())
                ->get();

            $avgResponseTime = $this->calculateAverageResponseTime($agent->id);
            $handoffsCompleted = $todayHandoffs->where('status', Handoff::STATUS_COMPLETED)->count();
            $handoffsPending = $todayHandoffs->where('status', Handoff::STATUS_PENDING)->count();

            $performance[] = [
                'agent_id' => $agent->id,
                'agent_name' => $agent->name,
                'avg_response_time' => $avgResponseTime,
                'completed_today' => $handoffsCompleted,
                'pending_today' => $handoffsPending,
                'status' => $this->getAgentStatus($avgResponseTime, $handoffsPending)
            ];

            $status = $this->getAgentStatus($avgResponseTime, $handoffsPending);
            $statusEmoji = $status === 'good' ? '✅' : ($status === 'warning' ? '⚠️' : '🚨');
            
            $this->line("  {$statusEmoji} {$agent->name}: {$avgResponseTime}min avg, {$handoffsCompleted} completed");
        }

        return $performance;
    }

    private function checkSystemHealth(): array
    {
        $this->info('🏥 Checking System Health...');

        $health = [
            'status' => 'good',
            'issues' => []
        ];

        // Check conversation queue backup
        $queuedConversations = Conversation::where('status', Conversation::STATUS_PENDING)
            ->where('created_at', '<', now()->subMinutes(10))
            ->count();

        if ($queuedConversations > 50) {
            $health['issues'][] = "High conversation queue: {$queuedConversations} pending";
            $health['status'] = 'warning';
        }

        // Check AI agent availability
        $inactiveAgents = AiSalesAgent::where('is_active', false)->count();
        $totalAgents = AiSalesAgent::count();
        
        if ($inactiveAgents > ($totalAgents * 0.3)) {
            $health['issues'][] = "Too many inactive AI agents: {$inactiveAgents}/{$totalAgents}";
            $health['status'] = 'critical';
        }

        // Check failed message count
        $failedMessages = \DB::table('failed_jobs')
            ->where('created_at', '>', now()->subHour())
            ->count();

        if ($failedMessages > 10) {
            $health['issues'][] = "High failed message count: {$failedMessages} in last hour";
            $health['status'] = $health['status'] === 'critical' ? 'critical' : 'warning';
        }

        $healthEmoji = $health['status'] === 'good' ? '✅' : 
                      ($health['status'] === 'warning' ? '⚠️' : '🚨');
        
        $this->line("  {$healthEmoji} System Status: {$health['status']}");
        
        foreach ($health['issues'] as $issue) {
            $this->warn("    - {$issue}");
        }

        return $health;
    }

    private function processAlerts(array $pendingAlerts, array $slaBreaches): int
    {
        $alertsSent = 0;

        // Send critical alerts immediately
        if ($pendingAlerts['critical_count'] > 0 || $slaBreaches['count'] > 0) {
            $this->sendCriticalAlert($pendingAlerts, $slaBreaches);
            $alertsSent++;
        }

        // Send summary alerts for warnings
        if ($pendingAlerts['warning_count'] > 0) {
            $this->sendWarningAlert($pendingAlerts);
            $alertsSent++;
        }

        return $alertsSent;
    }

    private function escalateHandoff(Handoff $handoff): void
    {
        $handoff->update([
            'is_escalated' => true,
            'escalated_at' => now(),
            'priority' => min($handoff->priority + 1, 5) // Increase priority
        ]);

        // Log escalation
        Log::warning('Handoff auto-escalated due to SLA breach', [
            'handoff_id' => $handoff->id,
            'lead_id' => $handoff->lead_id,
            'wait_time_minutes' => now()->diffInMinutes($handoff->created_at)
        ]);

        $this->line("    ⬆️ Auto-escalated handoff #{$handoff->id}");
    }

    private function calculateAverageResponseTime(int $agentId): int
    {
        $completedHandoffs = Handoff::where('assigned_agent_id', $agentId)
            ->where('status', Handoff::STATUS_COMPLETED)
            ->whereNotNull('response_time_minutes')
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->get();

        if ($completedHandoffs->isEmpty()) {
            return 0;
        }

        return (int) $completedHandoffs->avg('response_time_minutes');
    }

    private function getAgentStatus(int $avgResponseTime, int $pendingCount): string
    {
        if ($avgResponseTime > 30 || $pendingCount > 5) {
            return 'critical';
        } elseif ($avgResponseTime > 15 || $pendingCount > 2) {
            return 'warning';
        } else {
            return 'good';
        }
    }

    private function sendCriticalAlert(array $pendingAlerts, array $slaBreaches): void
    {
        $subject = 'CRITICAL: SLA Monitoring Alert';
        $message = "Critical SLA alerts detected:\n\n";

        if ($pendingAlerts['critical_count'] > 0) {
            $message .= "Critical Handoffs ({$pendingAlerts['critical_count']}):\n";
            foreach ($pendingAlerts['alerts'] as $alert) {
                if ($alert['severity'] === 'critical') {
                    $message .= "- Handoff #{$alert['handoff_id']}: {$alert['lead_name']} waiting {$alert['waiting_minutes']} min\n";
                }
            }
            $message .= "\n";
        }

        if ($slaBreaches['count'] > 0) {
            $message .= "SLA Breaches ({$slaBreaches['count']}):\n";
            foreach ($slaBreaches['breaches'] as $breach) {
                $message .= "- Handoff #{$breach['handoff_id']}: {$breach['lead_name']} - {$breach['breach_minutes']} min overdue\n";
            }
        }

        Log::critical('SLA Critical Alert', [
            'critical_handoffs' => $pendingAlerts['critical_count'],
            'sla_breaches' => $slaBreaches['count'],
            'message' => $message
        ]);

        // Here you would send actual email/SMS alerts to management
        // Mail::to(config('alerts.management_emails'))->send(new SlaAlert($subject, $message));
    }

    private function sendWarningAlert(array $pendingAlerts): void
    {
        $message = "Warning: {$pendingAlerts['warning_count']} handoffs approaching SLA thresholds";
        
        Log::warning('SLA Warning Alert', [
            'warning_handoffs' => $pendingAlerts['warning_count'],
            'alerts' => $pendingAlerts['alerts']
        ]);
    }
}