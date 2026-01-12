<?php

namespace App\Services;

use App\Models\Handoff;
use App\Models\Lead;
use App\Models\User;
use App\Models\AiSalesAgent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Notify when handoff is created
     */
    public function notifyHandoffCreated(Handoff $handoff, User $agentOwner): void
    {
        try {
            $data = [
                'handoff' => $handoff,
                'lead' => $handoff->lead,
                'agent' => $handoff->aiSalesAgent,
                'priority' => $handoff->priority_level,
                'reason' => $handoff->reason_code,
                'sla_deadline' => $handoff->sla_deadline,
                'context' => $handoff->context_data,
            ];

            // Send email notification
            Mail::send('emails.handoff.created', $data, function ($message) use ($agentOwner, $handoff) {
                $message->to($agentOwner->email)
                    ->subject("AI Sales Agent Escalation - {$handoff->priority_level} priority")
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            // Log notification
            Log::info('Handoff creation notification sent', [
                'handoff_id' => $handoff->id,
                'notified_user' => $agentOwner->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send handoff creation notification', [
                'handoff_id' => $handoff->id,
                'user_id' => $agentOwner->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify when handoff is assigned
     */
    public function notifyHandoffAssigned(Handoff $handoff, User $assignedAgent): void
    {
        try {
            $data = [
                'handoff' => $handoff,
                'lead' => $handoff->lead,
                'agent' => $handoff->aiSalesAgent,
                'assigned_agent' => $assignedAgent,
                'context' => $handoff->context,
            ];

            Mail::send('emails.handoff.assigned', $data, function ($message) use ($assignedAgent, $handoff) {
                $message->to($assignedAgent->email)
                    ->subject("Handoff Assigned: Lead #{$handoff->lead_id}")
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('Handoff assignment notification sent', [
                'handoff_id' => $handoff->id,
                'assigned_to' => $assignedAgent->email,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send handoff assignment notification', [
                'handoff_id' => $handoff->id,
                'user_id' => $assignedAgent->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify when handoff is resolved
     */
    public function notifyHandoffResolved(Handoff $handoff, User $resolvedBy, array $outcome): void
    {
        try {
            $data = [
                'handoff' => $handoff,
                'lead' => $handoff->lead,
                'resolved_by' => $resolvedBy,
                'resolution' => $handoff->resolution,
                'outcome' => $outcome,
                'resolution_time' => $handoff->created_at->diffInHours($handoff->resolved_at),
            ];

            // Notify original agent owner
            if ($handoff->aiSalesAgent && $handoff->aiSalesAgent->user) {
                Mail::send('emails.handoff.resolved', $data, function ($message) use ($handoff) {
                    $message->to($handoff->aiSalesAgent->user->email)
                        ->subject("Handoff Resolved: Lead #{$handoff->lead_id}")
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            }

            Log::info('Handoff resolution notification sent', [
                'handoff_id' => $handoff->id,
                'resolved_by' => $resolvedBy->id,
                'outcome_type' => $outcome['type'] ?? 'unknown',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send handoff resolution notification', [
                'handoff_id' => $handoff->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify fallback person
     */
    public function notifyFallbackPerson(Handoff $handoff, AiSalesAgent $agent): void
    {
        try {
            if (!$agent->fallback_person) {
                return;
            }

            // Try to find user by fallback_person field (could be email or phone)
            $fallbackUser = User::where('email', $agent->fallback_person)
                ->orWhere('phone', $agent->fallback_person)
                ->first();

            $data = [
                'handoff' => $handoff,
                'lead' => $handoff->lead,
                'agent' => $agent,
                'fallback_contact' => $agent->fallback_person,
            ];

            if ($fallbackUser) {
                // Send email to user
                Mail::send('emails.handoff.fallback', $data, function ($message) use ($fallbackUser, $handoff) {
                    $message->to($fallbackUser->email)
                        ->subject("AI Agent Escalation - Action Required")
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            } else {
                // Send to fallback contact directly (if it's an email)
                if (filter_var($agent->fallback_person, FILTER_VALIDATE_EMAIL)) {
                    Mail::send('emails.handoff.fallback', $data, function ($message) use ($agent, $handoff) {
                        $message->to($agent->fallback_person)
                            ->subject("AI Agent Escalation - Action Required")
                            ->from(config('mail.from.address'), config('mail.from.name'));
                    });
                }
            }

            Log::info('Fallback person notification sent', [
                'handoff_id' => $handoff->id,
                'fallback_person' => $agent->fallback_person,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to notify fallback person', [
                'handoff_id' => $handoff->id,
                'fallback_person' => $agent->fallback_person,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Broadcast handoff creation to real-time channels
     */
    public function broadcastHandoffCreated(Handoff $handoff): void
    {
        try {
            // This could integrate with broadcasting systems like Pusher, Redis, etc.
            // For now, we'll just log and could extend with real-time notifications
            
            $broadcastData = [
                'type' => 'handoff_created',
                'handoff_id' => $handoff->id,
                'lead_id' => $handoff->lead_id,
                'priority' => $handoff->priority_level,
                'reason' => $handoff->reason,
                'agent_name' => $handoff->aiSalesAgent->assistant_name,
                'created_at' => $handoff->created_at->toISOString(),
            ];

            // Log for dashboard/monitoring systems
            Log::channel('handoffs')->info('Handoff broadcast', $broadcastData);

            // Here you could add integration with:
            // - WebSocket servers
            // - Real-time notification systems
            // - Dashboard updates
            // - Mobile push notifications

        } catch (\Exception $e) {
            Log::error('Failed to broadcast handoff creation', [
                'handoff_id' => $handoff->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify about overdue handoffs
     */
    public function notifyOverdueHandoffs(array $overdueHandoffs): void
    {
        if (empty($overdueHandoffs)) {
            return;
        }

        try {
            // Group overdue handoffs by user for efficient notification
            $groupedHandoffs = collect($overdueHandoffs)->groupBy(function ($handoff) {
                return $handoff->aiSalesAgent->user_id ?? null;
            });

            foreach ($groupedHandoffs as $userId => $userHandoffs) {
                if (!$userId) continue;

                $user = User::find($userId);
                if (!$user) continue;

                $data = [
                    'user' => $user,
                    'overdue_handoffs' => $userHandoffs,
                    'count' => $userHandoffs->count(),
                ];

                Mail::send('emails.handoff.overdue', $data, function ($message) use ($user, $userHandoffs) {
                    $message->to($user->email)
                        ->subject("Overdue Handoffs Alert - {$userHandoffs->count()} items")
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            }

            Log::info('Overdue handoffs notifications sent', [
                'total_overdue' => count($overdueHandoffs),
                'users_notified' => $groupedHandoffs->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send overdue handoffs notifications', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send daily handoff summary
     */
    public function sendDailyHandoffSummary(User $user): void
    {
        try {
            $yesterday = now()->subDay();
            
            // Get handoffs for user's agents
            $handoffs = Handoff::whereHas('aiSalesAgent', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->whereDate('created_at', $yesterday)
                ->with(['lead', 'aiSalesAgent'])
                ->get();

            if ($handoffs->isEmpty()) {
                return; // No handoffs to report
            }

            $stats = [
                'total' => $handoffs->count(),
                'urgent' => $handoffs->where('priority_level', 'urgent')->count(),
                'high' => $handoffs->where('priority_level', 'high')->count(),
                'resolved' => $handoffs->where('status', 'resolved')->count(),
                'pending' => $handoffs->where('status', 'pending')->count(),
            ];

            $data = [
                'user' => $user,
                'date' => $yesterday->format('Y-m-d'),
                'handoffs' => $handoffs,
                'stats' => $stats,
            ];

            Mail::send('emails.handoff.daily_summary', $data, function ($message) use ($user, $yesterday) {
                $message->to($user->email)
                    ->subject("Daily Handoff Summary - {$yesterday->format('M j, Y')}")
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });

            Log::info('Daily handoff summary sent', [
                'user_id' => $user->id,
                'date' => $yesterday->format('Y-m-d'),
                'total_handoffs' => $stats['total'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send daily handoff summary', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send system alerts for high failure rates or issues
     */
    public function sendSystemAlert(string $alertType, array $data): void
    {
        try {
            // Get admin users - using a different approach since roles() is not an Eloquent relationship
            $adminUsers = User::where('is_active', true)
                ->where(function ($query) {
                    // Get business owners (they act as admins for their businesses)
                    $query->whereHas('business', function ($q) {
                        $q->whereNotNull('user_id');
                    })
                    // Or get users with admin permissions (can be expanded later)
                    ->orWhere('user_type_id', 1) // Assuming user_type_id 1 is admin
                    ->orWhere('email', 'like', '%admin%'); // Basic admin check
                })
                ->get();

            foreach ($adminUsers as $admin) {
                $emailData = array_merge($data, [
                    'admin' => $admin,
                    'alert_type' => $alertType,
                    'timestamp' => now(),
                ]);

                Mail::send('emails.system.alert', $emailData, function ($message) use ($admin, $alertType) {
                    $message->to($admin->email)
                        ->subject("System Alert: {$alertType}")
                        ->from(config('mail.from.address'), config('mail.from.name'));
                });
            }

            Log::warning('System alert sent', [
                'alert_type' => $alertType,
                'data' => $data,
                'admins_notified' => $adminUsers->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send system alert', [
                'alert_type' => $alertType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get notification preferences for user
     */
    public function getNotificationPreferences(User $user): array
    {
        // This could be expanded to include user-specific notification preferences
        return [
            'handoff_created' => true,
            'handoff_assigned' => true,
            'handoff_resolved' => true,
            'overdue_alerts' => true,
            'daily_summary' => true,
            'system_alerts' => $user->hasRole('admin'),
            'channels' => ['email'], // Could include 'sms', 'slack', 'webhook', etc.
        ];
    }
}