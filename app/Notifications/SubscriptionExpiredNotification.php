<?php

namespace App\Notifications;

use App\Models\BillingAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionExpiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected BillingAccount $billingAccount
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = ucfirst($this->billingAccount->subscription_plan ?? 'Unknown');
        $expiredAt = $this->billingAccount->subscription_expires_at
            ? $this->billingAccount->subscription_expires_at->format('F j, Y')
            : 'recently';

        return (new MailMessage)
            ->subject('Your SafariChat Subscription Has Expired')
            ->greeting('Hello!')
            ->line("Your {$plan} Plan subscription expired on {$expiredAt}.")
            ->line('Your AI features and advanced capabilities have been paused.')
            ->action('Renew Subscription', url('/billing'))
            ->line('Renew now to restore full access to all features.');
    }
}
