<?php

namespace App\Console\Commands;

use App\Jobs\CustomerSuccess\CreditLowAlertJob;
use App\Jobs\CustomerSuccess\UsageLimitApproachingJob;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CS Phase 4 — Credit & usage-limit alert dispatcher
 *
 * Runs hourly. For every user with a billing account it calculates the
 * AI-credit percentage remaining and dispatches the appropriate alert job
 * when critical thresholds are crossed.  The jobs themselves implement
 * 720-hour dedup so the same message is never sent more than once per ~month.
 *
 *   Credit-low thresholds  (all users, including trial):
 *     ≤ 20 % remaining  → CreditLowAlertJob(userId, 20)  — "heads-up" warning
 *     ≤ 10 % remaining  → CreditLowAlertJob(userId, 10)  — urgent alert
 *
 *   Usage-limit thresholds (paid users only; jobs enforce this internally):
 *     ≥ 80 % used       → UsageLimitApproachingJob(userId, 80)  — first notice
 *     ≥ 95 % used       → UsageLimitApproachingJob(userId, 95)  — final warning
 */
class SendCsCreditMonitorCommand extends Command
{
    protected $signature   = 'cs:credit-monitor';
    protected $description = 'Dispatch credit-low and usage-limit alerts for users approaching AI credit limits';

    public function handle(): int
    {
        $this->info('CS credit monitor started.');

        $users = User::with('billingAccount')
            ->whereHas('billingAccount', fn ($q) => $q->whereNotNull('ai_credits'))
            ->get();

        $checked    = $users->count();
        $dispatched = 0;

        foreach ($users as $user) {
            $billingAccount = $user->billingAccount;
            if (! $billingAccount) {
                continue;
            }

            $planCode  = $billingAccount->subscription_plan ?? 'trial';
            $planLimit = (int) config("safarichat_billing.plans.{$planCode}.limits.ai_credits", 0);

            // Skip if plan config is missing or has no credit limit
            if ($planLimit <= 0) {
                continue;
            }

            $available     = max(0, (int) ($billingAccount->ai_credits ?? 0));
            $percentRemain = (int) round(($available / $planLimit) * 100);
            $percentUsed   = 100 - $percentRemain;

            // ── Credit-low alerts (applies to all users, trial included) ──────────
            // Dispatch only the most urgent applicable tier to avoid duplicate sends
            if ($percentRemain <= 10) {
                CreditLowAlertJob::dispatch($user->id, 10)->onQueue('cs');
                $dispatched++;
            } elseif ($percentRemain <= 20) {
                CreditLowAlertJob::dispatch($user->id, 20)->onQueue('cs');
                $dispatched++;
            }

            // ── Usage-limit alerts (paid users only; job also enforces this) ─────
            $subscriptionStatus = $billingAccount->subscription_status ?? 'trial';
            $isPaid             = ! in_array($subscriptionStatus, ['trial', 'inactive', 'cancelled']);

            if ($isPaid) {
                if ($percentUsed >= 95) {
                    UsageLimitApproachingJob::dispatch($user->id, 95)->onQueue('cs');
                    $dispatched++;
                } elseif ($percentUsed >= 80) {
                    UsageLimitApproachingJob::dispatch($user->id, 80)->onQueue('cs');
                    $dispatched++;
                }
            }
        }

        $message = "CS credit monitor: {$dispatched} alert job(s) dispatched from {$checked} user(s) checked.";
        Log::info($message);
        $this->info($message);

        return 0;
    }
}
