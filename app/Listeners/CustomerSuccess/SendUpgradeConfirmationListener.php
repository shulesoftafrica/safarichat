<?php

namespace App\Listeners\CustomerSuccess;

use App\Events\SubscriptionUpgraded;
use App\Models\CsMessageLog;
use App\Services\CustomerSuccess\CsMessageRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendUpgradeConfirmationListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'cs';
    public int    $tries  = 3;
    public int    $backoff = 60;

    public function __construct(private readonly CsMessageRenderer $renderer) {}

    public function handle(SubscriptionUpgraded $event): void
    {
        $user     = $event->user;
        $fromPlan = $event->fromPlan;
        $toPlan   = $event->toPlan;

        if (CsMessageLog::alreadySent($user->id, 'upgrade_confirmation', hours: 1)) {
            return;
        }

        $toPlanConfig  = config("safarichat_billing.plans.{$toPlan}", []);
        $toPlanLabel   = ucfirst($toPlan);
        $fromPlanLabel = ucfirst($fromPlan);
        $limits        = $toPlanConfig['limits'] ?? [];
        $renewalDate   = now()->addDays(30)->format('F j, Y');

        $features = $this->buildFeatureBullets($limits);

        $sent = $this->renderer->send($user, 'upgrade_confirmation', [
            'from_plan'    => $fromPlanLabel,
            'to_plan'      => $toPlanLabel,
            'features'     => $features,
            'renewal_date' => $renewalDate,
            'dashboard_link' => config('app.url') . '/dashboard',
        ], $user->business_id ?? $user->id);

        if ($sent) {
            Log::info('[CS] Upgrade confirmation message sent', [
                'user_id'   => $user->id,
                'from_plan' => $fromPlan,
                'to_plan'   => $toPlan,
            ]);
        }
    }

    private function buildFeatureBullets(array $limits): string
    {
        $bullets = [];

        if (! empty($limits['unlimited_messages'])) {
            $bullets[] = '✅ Unlimited AI conversations';
        }

        $maxContacts = $limits['max_contacts'] ?? null;
        if ($maxContacts) {
            $bullets[] = '✅ Up to ' . number_format($maxContacts) . ' contacts';
        }

        if (! empty($limits['customer_followups'])) {
            $bullets[] = '✅ Automated follow-ups';
        }

        if (! empty($limits['customer_categorization'])) {
            $bullets[] = '✅ Smart contact categorization';
        }

        $channels = $limits['whatsapp_channels'] ?? null;
        if ($channels && $channels > 1) {
            $bullets[] = '✅ ' . $channels . ' WhatsApp channels';
        }

        if (! empty($limits['sales_reports'])) {
            $bullets[] = '✅ Advanced sales reports & analytics';
        }

        return implode("\n", $bullets) ?: '✅ Enhanced AI sales features';
    }

    public function failed(SubscriptionUpgraded $event, \Throwable $exception): void
    {
        Log::error('[CS] SendUpgradeConfirmationListener failed', [
            'user_id' => $event->user->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
