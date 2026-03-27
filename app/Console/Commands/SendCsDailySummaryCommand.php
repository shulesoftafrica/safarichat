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

            SendDailyBusinessSummaryJob::dispatch($user->id, $business->id);
            $dispatched++;

            $this->line("  ✅ Dispatched for: {$user->email} / {$business->name}");
        }

        $this->info("Done. {$dispatched} job(s) dispatched, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
