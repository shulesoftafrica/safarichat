<?php

namespace App\Listeners\CustomerSuccess;

use App\Events\SubscriptionActivated;
use App\Models\CsMessageLog;
use App\Services\CustomerSuccess\CsMessageRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendSubscriptionSuccessMessageListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'cs';
    public int    $tries  = 3;
    public int    $backoff = 60;

    public function __construct(private readonly CsMessageRenderer $renderer) {}

    public function handle(SubscriptionActivated $event): void
    {
        $user     = $event->user;
        $planCode = $event->planCode;

        // Once-ever guard — activation message is sent once per subscription event
        if (CsMessageLog::alreadySent($user->id, 'subscription_success', hours: 1)) {
            return;
        }

        $planConfig   = config("safarichat_billing.plans.{$planCode}", []);
        $planLabel    = ucfirst($planCode);
        $limits       = $planConfig['limits'] ?? [];
        $renewalDate  = now()->addDays(30)->format('F j, Y');

        // Build feature bullets from plan limits
        $features = $this->buildFeatureBullets($limits);

        // Context-aware CTA (OnboardingGapResolver logic — inline)
        $cta = $this->resolveCtaForUser($user);

        $sent = $this->renderer->send($user, 'subscription_success', [
            'plan_name'    => $planLabel,
            'features'     => $features,
            'renewal_date' => $renewalDate,
            'cta'          => $cta,
            'dashboard_link' => config('app.url') . '/dashboard',
        ], $user->business_id ?? $user->id);

        if ($sent) {
            Log::info('[CS] Subscription success message sent', [
                'user_id'   => $user->id,
                'plan_code' => $planCode,
            ]);
        }
    }

    private function buildFeatureBullets(array $limits): string
    {
        $bullets = [];

        if (! empty($limits['unlimited_messages'])) {
            $bullets[] = '✅ Unlimited AI-powered conversations';
        }

        $maxContacts = $limits['max_contacts'] ?? null;
        if ($maxContacts) {
            $bullets[] = '✅ Up to ' . number_format($maxContacts) . ' business contacts';
        }

        $maxProducts = $limits['max_products'] ?? null;
        if ($maxProducts) {
            $bullets[] = '✅ Up to ' . $maxProducts . ' products/services';
        }

        if (! empty($limits['customer_followups'])) {
            $bullets[] = '✅ Automated customer follow-ups';
        }

        if (! empty($limits['sales_reports'])) {
            $bullets[] = '✅ Advanced sales reports';
        }

        $aiCredits = $limits['ai_credits'] ?? null;
        if ($aiCredits) {
            $bullets[] = '✅ ' . number_format($aiCredits) . ' AI credits per month';
        }

        return implode("\n", $bullets) ?: '✅ Full AI sales agent access';
    }

    /**
     * Simple inline equivalent of OnboardingGapResolver.
     * Checks what the user hasn't done yet and returns an appropriate CTA string.
     */
    private function resolveCtaForUser(\App\Models\User $user): string
    {
        $business = $user->business;

        // No products yet
        if ($business && $business->products()->count() === 0) {
            return '→ Add your first product so your AI agent can start selling: '
                . config('app.url') . '/dashboard/products';
        }

        // Never sent a broadcast
        // (Simple proxy: check if user has any messages in outbox)
        return '→ Share your WhatsApp number on social media to drive your first leads: '
            . config('app.url') . '/dashboard/compose';
    }

    public function failed(SubscriptionActivated $event, \Throwable $exception): void
    {
        Log::error('[CS] SendSubscriptionSuccessMessageListener failed', [
            'user_id' => $event->user->id,
            'error'   => $exception->getMessage(),
        ]);
    }
}
