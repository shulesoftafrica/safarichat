<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingAccount;
use App\Services\AccountSuspensionService;
use App\Services\OnboardingVideo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Cross-tenant account actions for the ShuleSoft admin panel (SafariChat menu there).
 * Every route sits behind VerifyAdminPanelSignature. Changes go through the BillingAccount
 * methods so the billing_credits_manager trigger keeps writing credit_adjustments as usual.
 * The admin panel keeps its own audit log of who did what and why; `admin` in each payload
 * is the admin panel user, recorded here in the application log as well.
 */
class AdminPanelController extends Controller
{
    /** Plans an admin can move a business onto; mirrors config/safarichat_billing.php. */
    const PLANS = ['starter', 'pro', 'premium'];

    /**
     * POST /api/admin-panel/businesses/{businessId}/manage
     * Any of: plan, expires_at (Y-m-d), add_credits; plus note and admin.
     */
    public function manage(Request $request, int $businessId)
    {
        $v = Validator::make($request->all(), [
            'plan' => 'nullable|string|in:' . implode(',', self::PLANS),
            'expires_at' => 'nullable|date_format:Y-m-d',
            'add_credits' => 'nullable|integer|min:0|max:10000000',
            'note' => 'nullable|string|max:1000',
            'admin' => 'required|string|max:255',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $account = BillingAccount::where('business_id', $businessId)->first();
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'This business has no billing account'], 404);
        }

        $before = $this->snapshot($account);

        DB::transaction(function () use ($request, $account) {
            // Expiry first: a future expiry on an expired/cancelled account makes it active
            // again, and the credits trigger only manages active subscriptions — so the plan
            // change below then gets its fresh credit allocation.
            if ($request->filled('expires_at')) {
                $expiresAt = Carbon::parse($request->input('expires_at'))->endOfDay();
                $account->subscription_expires_at = $expiresAt;
                if ($account->subscription_plan === 'trial') {
                    $account->trial_ends_at = $expiresAt;
                }
                if ($expiresAt->isFuture() && in_array($account->subscription_status, ['expired', 'cancelled', 'inactive', null], true)) {
                    $account->subscription_status = 'active';
                }
                $account->save();
            }

            if ($request->filled('plan') && $request->input('plan') !== $account->subscription_plan) {
                $account->changePlan($request->input('plan'));
            }

            if ((int) $request->input('add_credits') > 0) {
                $account->addCredits((int) $request->input('add_credits'), 'Admin panel: ' . ($request->input('note') ?: 'manual top-up'));
            }
        });

        $account->refresh();
        AccountSuspensionService::forgetBusiness($businessId);

        Log::info('Admin panel updated a billing account', [
            'business_id' => $businessId,
            'admin' => $request->input('admin'),
            'note' => $request->input('note'),
            'before' => $before,
            'after' => $this->snapshot($account),
        ]);

        return response()->json(['success' => true, 'before' => $before, 'after' => $this->snapshot($account)]);
    }

    /**
     * POST /api/admin-panel/businesses/{businessId}/payment
     *
     * Record a payment confirmed outside the billing platform (UCN / Lipa Namba or card).
     * Applied the way BillingWebhookController::handlePaymentSuccess applies a paid
     * subscription: plan, status active, limits from config, last payment columns and a new
     * billing_cycle_id, which makes the billing_credits_manager trigger reset the plan's monthly
     * credits. The period starts after the current paid period or trial, so no days are lost,
     * and runs 30 days per month paid.
     */
    public function payment(Request $request, int $businessId)
    {
        $v = Validator::make($request->all(), [
            'plan' => 'required|string|in:' . implode(',', self::PLANS),
            'months' => 'required|integer|in:1,3,6,12',
            'method' => 'required|string|in:UCN,Card',
            'reference' => 'required|string|max:100',
            'note' => 'nullable|string|max:1000',
            'admin' => 'required|string|max:255',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $account = BillingAccount::where('business_id', $businessId)->first();
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'This business has no billing account'], 404);
        }

        $reference = trim($request->input('reference'));
        $cycleId = 'admin_' . preg_replace('/[^A-Za-z0-9]/', '', $reference) . '_' . now()->format('Ym');
        if ($account->billing_cycle_id === $cycleId || $account->last_transaction_id === $reference) {
            return response()->json(['success' => false, 'message' => "Payment {$reference} is already recorded for this client."], 422);
        }

        $plan = $request->input('plan');
        $months = (int) $request->input('months');
        $amount = (int) config("safarichat_billing.plans.{$plan}.price", 0) * $months;

        $start = now();
        if ($account->subscription_plan === 'trial' && $account->trial_ends_at && $account->trial_ends_at->isFuture()) {
            $start = $account->trial_ends_at->copy();
        } elseif ($account->subscription_plan !== 'trial' && $account->subscription_status === 'active'
            && $account->subscription_expires_at && $account->subscription_expires_at->isFuture()) {
            $start = $account->subscription_expires_at->copy();
        }
        $end = $start->copy()->addDays(30 * $months)->endOfDay();

        $before = $this->snapshot($account);

        DB::transaction(function () use ($account, $plan, $cycleId, $end, $amount, $reference) {
            $account->forceFill([
                'subscription_status' => 'active',
                'subscription_plan' => $plan,
                'billing_cycle_id' => $cycleId,
                'subscription_started_at' => $account->subscription_plan === $plan && $account->subscription_started_at ? $account->subscription_started_at : now(),
                'subscription_expires_at' => $end,
                'last_payment_at' => now(),
                'last_payment_amount' => $amount,
                'last_transaction_id' => $reference,
            ]);
            // Sets the plan limit columns and saves everything in one write (one trigger run).
            $account->syncLimitsFromPlan();
        });
        AccountSuspensionService::forgetBusiness($businessId);

        Log::info('Admin panel: payment recorded', [
            'business_id' => $businessId,
            'admin' => $request->input('admin'),
            'method' => $request->input('method'),
            'reference' => $reference,
            'amount' => $amount,
            'period' => [$start->toDateString(), $end->toDateString()],
            'before' => $before,
            'after' => $this->snapshot($account),
        ]);

        return response()->json([
            'success' => true,
            'before' => $before,
            'after' => $this->snapshot($account->refresh()),
            'payment' => [
                'plan' => $plan, 'months' => $months, 'amount' => $amount, 'method' => $request->input('method'),
                'reference' => $reference, 'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(),
            ],
        ]);
    }

    /**
     * POST /api/admin-panel/settings/onboarding-video
     *
     * The demo video every client sees on their dashboard home (OnboardingVideo). video_id is
     * the 11-character YouTube ID; the admin panel extracts it from whatever link was pasted.
     */
    public function onboardingVideo(Request $request)
    {
        $v = Validator::make($request->all(), [
            'video_id' => ['required', 'string', 'regex:/^[\w-]{11}$/'],
            'title' => 'required|string|max:120',
            'visible' => 'required|boolean',
            'admin' => 'required|string|max:255',
        ], ['video_id.regex' => 'That is not a valid YouTube video.']);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
        }

        $before = OnboardingVideo::settings();
        OnboardingVideo::save($request->input('video_id'), $request->input('title'), $request->boolean('visible'));
        $after = OnboardingVideo::settings();

        Log::info('Admin panel: onboarding video changed', ['admin' => $request->input('admin'), 'before' => $before, 'after' => $after]);

        return response()->json(['success' => true, 'before' => $before, 'after' => $after]);
    }

    /** POST /api/admin-panel/businesses/{businessId}/suspend */
    public function suspend(Request $request, int $businessId)
    {
        return $this->setStatus($request, $businessId, 'suspended');
    }

    /** POST /api/admin-panel/businesses/{businessId}/activate */
    public function activate(Request $request, int $businessId)
    {
        return $this->setStatus($request, $businessId, 'active');
    }

    private function setStatus(Request $request, int $businessId, string $status)
    {
        if (!$request->filled('admin')) {
            return response()->json(['success' => false, 'message' => 'admin is required'], 422);
        }

        $account = BillingAccount::where('business_id', $businessId)->first();
        if (!$account) {
            return response()->json(['success' => false, 'message' => 'This business has no billing account'], 404);
        }

        $before = $this->snapshot($account);
        // `status` is not a credit field, so the credits trigger leaves this update alone.
        $account->update(['status' => $status]);
        AccountSuspensionService::forgetBusiness($businessId);

        Log::info("Admin panel set billing account status to {$status}", [
            'business_id' => $businessId,
            'admin' => $request->input('admin'),
            'note' => $request->input('note'),
        ]);

        return response()->json(['success' => true, 'before' => $before, 'after' => $this->snapshot($account->refresh())]);
    }

    private function snapshot(BillingAccount $account): array
    {
        return [
            'status' => $account->status,
            'subscription_plan' => $account->subscription_plan,
            'subscription_status' => $account->subscription_status,
            'subscription_expires_at' => optional($account->subscription_expires_at)->toDateTimeString(),
            'base_credits' => $account->base_credits,
            'topup_credits' => $account->topup_credits,
            'ai_credits_used' => $account->ai_credits_used,
            'available_credits' => $account->available_credits,
        ];
    }
}
