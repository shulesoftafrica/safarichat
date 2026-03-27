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
 * Fires when a paid user's credit usage approaches the monthly plan limit.
 *
 * Thresholds (expressed as % of plan credits used):
 *   80 → sends the first "you're using a lot" nudge with an upgrade CTA
 *   95 → sends an urgent "almost out" warning
 */
class UsageLimitApproachingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 120;

    /**
     * @param int $userId    The user to check.
     * @param int $threshold Percentage of plan credits USED that triggers this alert. (80 or 95)
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

        // Only apply to paid (non-trial) users
        if ($user->subscription_status === 'trial' || $user->subscription_status === 'inactive') {
            return;
        }

        $business  = $user->business;
        $planCode  = $business?->subscription_plan ?? 'starter';
        $planLimit = (int) config("safarichat_billing.plans.{$planCode}.limits.ai_credits", 0);

        if ($planLimit <= 0) {
            return;
        }

        $available      = (int) ($user->available_credits ?? 0);
        $percentUsed    = (int) round((1 - $available / $planLimit) * 100);
        $percentRemain  = 100 - $percentUsed;

        // Only fire if we've actually crossed this threshold
        if ($percentUsed < $this->threshold) {
            return;
        }

        $logType = match(true) {
            $this->threshold >= 95 => 'usage_limit_95',
            default                => 'usage_limit_80',
        };

        // Dedup — fire once per monthly billing cycle (≈ 30 days)
        if (CsMessageLog::alreadySent($this->userId, $logType, hours: 720)) {
            return;
        }

        $planLabel      = ucfirst($planCode);
        $billingLink    = config('app.url') . '/billing';

        if ($this->threshold >= 95) {
            $vars = [
                'plan_name'       => $planLabel,
                'percent_used'    => $percentUsed,
                'percent_remain'  => $percentRemain,
                'remaining'       => number_format($available),
                'usage_label'     => 'almost at',
                'billing_link'    => $billingLink,
                'urgency_prefix'  => '⚠️',
            ];
        } else {
            $vars = [
                'plan_name'      => $planLabel,
                'percent_used'   => $percentUsed,
                'percent_remain' => $percentRemain,
                'remaining'      => number_format($available),
                'usage_label'    => '80% of',
                'billing_link'   => $billingLink,
                'urgency_prefix' => '📈',
            ];
        }

        $sent = $renderer->send(
            $user,
            'usage_limit_warning',
            $vars,
            $user->business_id ?? $user->id
        );

        if ($sent) {
            Log::info('[CS] Usage limit warning sent', [
                'user_id'     => $user->id,
                'plan_code'   => $planCode,
                'percent_used' => $percentUsed,
                'threshold'   => $this->threshold,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[CS] UsageLimitApproachingJob failed', [
            'user_id'   => $this->userId,
            'threshold' => $this->threshold,
            'error'     => $e->getMessage(),
        ]);
    }
}
