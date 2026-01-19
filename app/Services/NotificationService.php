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
     * Send WhatsApp message
     */
    private function sendWhatsAppMessage(string $phone, string $message): void
    {
        try {
            // Use the same logic as in Setup controller
            $controller = new \App\Http\Controllers\Setup();
            $controller->sendTextMessage($phone, $message, 'whatsapp', 'notification');
            
            Log::info('WhatsApp notification sent', [
                'phone' => $phone,
                'message_length' => strlen($message)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send WhatsApp notification', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify when handoff is created
     */
    public function notifyHandoffCreated(Handoff $handoff, User $agentOwner): void
    {
        try {
            $message = "🚨 *New Customer Escalation Created*\n\n";
            $message .= "📋 Lead ID: #{$handoff->lead_id}\n";
            $message .= "👤 Customer: {$handoff->lead->contact->guest_name}\n";
            $message .= "📞 Phone: {$handoff->lead->contact->guest_phone}\n";
            $message .= "🔥 Priority: " . ucfirst($handoff->priority_level) . "\n";
            $message .= "📝 Reason: {$handoff->reason_code}\n\n";
            $message .= "Please check your dashboard to handle this escalation.";

            $this->sendWhatsAppMessage($agentOwner->phone, $message);

            // Log notification
            Log::info('Handoff creation notification sent', [
                'handoff_id' => $handoff->id,
                'notified_user' => $agentOwner->phone,
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
            $message = "✅ *Customer Escalation Assigned to You*\n\n";
            $message .= "📋 Lead ID: #{$handoff->lead_id}\n";
            $message .= "👤 Customer: {$handoff->lead->contact->guest_name}\n";
            $message .= "📞 Phone: {$handoff->lead->contact->guest_phone}\n";
            $message .= "🔥 Priority: {$handoff->priority_level}\n";
            $message .= "📝 Reason: {$handoff->reason_code}\n";
            $message .= "⏰ SLA Deadline: {$handoff->sla_deadline->format('M d, Y H:i')}\n\n";
            $message .= "Please handle this escalation promptly. Check your dashboard for full details.";

            $this->sendWhatsAppMessage($assignedAgent->phone, $message);

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
            if ($handoff->lead->aiSalesAgent && $handoff->lead->aiSalesAgent->user) {
                $message = "✅ *Customer Escalation Resolved*\n\n";
                $message .= "📋 Lead ID: #{$handoff->lead_id}\n";
                $message .= "👤 Customer: {$handoff->lead->contact->guest_name}\n";
                $message .= "👨‍💼 Resolved by: {$resolvedBy->name}\n";
                $message .= "📝 Resolution: {$handoff->resolution_notes}\n";
                if (isset($outcome['customer_satisfaction'])) {
                    $message .= "⭐ Satisfaction: {$outcome['customer_satisfaction']}/5\n";
                }
                $message .= "\nGreat work on handling this escalation!";

                $this->sendWhatsAppMessage($handoff->lead->aiSalesAgent->user->phone, $message);
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
                // Send WhatsApp message to user
                $message = "🔄 *Fallback Customer Escalation Assigned*\n\n";
                $message .= "📋 Lead ID: #{$handoff->lead_id}\n";
                $message .= "👤 Customer: {$handoff->lead->contact->guest_name}\n";
                $message .= "🔥 Priority: " . ucfirst($handoff->priority_level) . "\n";
                $message .= "⚠️ This escalation was auto-assigned as a fallback.\n\n";
                $message .= "Please handle this escalation promptly.";

                $this->sendWhatsAppMessage($fallbackUser->phone, $message);
            } else {
                // Send to fallback contact directly (if it's a phone number)
                if (preg_match('/^\+?[1-9]\d{1,14}$/', $agent->fallback_person)) {
                    $message = "🚨 *URGENT: Admin Fallback Escalation*\n\n";
                    $message .= "📋 Handoff ID: #{$handoff->id}\n";
                    $message .= "👤 Customer: {$handoff->lead->contact->guest_name}\n";
                    $message .= "🔥 Priority: " . ucfirst($handoff->priority_level) . "\n";
                    $message .= "⚠️ No agents available - admin intervention required!\n\n";
                    $message .= "Please assign this escalation manually.";

                    $this->sendWhatsAppMessage($agent->fallback_person, $message);
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

                $message = "🚨 *OVERDUE Customer Escalations*\n\n";
                $message .= "You have {$userHandoffs->count()} overdue escalation(s):\n\n";
                foreach ($userHandoffs as $handoff) {
                    $message .= "📋 Lead #{$handoff->lead_id} - {$handoff->lead->contact->guest_name}\n";
                    $message .= "⏰ Due: {$handoff->sla_deadline->diffForHumans()}\n";
                    $message .= "🔥 Priority: " . ucfirst($handoff->priority_level) . "\n\n";
                }
                $message .= "⚠️ Please handle these escalations immediately!";

                $this->sendWhatsAppMessage($user->phone, $message);
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

            $message = "📊 *Daily Escalation Summary - {$yesterday->format('M d, Y')}*\n\n";
            $message .= "📥 New: {$data['stats']['new_handoffs']}\n";
            $message .= "✅ Resolved: {$data['stats']['resolved_handoffs']}\n";
            $message .= "⏳ Pending: {$data['stats']['pending_handoffs']}\n";
            if ($data['stats']['overdue_handoffs'] > 0) {
                $message .= "🚨 Overdue: {$data['stats']['overdue_handoffs']}\n";
            }
            $message .= "\nKeep up the great work!";

            $this->sendWhatsAppMessage($user->phone, $message);

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
                $emailData = [
                    'admin' => $admin,
                    'alert_type' => $alertType,
                    'timestamp' => now(),
                    'alert_data' => $data, // Keep original data structure for the view
                ];

                // Also merge the data at top level for backward compatibility
                $emailData = array_merge($emailData, $data);

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