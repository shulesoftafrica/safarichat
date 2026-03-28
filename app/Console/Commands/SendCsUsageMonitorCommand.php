<?php

namespace App\Console\Commands;

use App\Jobs\CustomerSuccess\CreditLowAlertJob;
use App\Jobs\CustomerSuccess\UsageLimitApproachingJob;
use App\Models\User;
use Illuminate\Console\Command;

class SendCsUsageMonitorCommand extends Command
{
    protected $signature = 'cs:usage-monitor
                            {--dry-run : Report thresholds without dispatching jobs}';

    protected $description = 'Dispatch credit and usage alerts for users approaching plan limits.';

    public function handle(): int
    {
        $isDry = $this->option('dry-run');

        // Target all active users (paid + trial).
        // NOTE: all billing state lives on billing_accounts — eager-load to avoid N+1.
        $users = User::whereHas('billingAccount', fn ($q) =>
                $q->whereIn('subscription_status', ['active', 'trial'])
            )
            ->with('billingAccount')
            ->get();

        $this->info(sprintf('[cs:usage-monitor] Checking %d user(s).', $users->count()));

        $dispatched = 0;

        foreach ($users as $user) {
            $billingAccount = $user->billingAccount;
            if (! $billingAccount) {
                continue;
            }

            // subscription_plan lives on billing_accounts (removed from businesses/users)
            $planCode  = $billingAccount->subscription_plan ?? 'starter';
            $planLimit = (int) config("safarichat_billing.plans.{$planCode}.limits.ai_credits", 0);

            if ($planLimit <= 0) {
                continue;
            }

            // ai_credits is the live balance (available_credits column was dropped from users)
            $available     = (int) ($billingAccount->ai_credits ?? 0);
            $percentRemain = ($available / $planLimit) * 100;
            $percentUsed   = 100 - $percentRemain;

            if ($isDry) {
                $this->line(sprintf(
                    '  userId=%-6d %-10s  %.1f%% used  %s credits remaining',
                    $user->id,
                    $planCode,
                    $percentUsed,
                    number_format($available)
                ));
                continue;
            }

            // ── Usage limit approaching (paid users, 80% and 95% thresholds) ──────
            if ($billingAccount->subscription_status === 'active') {
                if ($percentUsed >= 80) {
                    UsageLimitApproachingJob::dispatch($user->id, 80)->onQueue('cs');
                    $dispatched++;
                }
                if ($percentUsed >= 95) {
                    UsageLimitApproachingJob::dispatch($user->id, 95)->onQueue('cs');
                    $dispatched++;
                }
            }

            // ── Credit low alerts (all users, 20% and 10% remaining thresholds) ───
            if ($percentRemain <= 20) {
                CreditLowAlertJob::dispatch($user->id, 20)->onQueue('cs');
                $dispatched++;
            }
            if ($percentRemain <= 10) {
                CreditLowAlertJob::dispatch($user->id, 10)->onQueue('cs');
                $dispatched++;
            }
        }

        if (! $isDry) {
            $this->info(sprintf('[cs:usage-monitor] Dispatched %d alert job(s).', $dispatched));
        }

        return self::SUCCESS;
    }
}
