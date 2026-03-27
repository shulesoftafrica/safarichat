<?php

namespace App\Console\Commands;

use App\Jobs\CustomerSuccess\TrialEndingWarningJob;
use App\Jobs\CustomerSuccess\TrialExpiredJob;
use App\Models\CsConversationSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCsTrialMonitorCommand extends Command
{
    protected $signature = 'cs:trial-monitor
                            {--dry-run : Report buckets without dispatching jobs}';

    protected $description = 'Monitor trial lifecycle: dispatch T-3h warnings, T=0 expiry notices, and expire stale sessions.';

    public function handle(): int
    {
        $now    = Carbon::now();
        $isDry  = $this->option('dry-run');

        // ── T-3h bucket ──────────────────────────────────────────────────────────
        // Users whose trial ends in the next 0–3 hours
        $warningUsers = User::where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', $now)
            ->where('trial_ends_at', '<=', $now->copy()->addHours(3))
            ->get();

        $this->info(sprintf('[cs:trial-monitor] T-3h bucket: %d user(s)', $warningUsers->count()));

        foreach ($warningUsers as $user) {
            if ($isDry) {
                $this->line(sprintf('  DRY-RUN  [T-3h]  userId=%d  trial_ends_at=%s', $user->id, $user->trial_ends_at));
                continue;
            }
            TrialEndingWarningJob::dispatch($user->id)->onQueue('cs');
        }

        // ── T=0 bucket ───────────────────────────────────────────────────────────
        // Users whose trial has ended but subscription_status still = 'trial'
        $expiredUsers = User::where('subscription_status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', $now)
            ->get();

        $this->info(sprintf('[cs:trial-monitor] T=0  bucket: %d user(s)', $expiredUsers->count()));

        foreach ($expiredUsers as $user) {
            if ($isDry) {
                $this->line(sprintf('  DRY-RUN  [T=0]   userId=%d  trial_ended_at=%s', $user->id, $user->trial_ends_at));
                continue;
            }
            TrialExpiredJob::dispatch($user->id)->onQueue('cs');
        }

        // ── Expire stale conversation sessions ───────────────────────────────────
        $expired = $isDry ? 0 : CsConversationSession::expireStale();
        $this->info(sprintf('[cs:trial-monitor] Stale sessions expired: %d', $expired));

        return self::SUCCESS;
    }
}
