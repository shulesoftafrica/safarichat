<?php

namespace App\Services\CustomerSuccess;

use App\Models\CsConversationSession;
use App\Models\CsMessageLog;
use App\Models\User;
use App\Models\WhatsappInstance;
use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * CsConversationHandler
 *
 * Handles inbound WhatsApp messages directed at a CS instance (instance_type = 'customer_success')
 * or the CS half of a hybrid instance (instance_type = 'both').
 *
 * Waterfall (§4.3.7):
 *   1. Active conversation session → continue
 *   2. CS keyword match          → start / resume flow
 *   3. User has received any CS message in last 24h → contextual reply
 *   4. Default                   → help menu
 */
class CsConversationHandler
{
    // ── Plan meta ────────────────────────────────────────────────────────────────

    /** Plans offered in the upgrade flow, in display order */
    private const UPGRADE_PLANS = ['starter', 'pro', 'premium'];

    /** Human-readable plan names */
    private const PLAN_LABELS = [
        'starter' => 'Starter',
        'pro'     => 'Pro',
        'premium' => 'Premium',
    ];

    /**
     * Map plan codes to billing-API price plan IDs.
     * Mirrors BillingController::getPricePlanIdForPlan().
     */
    private function getPricePlanId(string $planCode): int
    {
        return match($planCode) {
            'starter' => (int) config('services.billing.price_plans.starter', 4),
            'pro'     => (int) config('services.billing.price_plans.pro',     5),
            'premium' => (int) config('services.billing.price_plans.premium', 6),
            default   => 8,
        };
    }

    // ── Keyword detection ────────────────────────────────────────────────────────

    private const KEYWORDS = [
        'upgrade', 'help', 'report', 'pause',
        'buy credits', 'yes', 'no',
        'bei', 'package', 'lipa', 'pay', 'price', 'how much',
        'nunua credits', 'gharama',
    ];

    private function matchesKeyword(string $text): ?string
    {
        $lower = mb_strtolower(trim($text));
        foreach (self::KEYWORDS as $kw) {
            if (str_contains($lower, $kw)) {
                return $kw;
            }
        }
        return null;
    }

    private function isUpgradeKeyword(string $kw): bool
    {
        return in_array($kw, ['upgrade', 'bei', 'package', 'lipa', 'pay', 'price', 'how much', 'gharama'], true);
    }

    private function isCreditKeyword(string $kw): bool
    {
        return in_array($kw, ['buy credits', 'nunua credits'], true);
    }

    // ── Public entry point ───────────────────────────────────────────────────────

    /**
     * Route an inbound message through the CS waterfall.
     *
     * @param User             $user       The business owner / CS actor
     * @param string           $message    Normalised plain-text body of the incoming WA message
     * @param array            $rawWebhook Full webhook payload (for context / logging)
     * @param WhatsappInstance $instance   The instance that received the message
     */
    public function handleInbound(
        User             $user,
        string           $message,
        array            $rawWebhook,
        WhatsappInstance $instance
    ): void {
        try {
            $this->dispatchWaterfall($user, $message);
        } catch (\Throwable $e) {
            Log::error('[CsConversationHandler] Unhandled exception', [
                'user_id'     => $user->id,
                'instance_id' => $instance->id,
                'message'     => $message,
                'error'       => $e->getMessage(),
                'trace'       => $e->getTraceAsString(),
            ]);
        }
    }

    // ── Waterfall ────────────────────────────────────────────────────────────────

    private function dispatchWaterfall(User $user, string $message): void
    {
        // Step 1 — active conversation session
        $session = CsConversationSession::findActive($user->id);
        if ($session) {
            $this->continueSession($session, $message, $user);
            return;
        }

        // Step 2 — CS keyword
        $kw = $this->matchesKeyword($message);
        if ($kw !== null) {
            $this->handleKeyword($user, $message, $kw);
            return;
        }

        // Step 3 — user has already received a CS message recently → contextual reply
        if (CsMessageLog::alreadySent($user->id, '%', hours: 24)) {
            $this->sendHelpMenu($user);
            return;
        }

        // Step 4 — default: show the help menu
        $this->sendHelpMenu($user);
    }

    // ── Keyword handler ──────────────────────────────────────────────────────────

    private function handleKeyword(User $user, string $message, string $kw): void
    {
        // Credit purchase intent
        if ($this->isCreditKeyword($kw)) {
            $this->startCreditPurchaseFlow($user);
            return;
        }

        if ($this->isUpgradeKeyword($kw)) {
            $context = ($user->subscription_status === 'trial')
                ? CsConversationSession::CONTEXT_TRIAL_UPGRADE
                : CsConversationSession::CONTEXT_SUBSCRIPTION_UPGRADE;

            $this->startUpgradeFlow($user, $context);
            return;
        }

        if ($kw === 'help') {
            $this->sendHelpMenu($user);
            return;
        }

        // 'yes', 'no', other words with no active session → help
        $this->sendHelpMenu($user);
    }

    // ── Credit purchase flow ──────────────────────────────────────────────────────

    /** Credit package definitions */
    private const CREDIT_PACKAGES = [
        1 => ['label' => 'Small Top-up',  'amount' => 10000,  'credits' => 100],
        2 => ['label' => 'Popular Pack',  'amount' => 50000,  'credits' => 600],
        3 => ['label' => 'Power Pack',    'amount' => 100000, 'credits' => 1400],
    ];

    /**
     * Show the credit package selection menu and open a credit_purchase session.
     */
    private function startCreditPurchaseFlow(User $user): void
    {
        CsConversationSession::startFor($user->id, CsConversationSession::CONTEXT_CREDIT_PURCHASE);
        CsMessageRenderer::send($user, 'cs_credit_packages', [], $user->business_id ?? $user->id);
    }

    /**
     * User chose a credit package (1 / 2 / 3).
     */
    private function handleCreditPackageChoice(CsConversationSession $session, string $message, User $user): void
    {
        $choice = (int) trim($message);

        if (! isset(self::CREDIT_PACKAGES[$choice])) {
            CsMessageRenderer::send(
                $user,
                'cs_invalid_choice',
                ['max' => count(self::CREDIT_PACKAGES)],
                $user->business_id ?? $user->id
            );
            return;
        }

        $package = self::CREDIT_PACKAGES[$choice];
        $amount  = $package['amount'];
        $label   = $package['label'];

        // Use a pseudo plan ID for credits purchases (configurable)
        $creditsPlanId = (int) config('services.billing.price_plans.credits', 10);

        try {
            $result = BillingService::createSubscriptionInvoice(
                $user,
                $creditsPlanId,
                $amount,
                'flutterwave',
                config('app.url') . '/billing/success',
                config('app.url') . '/billing/cancel'
            );
        } catch (\Throwable $e) {
            Log::error('[CsConversationHandler] Credit invoice creation failed', [
                'user_id' => $user->id, 'error' => $e->getMessage(),
            ]);
            CsMessageRenderer::send($user, 'cs_billing_error', [], $user->business_id ?? $user->id);
            return;
        }

        if (! ($result['success'] ?? false)) {
            CsMessageRenderer::send($user, 'cs_billing_error', [], $user->business_id ?? $user->id);
            return;
        }

        $invoiceData     = $result['data']['invoice'] ?? $result['data'] ?? [];
        $invoiceId       = $invoiceData['id']           ?? null;
        $paymentLinks    = $result['data']['payment_links'] ?? [];
        $ucn             = $paymentLinks['ucn']          ?? '';
        $stripeLink      = $paymentLinks['stripe']        ?? '';
        $flutterwaveLink = $paymentLinks['flutterwave']   ?? '';

        $session->awaitPayment([
            'credit_package' => $choice,
            'plan_name'      => $label,
            'amount'         => $amount,
            'invoice_id'     => $invoiceId,
            'ucn'            => $ucn,
        ]);

        CsMessageRenderer::send($user, 'cs_payment_details', [
            'plan_name'        => $label . ' (Credits)',
            'amount'           => number_format($amount),
            'ucn'              => $ucn              ?: 'N/A',
            'stripe_link'      => $stripeLink       ?: 'N/A',
            'flutterwave_link' => $flutterwaveLink  ?: 'N/A',
        ], $user->business_id ?? $user->id);
    }

    // ── Upgrade flow ─────────────────────────────────────────────────────────────

    /**
     * Show the plan-selection menu and open a new session.
     */
    private function startUpgradeFlow(User $user, string $context): void
    {
        $plans   = config('safarichat_billing.plans', []);
        $locale  = $this->resolveLocale($user);

        // Build a numbered list of purchasable plans
        $lines = [];
        foreach (self::UPGRADE_PLANS as $idx => $code) {
            $planCfg   = $plans[$code] ?? [];
            $price     = number_format((int) ($planCfg['price'] ?? 0));
            $label     = self::PLAN_LABELS[$code] ?? ucfirst($code);
            $lines[]   = ($idx + 1) . ". *{$label}* — TZS {$price}/mo";
        }
        $planList = implode("\n", $lines);

        // Create session before sending (so if render fails the session still exists)
        CsConversationSession::startFor($user->id, $context);

        CsMessageRenderer::send($user, 'cs_plan_list', ['plan_list' => $planList], $user->business_id ?? $user->id);
    }

    // ── Session continuation ─────────────────────────────────────────────────────

    private function continueSession(CsConversationSession $session, string $message, User $user): void
    {
        if ($session->state === CsConversationSession::STATE_AWAITING_PACKAGE) {
            if ($session->context === CsConversationSession::CONTEXT_CREDIT_PURCHASE) {
                $this->handleCreditPackageChoice($session, $message, $user);
            } else {
                $this->handlePackageChoice($session, $message, $user);
            }
            return;
        }

        if ($session->state === CsConversationSession::STATE_AWAITING_PAYMENT) {
            $this->handlePaymentConfirmation($session, $message, $user);
            return;
        }

        // expired / completed slipped past findActive
        $this->sendHelpMenu($user);
    }

    /**
     * User replied with a plan number (1 / 2 / 3).
     */
    private function handlePackageChoice(CsConversationSession $session, string $message, User $user): void
    {
        $choice = (int) trim($message);

        if ($choice < 1 || $choice > count(self::UPGRADE_PLANS)) {
            // Invalid choice — re-prompt
            CsMessageRenderer::send(
                $user,
                'cs_invalid_choice',
                ['max' => count(self::UPGRADE_PLANS)],
                $user->business_id ?? $user->id
            );
            return;
        }

        $planCode  = self::UPGRADE_PLANS[$choice - 1];
        $planLabel = self::PLAN_LABELS[$planCode];
        $planCfg   = config("safarichat_billing.plans.{$planCode}", []);
        $amount    = (int) ($planCfg['price'] ?? 0);

        // Create invoice
        try {
            $pricePlanId = $this->getPricePlanId($planCode);
            $result = BillingService::createSubscriptionInvoice(
                $user,
                $pricePlanId,
                $amount,
                'flutterwave',
                config('app.url') . '/billing/success',
                config('app.url') . '/billing/cancel'
            );
        } catch (\Throwable $e) {
            Log::error('[CsConversationHandler] Invoice creation failed', [
                'user_id'   => $user->id,
                'plan_code' => $planCode,
                'error'     => $e->getMessage(),
            ]);
            CsMessageRenderer::send($user, 'cs_billing_error', [], $user->business_id ?? $user->id);
            return;
        }

        if (! ($result['success'] ?? false) || ! isset($result['data'])) {
            Log::warning('[CsConversationHandler] Invoice result not successful', [
                'user_id' => $user->id,
                'result'  => $result,
            ]);
            CsMessageRenderer::send($user, 'cs_billing_error', [], $user->business_id ?? $user->id);
            return;
        }

        $invoiceData  = $result['data']['invoice'] ?? $result['data'] ?? [];
        $invoiceId    = $invoiceData['id']       ?? null;
        $paymentLinks = $result['data']['payment_links'] ?? [];

        $ucn             = $paymentLinks['ucn']          ?? '';
        $stripeLink      = $paymentLinks['stripe']        ?? '';
        $flutterwaveLink = $paymentLinks['flutterwave']   ?? '';

        // Advance session state
        $session->awaitPayment([
            'plan_code'   => $planCode,
            'plan_name'   => $planLabel,
            'amount'      => $amount,
            'invoice_id'  => $invoiceId,
            'ucn'         => $ucn,
        ]);

        // Send payment details
        CsMessageRenderer::send($user, 'cs_payment_details', [
            'plan_name'         => $planLabel,
            'amount'            => number_format($amount),
            'ucn'               => $ucn ?: 'N/A',
            'stripe_link'       => $stripeLink  ?: 'N/A',
            'flutterwave_link'  => $flutterwaveLink ?: 'N/A',
        ], $user->business_id ?? $user->id);
    }

    /**
     * User replied after they've received payment instructions.
     */
    private function handlePaymentConfirmation(CsConversationSession $session, string $message, User $user): void
    {
        $lower = mb_strtolower(trim($message));

        // If user sends a screenshot / media or says "done" / "sent"
        $completionWords = ['done', 'sent', 'paid', 'nimetuma', 'nimetoa', 'nimefanya', 'ok', 'okay'];
        $isConfirmation  = array_any($completionWords, fn($w) => str_contains($lower, $w));

        // PHP < 8.1 fallback for array_any — check manually if needed
        if (! function_exists('array_any')) {
            $isConfirmation = false;
            foreach ($completionWords as $w) {
                if (str_contains($lower, $w)) {
                    $isConfirmation = true;
                    break;
                }
            }
        }

        if ($isConfirmation) {
            $session->complete();
            CsMessageRenderer::send($user, 'cs_payment_received', [], $user->business_id ?? $user->id);
            return;
        }

        // User may be asking for another plan or cancelling
        if (str_contains($lower, 'cancel') || str_contains($lower, 'back') || str_contains($lower, 'rudi')) {
            $session->complete();
            $this->startUpgradeFlow($user, $session->context);
            return;
        }

        // Otherwise just re-send a reminder with the payment link
        $payload         = $session->payload ?? [];
        $ucn             = $payload['ucn']       ?? '';
        $planLabel       = $payload['plan_name'] ?? '';
        $amount          = isset($payload['amount']) ? number_format((int) $payload['amount']) : '';

        CsMessageRenderer::send($user, 'cs_payment_reminder', [
            'plan_name' => $planLabel,
            'amount'    => $amount,
            'ucn'       => $ucn ?: 'N/A',
        ], $user->business_id ?? $user->id);
    }

    // ── Contextual reply ─────────────────────────────────────────────────────────

    /**
     * User has previously received a CS message and replied with a non-keyword message.
     * We just show the help menu rather than ignore them.
     */
    private function handleContextualReply(User $user, string $message): void
    {
        $this->sendHelpMenu($user);
    }

    // ── Help menu ────────────────────────────────────────────────────────────────

    private function sendHelpMenu(User $user): void
    {
        CsMessageRenderer::send($user, 'cs_help_menu', [], $user->business_id ?? $user->id);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────────

    private function resolveLocale(User $user): string
    {
        return CsMessageRenderer::resolveLocale($user);
    }
}
