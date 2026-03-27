<?php

namespace App\Listeners\CustomerSuccess;

use App\Events\WhatsappInstanceConnected;
use App\Models\CsMessageLog;
use App\Services\CustomerSuccess\CsMessageRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendWelcomeMessageListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'cs';
    public int $tries   = 3;
    public int $backoff  = 60; // seconds between retries

    public function __construct(private readonly CsMessageRenderer $renderer) {}

    public function handle(WhatsappInstanceConnected $event): void
    {
        $instance = $event->instance;
        $user     = $instance->user;

        if (!$user) {
            Log::warning('SendWelcomeMessageListener: instance has no user', [
                'instance_id' => $instance->id,
            ]);
            return;
        }

        // Guard: only send the welcome message once per user, ever
        if ($user->cs_welcome_sent_at) {
            Log::info('SendWelcomeMessageListener: welcome already sent, skipping', [
                'user_id'     => $user->id,
                'sent_at'     => $user->cs_welcome_sent_at,
            ]);
            return;
        }

        // Also check the log table as a secondary guard
        if (CsMessageLog::everSent($user->id, 'welcome')) {
            $user->updateQuietly(['cs_welcome_sent_at' => now()]);
            return;
        }

        $businessName  = $user->business?->name ?? $user->name ?? 'there';
        $dashboardLink = config('app.url') . '/dashboard';
        $userPhone     = $user->phone ?? $instance->phone_number;

        $sent = $this->renderer->send($user, 'welcome', [
            'business_name'  => $businessName,
            'dashboard_link' => $dashboardLink,
            'your_number'    => $userPhone,
        ], $user->business_id ?? 0);

        if ($sent) {
            $user->updateQuietly(['cs_welcome_sent_at' => now()]);
            Log::info('SendWelcomeMessageListener: welcome message dispatched', [
                'user_id' => $user->id,
            ]);
        }
    }

    public function failed(WhatsappInstanceConnected $event, \Throwable $exception): void
    {
        Log::error('SendWelcomeMessageListener: job failed after retries', [
            'instance_id' => $event->instance->id,
            'error'       => $exception->getMessage(),
        ]);
    }
}
