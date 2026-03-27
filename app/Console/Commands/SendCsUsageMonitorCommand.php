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
        // NOTE: subscription_status lives on billing_accounts, not users — use whereHas.
        $users = User::whereHas('billingAccount', fn ($q) =>
                $q->whereIn('subscription_status', ['active', 'trial'])
            )
            ->whereNotNull('available_credits')
            ->get();

        $this->info(sprintf('[cs:usage-monitor] Checking %d user(s).', $users->count()));

        $dispatched = 0;

        foreach ($users as $user) {
            $business   = $user->business;
            $planCode   = $business?->subscription_plan
                ?? ($user->subscription_status === 'trial' ? 'trial' : 'starter');
            $planLimit  = (int) config("safarichat_billing.plans.{$planCode}.limits.ai_credits", 0);

            if ($planLimit <= 0) {
                continue;
            }

            $available     = (int) ($user->available_credits ?? 0);
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
            if ($user->subscription_status === 'active') {
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
