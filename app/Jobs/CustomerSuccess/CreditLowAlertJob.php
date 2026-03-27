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
 * Fires when a user's AI credit balance drops to a low threshold.
 *
 * Threshold = percentage of plan credits REMAINING:
 *   20 → credits are at ≤ 20% (first warning — buy more credits)
 *   10 → credits at ≤ 10% (urgent — agent will stop soon)
 */
class CreditLowAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries  = 3;
    public int $backoff = 120;

    /**
     * @param int $userId    The user to check.
     * @param int $threshold Percentage of plan credits REMAINING that triggers this alert. (20 or 10)
     */
    public function __construct(
        private readonly int $userId,
        private readonly int $threshold,
    ) {}

    public function handle(CsMessageRenderer $renderer): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }

        // Credits alerts apply to all users (trial and paid)
        $business  = $user->business;
        $planCode  = $business?->subscription_plan ?? ($user->subscription_status === 'trial' ? 'trial' : 'starter');
        $planLimit = (int) config("safarichat_billing.plans.{$planCode}.limits.ai_credits", 1000);

        if ($planLimit <= 0) {
            return;
        }

        $available      = (int) ($user->available_credits ?? 0);
        $percentRemain  = (int) round(($available / $planLimit) * 100);

        // Only fire if remaining is at or below the threshold
        if ($percentRemain > $this->threshold) {
            return;
        }

        $logType = match(true) {
            $this->threshold <= 10 => 'credit_low_10',
            default                => 'credit_low_20',
        };

        // Dedup — fire once per monthly billing cycle (≈ 30 days)
        if (CsMessageLog::alreadySent($this->userId, $logType, hours: 720)) {
            return;
        }

        $billingLink = config('app.url') . '/billing';

        $sent = $renderer->send($user, 'credit_low_warning', [
            'remaining_credits' => number_format($available),
            'percent_left'      => $percentRemain,
            'billing_link'      => $billingLink,
            'urgency_prefix'    => $this->threshold <= 10 ? '🚨' : '⚡',
        ], $user->business_id ?? $user->id);

        if ($sent) {
            Log::info('[CS] Credit low alert sent', [
                'user_id'        => $user->id,
                'available'      => $available,
                'percent_remain' => $percentRemain,
                'threshold'      => $this->threshold,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[CS] CreditLowAlertJob failed', [
            'user_id'   => $this->userId,
            'threshold' => $this->threshold,
            'error'     => $e->getMessage(),
        ]);
    }
}
