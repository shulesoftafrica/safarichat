<?php

namespace App\Jobs\CustomerSuccess;

use App\Models\CsMessageLog;
use App\Models\User;
use App\Services\CustomerSuccess\CsMessageRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends the trial-expired message (§3.5.2 of csdesign.md).
 * Dispatched by SendCsTrialMonitorCommand every 15 minutes for users whose
 * trial_ends_at has passed but subscription_status is still 'trial'.
 */
class TrialExpiredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public readonly int $userId)
    {
        $this->onQueue('cs');
    }

    public function handle(CsMessageRenderer $renderer): void
    {
        $user = User::find($this->userId);

        if (!$user || $user->subscription_status !== 'trial') {
            return;
        }

        // Re-check: trial must actually be expired by now
        if (!$user->trial_ends_at || $user->trial_ends_at->isFuture()) {
            return;
        }

        // Hard dedup: this message is fired exactly ONCE per user
        if (CsMessageLog::everSent($user->id, 'trial_expired')) {
            return;
        }

        $sent = $renderer->send($user, 'trial_expired', [
            'billing_link' => config('app.url') . '/billing',
        ], $user->business?->id ?? 0);

        if ($sent) {
            Log::info('TrialExpiredJob: expiry message sent', ['user_id' => $user->id]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('TrialExpiredJob failed', ['user_id' => $this->userId, 'error' => $e->getMessage()]);
    }
}
