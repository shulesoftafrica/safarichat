<?php

namespace App\Console\Commands;

use App\Jobs\CustomerSuccess\SendDailyBusinessSummaryJob;
use App\Models\User;
use App\Models\WhatsappInstance;
use Illuminate\Console\Command;

/**
 * Dispatches a SendDailyBusinessSummaryJob for every eligible business owner.
 *
 * Scheduled: daily at 20:00 (Africa/Nairobi fallback — per-user timezone handled inside the job).
 * Artisan: php artisan cs:daily-summary
 */
class SendCsDailySummaryCommand extends Command
{
    protected $signature = 'cs:daily-summary
                            {--dry-run : List eligible users without dispatching jobs}';

    protected $description = 'Dispatch daily CS summary jobs for all connected business owners';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun ? '[DRY RUN] Listing eligible business owners…' : 'Dispatching CS daily summaries…');

        // Fetch all users who have a business
        $users = User::whereHas('business')
            ->with('business')
            ->get();

        $dispatched = 0;
        $skipped    = 0;

        // Spread the sends over a window instead of one burst: the notification API is capped at 2 requests/second
        // and one WhatsApp number must not fire hundreds of messages in the same second. Ignored by the sync queue,
        // which runs the jobs one after another (WaSenderService paces its own calls).
        $spreadSeconds = max(0, (int) config('cs.daily_summary_spread_minutes', 60)) * 60;
        $total         = max(1, $users->count());
        $position      = 0;

        foreach ($users as $user) {
            $business = $user->business;
            if (!$business) {
                continue;
            }

            if ($isDryRun) {
                $this->line("  → [{$user->id}] {$user->email} — {$business->name} (business_id={$business->id})");
                $dispatched++;
                continue;
            }

            $delay = $spreadSeconds > 0 ? (int) floor($position * $spreadSeconds / $total) : 0;
            SendDailyBusinessSummaryJob::dispatch($user->id, $business->id)->delay(now()->addSeconds($delay));
            $position++;
            $dispatched++;

            $this->line("  ✅ Dispatched for: {$user->email} / {$business->name}");
        }

        $this->info("Done. {$dispatched} job(s) dispatched, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
