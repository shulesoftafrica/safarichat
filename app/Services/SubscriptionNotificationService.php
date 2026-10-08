<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SubscriptionNotificationService
{
    /**
     * Schedule expiry warnings for a user
     */
    public function scheduleExpiryWarnings(User $user, Carbon $expiryDate): void
    {
        $warnings = [
            7 => ['message' => 'Your subscription expires in 7 days. Renew now to avoid interruption.', 'priority' => 'medium'],
            3 => ['message' => 'Your subscription expires in 3 days. Don\'t let your sales stop!', 'priority' => 'high'],
            1 => ['message' => 'Your subscription expires tomorrow. Renew immediately!', 'priority' => 'urgent']
        ];

        foreach ($warnings as $days => $config) {
            $scheduledFor = $expiryDate->copy()->subDays($days);
            
            if ($scheduledFor > now()) {
                NotificationQueue::updateOrCreate([
                    'user_id' => $user->id,
                    'category' => 'expiry_warning',
                    'scheduled_for' => $scheduledFor
                ], [
                    'notification_type' => 'whatsapp',
                    'priority' => $config['priority'],
                    'recipient' => $user->whatsapp_number ?? $user->phone,
                    'message' => $config['message'],
                    'status' => 'pending'
                ]);
            }
        }
    }

    /**
     * Send payment confirmation notification
     */
    public function sendPaymentConfirmation(User $user, $payment): void
    {
        $message = "Payment confirmed! Your SafariChat subscription is now active. Amount: {$payment->amount} TSH. Thank you!";
        
        NotificationQueue::create([
            'user_id' => $user->id,
            'notification_type' => 'whatsapp',
            'category' => 'payment_success',
            'priority' => 'high',
            'recipient' => $user->whatsapp_number ?? $user->phone,
            'message' => $message,
            'scheduled_for' => now(),
            'template_data' => [
                'payment_id' => $payment->id,
                'amount' => $payment->amount
            ]
        ]);
    }

    /**
     * Send missed opportunity alert
     */
    public function sendMissedOpportunityAlert(User $user, array $opportunities): void
    {
        $customerNames = collect($opportunities)->pluck('customer_name')->unique()->take(3);
        $totalCount = count($opportunities);
        
        $message = "🚨 Missed Opportunity Alert!\n\n";
        
        if ($totalCount === 1) {
            $message .= "A customer named {$customerNames->first()} wants to purchase something but SafariChat could not assist because your subscription is inactive.";
        } else {
            $names = $customerNames->join(', ', ' and ');
            $message .= "{$totalCount} customers including {$names} tried to contact you but SafariChat could not assist because your subscription is inactive.";
        }
        
        $message .= "\n\nReactivate now to avoid losing more customers!";

        NotificationQueue::create([
            'user_id' => $user->id,
            'notification_type' => 'whatsapp',
            'category' => 'missed_opportunity',
            'priority' => 'urgent',
            'recipient' => $user->whatsapp_number ?? $user->phone,
            'message' => $message,
            'scheduled_for' => now(),
            'template_data' => [
                'opportunities' => $opportunities,
                'total_count' => $totalCount
            ]
        ]);
    }

    /**
     * Send the daily summary to a business owner.
     *
     * WhatsApp is the primary channel (it reaches owners who have no email, and almost none had one:
     * the old email-only version delivered nothing). Email is sent as well when the user has an address.
     * Returns what really happened so the caller can report it truthfully; before, the command printed
     * "Sent" even when this method had returned early without sending anything.
     *
     * @return array{whatsapp:bool,email:bool,delivered:bool,reason:?string}
     */
    public function sendDailySummary(User $user, array $stats = []): array
    {
        $yesterday = now()->subDay();
        $businessName = $user->business->name ?? 'Your Business';

        $missedCount = $this->missedAutomationsCount($user, $yesterday);

        $whatsapp = false;
        $reason = null;

        if (config('notifications.daily_summary.whatsapp', true)) {
            $phone = $this->summaryRecipient($user);

            if (!$phone) {
                $reason = 'no_phone';
            } elseif (!Cache::add($this->summaryCacheKey($user, $yesterday), 1, now()->addDay())) {
                // already delivered for this date (command re-run): not an error, but nothing new sent
                $reason = 'already_sent_today';
                $whatsapp = true;
            } else {
                $whatsapp = $this->sendSummaryOnWhatsApp(
                    $phone,
                    $this->buildDailySummaryWhatsAppMessage($user, $stats, $businessName, $yesterday, $missedCount)
                );

                if (!$whatsapp) {
                    // let a later run retry today instead of being blocked by our own dedupe key
                    Cache::forget($this->summaryCacheKey($user, $yesterday));
                    $reason = 'whatsapp_send_failed';
                }
            }
        } else {
            $reason = 'whatsapp_disabled';
        }

        $email = $this->sendDailySummaryEmail($user, $stats);

        // Critical issues must still reach the owner even if the full summary could not be delivered.
        if (!$whatsapp && (($stats['overdue_handoffs'] ?? 0) > 0 || ($stats['failed_messages'] ?? 0) > 10)) {
            $this->sendWhatsAppSummary($user, $stats);
        }

        if (!$whatsapp && !$email) {
            Log::warning('Daily summary was not delivered', ['user_id' => $user->id, 'reason' => $reason ?? 'no_email']);
        }

        return ['whatsapp' => $whatsapp, 'email' => $email, 'delivered' => $whatsapp || $email, 'reason' => $reason];
    }

    /**
     * The text of the WhatsApp summary (pure: no I/O, so it can be tested).
     */
    public function buildDailySummaryWhatsAppMessage(User $user, array $stats, string $businessName, Carbon $date, int $missedAutomations = 0): string
    {
        $failed = (int) ($stats['failed_messages'] ?? 0);
        $overdue = (int) ($stats['overdue_handoffs'] ?? 0);
        $name = trim((string) ($user->name ?? ''));
        $first = $name !== '' ? explode(' ', $name)[0] : null;

        $lines = [];
        $lines[] = '📊 *Daily summary — ' . $businessName . '*';
        $lines[] = $date->format('l, j M Y') . ($first ? " · Hi {$first}" : '');
        $lines[] = '';
        $lines[] = '💬 Messages sent: *' . (int) ($stats['total_messages'] ?? 0) . '*'
            . ' (' . (int) ($stats['successful_messages'] ?? 0) . ' delivered, ' . $failed . ' failed)';
        $lines[] = '↩️ Replies received: *' . (int) ($stats['replies_received'] ?? 0) . '*';
        $lines[] = '🤖 AI conversations: *' . (int) ($stats['ai_conversations'] ?? 0) . '*';
        $lines[] = '🆕 New leads: *' . (int) ($stats['new_leads'] ?? 0) . '*';
        $lines[] = '🙋 Customer handoffs: *' . (int) ($stats['new_handoffs'] ?? 0) . '*';

        if (($stats['total_messages'] ?? 0) > 0) {
            $lines[] = '✅ Delivery rate: *' . ($stats['success_rate'] ?? 0) . '%*';
        }

        $attention = [];
        if ($overdue > 0) {
            $attention[] = "⚠️ {$overdue} customer escalation(s) are overdue";
        }
        if ($failed > 10) {
            $attention[] = "📱 High failure rate: {$failed} messages failed";
        }
        if ($missedAutomations > 0) {
            $attention[] = "⏸️ {$missedAutomations} automation(s) were missed";
        }
        if ($attention) {
            $lines[] = '';
            $lines[] = '*Needs your attention*';
            $lines = array_merge($lines, $attention);
        }

        $lines[] = '';
        $lines[] = 'Details: ' . rtrim((string) config('app.url'), '/') . '/dashboard';

        return implode("\n", $lines);
    }

    /** Automations that did not run for this user on $date (an optional extra line; never blocks the summary). */
    protected function missedAutomationsCount(User $user, Carbon $date): int
    {
        try {
            return (int) $user->missedAutomations()->whereDate('created_at', $date)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Where the owner wants WhatsApp messages: their WhatsApp number, else their phone. */
    private function summaryRecipient(User $user): ?string
    {
        $number = trim((string) ($user->whatsapp_number ?: $user->phone));

        return $number !== '' ? $number : null;
    }

    private function summaryCacheKey(User $user, Carbon $date): string
    {
        return 'daily_summary_whatsapp:' . $user->id . ':' . $date->toDateString();
    }

    /**
     * Send through the platform's own (system) WhatsApp line, the same sender the reconnect alerts use,
     * so it works whether or not the owner's own WhatsApp is connected.
     */
    protected function sendSummaryOnWhatsApp(string $phone, string $message): bool
    {
        try {
            return (bool) app(SystemWhatsAppService::class)->sendGenericMessage($phone, $message, 'system_notification');
        } catch (\Throwable $e) {
            Log::error('Daily summary WhatsApp send failed', ['phone' => $phone, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Email version of the summary. Returns true only if a message was handed to the mailer.
     */
    private function sendDailySummaryEmail(User $user, array $stats): bool
    {
        // Users without an email address simply get no email (WhatsApp covers them).
        if (empty($user->email)) {
            return false;
        }

        $yesterday = now()->subDay();
        $businessName = $user->business->name ?? 'Your Business';

        // Get missed automations for inactive subscriptions
        $missedAutomations = $user->missedAutomations()
            ->whereDate('created_at', $yesterday)
            ->get()
            ->groupBy('automation_type');
            
        $hasSubscriptionIssues = $missedAutomations->isNotEmpty();
        $totalCustomersAtRisk = 0;
        
        if ($hasSubscriptionIssues) {
            $totalCustomersAtRisk = $missedAutomations->flatten()->unique('target_data.customer_id')->count();
        }
        
        // Send email summary
        $sent = false;
        try {
            \Illuminate\Support\Facades\Mail::send('emails.daily-summary', [
                'user' => $user,
                'businessName' => $businessName,
                'date' => $yesterday->format('M j, Y'),
                'stats' => $stats,
                'hasSubscriptionIssues' => $hasSubscriptionIssues,
                'missedAutomations' => $missedAutomations->map->count(),
                'totalCustomersAtRisk' => $totalCustomersAtRisk
            ], function ($message) use ($user, $businessName, $yesterday) {
                $message->to($user->email)
                    ->subject("📊 Daily Summary - {$businessName} ({$yesterday->format('M j')})")
                    ->from(config('mail.from.address'), config('mail.from.name'));
            });
            $sent = true;
            
            \Illuminate\Support\Facades\Log::info('Daily summary email sent', [
                'user_id' => $user->id,
                'business' => $businessName,
                'has_issues' => $stats['has_issues'] ?? false
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send daily summary email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return $sent;
    }
    
    /**
     * Send critical WhatsApp summary
     */
    private function sendWhatsAppSummary(User $user, array $stats): void
    {
        if (!$user->whatsapp_number && !$user->phone) {
            return;
        }
        
        $businessName = $user->business->name ?? 'Your Business';
        $message = "🚨 *{$businessName} - Critical Alert*\n\n";
        
        if (($stats['overdue_handoffs'] ?? 0) > 0) {
            $message .= "⚠️ {$stats['overdue_handoffs']} customer escalations are overdue!\n";
        }

        if (($stats['failed_messages'] ?? 0) > 10) {
            $message .= "📱 High message failure rate: {$stats['failed_messages']} failed\n";
        }
        
        $message .= "\nCheck your dashboard for details.";
        
        NotificationQueue::create([
            'user_id' => $user->id,
            'notification_type' => 'whatsapp',
            'category' => 'critical_alert',
            'priority' => 'urgent',
            'recipient' => $user->whatsapp_number ?? $user->phone,
            'message' => $message,
            'scheduled_for' => now(),
        ]);
    }

    /**
     * Process notification queue and send pending notifications
     */
    public function processNotificationQueue(): int
    {
        $pendingNotifications = NotificationQueue::where('status', 'pending')
            ->where('scheduled_for', '<=', now())
            ->where('retry_count', '<', function($query) {
                $query->select('max_retries');
            })
            ->orderBy('priority')
            ->orderBy('scheduled_for')
            ->limit(100)
            ->get();

        $sentCount = 0;

        foreach ($pendingNotifications as $notification) {
            try {
                $success = $this->sendNotification($notification);
                
                if ($success) {
                    $notification->update([
                        'status' => 'sent',
                        'sent_at' => now()
                    ]);
                    $sentCount++;
                } else {
                    $this->handleNotificationFailure($notification, 'Send failed');
                }
            } catch (\Exception $e) {
                $this->handleNotificationFailure($notification, $e->getMessage());
            }
        }

        return $sentCount;
    }

    /**
     * Send WhatsApp notification
     */
    public function sendWhatsApp(string $number, string $message): bool
    {
        try {
            // Use existing WhatsApp service
            $whatsappService = app(WaSenderService::class);
            $result = $whatsappService->sendMessage($number, $message);

            // sendMessage() returns an array, but this method is declared `: bool`; returning the array
            // threw a TypeError, so nothing queued here was ever delivered.
            return is_array($result) ? (($result['success'] ?? false) === true) : (bool) $result;
        } catch (\Exception $e) {
            Log::error('WhatsApp send failed: ' . $e->getMessage(), [
                'number' => $number,
                'message' => $message
            ]);
            return false;
        }
    }

    /**
     * Send individual notification
     */
    private function sendNotification(NotificationQueue $notification): bool
    {
        switch ($notification->notification_type) {
            case 'whatsapp':
                return $this->sendWhatsApp($notification->recipient, $notification->message);
            case 'email':
                return $this->sendEmail($notification->recipient, $notification->subject, $notification->message);
            case 'dashboard':
                return true; // Dashboard notifications are stored, not sent
            default:
                return false;
        }
    }

    /**
     * Send email notification
     */
    private function sendEmail(string $email, string $subject, string $message): bool
    {
        try {
            Mail::raw($message, function ($mail) use ($email, $subject) {
                $mail->to($email)->subject($subject);
            });
            return true;
        } catch (\Exception $e) {
            Log::error('Email send failed: ' . $e->getMessage(), [
                'email' => $email,
                'subject' => $subject
            ]);
            return false;
        }
    }

    /**
     * Handle notification failure
     */
    private function handleNotificationFailure(NotificationQueue $notification, string $reason): void
    {
        $notification->increment('retry_count');
        $notification->update([
            'failure_reason' => $reason,
            'status' => $notification->retry_count >= $notification->max_retries ? 'failed' : 'pending',
            'scheduled_for' => $notification->retry_count < $notification->max_retries 
                ? now()->addMinutes(5 * $notification->retry_count) 
                : $notification->scheduled_for
        ]);
    }

    /**
     * Schedule final warning notification
     */
    public function scheduleFinalWarning(User $user): void
    {
        $daysInactive = now()->diffInDays($user->updated_at);
        
        if ($daysInactive >= 2) {
            $message = "🚨 FINAL WARNING 🚨\n\n";
            $message .= "Your pipeline is freezing!\n";
            $message .= "SafariChat cannot continue nurturing your customers.\n\n";
            $message .= "Why this matters:\n";
            $message .= "• Multiple customers pending follow-up\n";
            $message .= "• Active leads waiting for responses\n\n";
            $message .= "Your next sales cycle will be affected.\n\n";
            $message .= "Reactivate now to avoid losing momentum.";

            NotificationQueue::create([
                'user_id' => $user->id,
                'notification_type' => 'whatsapp',
                'category' => 'final_warning',
                'priority' => 'urgent',
                'recipient' => $user->whatsapp_number ?? $user->phone,
                'message' => $message,
                'scheduled_for' => now()
            ]);
        }
    }
}