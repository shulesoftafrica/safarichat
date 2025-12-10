<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationQueue;
use Carbon\Carbon;
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
     * Send daily summary for inactive users
     */
    public function sendDailySummary(User $user): void
    {
        $yesterday = now()->subDay();
        
        // Get missed automations from yesterday
        $missedAutomations = $user->missedAutomations()
            ->whereDate('created_at', $yesterday)
            ->get()
            ->groupBy('automation_type');

        if ($missedAutomations->isEmpty()) {
            return;
        }

        $message = "📊 Daily Report – Missed Automations (Subscription Inactive)\n\n";
        $message .= "Yesterday SafariChat could not perform:\n";

        $typeLabels = [
            'followup' => 'customer follow-up messages',
            'qualification' => 'qualifying question sessions',
            'reminder' => 'order reminders',
            'cart_recovery' => 'cart recovery attempts',
            'welcome_sequence' => 'welcome sequences'
        ];

        foreach ($missedAutomations as $type => $automations) {
            $count = $automations->count();
            $label = $typeLabels[$type] ?? $type;
            $message .= "• {$count} {$label}\n";
        }

        $totalCustomers = $missedAutomations->flatten()->unique('lead_id')->count();
        $message .= "\nTotal customers at risk: {$totalCustomers}\n\n";
        $message .= "Reactivate now to avoid losing future customers.";

        NotificationQueue::create([
            'user_id' => $user->id,
            'notification_type' => 'whatsapp',
            'category' => 'daily_summary',
            'priority' => 'medium',
            'recipient' => $user->whatsapp_number ?? $user->phone,
            'message' => $message,
            'scheduled_for' => now()->hour(8), // Send at 8 AM
            'template_data' => [
                'date' => $yesterday->toDateString(),
                'missed_count' => $missedAutomations->flatten()->count(),
                'customers_at_risk' => $totalCustomers
            ]
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
            return $whatsappService->sendMessage($number, $message);
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