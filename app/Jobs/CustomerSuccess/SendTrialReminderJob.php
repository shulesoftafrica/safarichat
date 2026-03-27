<?php

namespace App\Jobs\CustomerSuccess;

use App\Models\CsMessageLog;
use App\Models\User;
use App\Services\CustomerSuccess\CsMessageRenderer;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends the daily trial countdown reminder (§3.4 of csdesign.md).
 * Dispatched each morning at 09:00 for every active trial user.
 */
class SendTrialReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $queue = 'cs';
    public int    $tries = 2;

    public function __construct(public readonly int $userId) {}

    public function handle(CsMessageRenderer $renderer): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            return;
        }

        // Guard: only send to active trial users
        if ($user->subscription_status !== 'trial' || !$user->trial_ends_at) {
            return;
        }

        // Guard: trial must still be in the future
        if ($user->trial_ends_at->isPast()) {
            return;
        }

        // Guard: deduplicate — only once per calendar day
        if (CsMessageLog::alreadySent($user->id, 'trial_reminder', hours: 20)) {
            return;
        }

        $daysLeft = (int) now()->diffInDays($user->trial_ends_at, false);
        $daysLeft = max(0, $daysLeft);

        $sent = $renderer->send($user, 'trial_reminder', [
            'days_left'    => $daysLeft,
            'trial_ends'   => $user->trial_ends_at->format('d M Y'),
            'billing_link' => config('app.url') . '/billing',
        ], $user->business?->id ?? 0);

        if ($sent) {
            Log::info('SendTrialReminderJob: sent', [
                'user_id'  => $user->id,
                'days_left' => $daysLeft,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendTrialReminderJob failed', ['user_id' => $this->userId, 'error' => $e->getMessage()]);
    }
}
