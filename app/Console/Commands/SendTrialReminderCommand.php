<?php

namespace App\Console\Commands;

use App\Jobs\CustomerSuccess\SendTrialReminderJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendTrialReminderCommand extends Command
{
    protected $signature = 'cs:trial-reminders
                            {--dry-run : List users without dispatching jobs}';

    protected $description = 'Dispatch daily trial-countdown WhatsApp reminders for all active trial users.';

    public function handle(): int
    {
        $now = Carbon::now();

        $users = User::whereHas('billingAccount', function ($q) use ($now) {
            $q->where('subscription_plan', 'trial')
              ->whereNotNull('trial_ends_at')
              ->where('trial_ends_at', '>', $now);
        })->get();

        $this->info(sprintf('[cs:trial-reminders] %d active trial user(s) found.', $users->count()));

        if ($this->option('dry-run')) {
            foreach ($users as $user) {
                $days = (int) $now->diffInDays($user->trial_ends_at, false);
                $this->line(sprintf('  DRY-RUN  userId=%d  %s  days_left=%d', $user->id, $user->email, $days));
            }
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($users as $user) {
            SendTrialReminderJob::dispatch($user->id)->onQueue('cs');
            $dispatched++;
        }

        $this->info(sprintf('[cs:trial-reminders] Dispatched %d reminder job(s).', $dispatched));

        return self::SUCCESS;
    }
}
