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
 * Sends the T-3h trial ending warning (§3.5.1 of csdesign.md).
 * Dispatched by SendCsTrialMonitorCommand every 15 minutes for users whose
 * trial_ends_at falls within the next 3 hours.
 */
class TrialEndingWarningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'cs';
    public int    $tries = 2;

    public function __construct(public readonly int $userId) {}

    public function handle(CsMessageRenderer $renderer): void
    {
        $user = User::find($this->userId);

        if (!$user || $user->subscription_status !== 'trial' || !$user->trial_ends_at) {
            return;
        }

        // Re-check condition: trial must end within the next 3.5 hours
        // (extra 30 min buffer for queue delay)
        $endsIn = now()->diffInMinutes($user->trial_ends_at, false);

        if ($endsIn <= 0 || $endsIn > 210) { // 3.5 h = 210 min
            return;
        }

        // Hard dedup: send this warning ONCE per user, ever
        if (CsMessageLog::everSent($user->id, 'trial_warning_3h')) {
            return;
        }

        $sent = $renderer->send($user, 'trial_warning_3h', [
            'billing_link' => config('app.url') . '/billing',
        ], $user->business?->id ?? 0);

        if ($sent) {
            Log::info('TrialEndingWarningJob: T-3h warning sent', ['user_id' => $user->id]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('TrialEndingWarningJob failed', ['user_id' => $this->userId, 'error' => $e->getMessage()]);
    }
}
