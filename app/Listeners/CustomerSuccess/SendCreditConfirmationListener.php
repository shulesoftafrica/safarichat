<?php

namespace App\Listeners\CustomerSuccess;

use App\Events\CreditsAdded;
use App\Models\CsMessageLog;
use App\Services\CustomerSuccess\CsMessageRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendCreditConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'cs';
    public int    $tries  = 3;
    public int    $backoff = 60;

    public function __construct(private readonly CsMessageRenderer $renderer) {}

    public function handle(CreditsAdded $event): void
    {
        $user          = $event->user;
        $creditsAdded  = $event->creditsAdded;
        $newBalance    = $event->newBalance;

        // Dedup — one confirmation per credits-added event (1-hour window)
        if (CsMessageLog::alreadySent($user->id, 'credits_added', hours: 1)) {
            return;
        }

        $sent = $this->renderer->send($user, 'credits_added', [
            'credits_added' => number_format($creditsAdded),
            'new_balance'   => number_format($newBalance),
            'dashboard_link' => config('app.url') . '/dashboard',
        ], $user->business_id ?? $user->id);

        if ($sent) {
            Log::info('[CS] Credit confirmation message sent', [
                'user_id'       => $user->id,
                'credits_added' => $creditsAdded,
                'new_balance'   => $newBalance,
            ]);
        }
    }

    public function failed(CreditsAdded $event, \Throwable $exception): void
    {
        Log::error('[CS] SendCreditConfirmationListener failed', [
            'user_id' => $event->user->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
