<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class BillingController extends Controller
{
    /**
     * Show payment page for plan upgrade
     */
    public function showPayment(Request $request)
    {
        $planCode = $request->input('plan_code');
        $amount = $request->input('amount');
        $feature = $request->input('feature');

        // Validate required parameters
        if (!$planCode || !$amount) {
            return redirect()->route('ai-agents.index')->with('error', 'Invalid payment parameters');
        }

        // Validate plan code
        $validPlans = ['starter', 'pro', 'premium'];
        if (!in_array($planCode, $validPlans)) {
            return redirect()->route('ai-agents.index')->with('error', 'Invalid plan selected');
        }

        return view('billing.payment', [
            'plan_code' => $planCode,
            'amount' => $amount,
            'feature' => $feature
        ]);
    }

    /**
     * Process payment based on selected method
     */
    public function processPayment(Request $request)
    {
        $user = Auth::user();
        $planCode = $request->input('plan_code');
        $amount = $request->input('amount');
        $paymentMethod = $request->input('payment_method');
        $feature = $request->input('feature');

        try {
            // Validate inputs
            $validPlans = ['starter', 'pro', 'premium'];
            $validMethods = ['ucn', 'stripe', 'flutterwave'];

            if (!in_array($planCode, $validPlans)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid plan selected'
                ], 400);
            }

            if (!in_array($paymentMethod, $validMethods)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payment method selected'
                ], 400);
            }

            // Check if this is actually an upgrade
            $billingAccount = $user->business->billingAccount ?? null;
            $currentPlan = $billingAccount ? ($billingAccount->subscription_plan ?? 'trial') : 'trial';
            $planHierarchy = ['trial' => 0, 'starter' => 1, 'pro' => 2, 'premium' => 3];
            
            if ($planHierarchy[$planCode] <= $planHierarchy[$currentPlan]) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected plan is not an upgrade from your current plan'
                ], 400);
            }

            // Process payment based on method
            switch ($paymentMethod) {
                case 'ucn':
                    return $this->processUCNPayment($user, $planCode, $amount, $feature);
                
                case 'stripe':
                    return $this->processStripePayment($user, $planCode, $amount, $feature);
                
                case 'flutterwave':
                    return $this->processFlutterwavePayment($user, $planCode, $amount, $feature);
                
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment method not implemented yet'
                    ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'user_id' => $user->id,
                'plan_code' => $planCode,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Process UCN payment
     */
    private function processUCNPayment($user, $planCode, $amount, $feature)
    {
        // For now, create a pending payment record and show UCN payment instructions
        $paymentReference = 'UCN_' . time() . '_' . $user->id;
        
        // Store payment intent in database
        DB::table('payment_intents')->insert([
            'user_id' => $user->id,
            'plan_code' => $planCode,
            'amount' => $amount,
            'payment_method' => 'ucn',
            'payment_reference' => $paymentReference,
            'feature' => $feature,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Log::info('UCN payment initiated', [
            'user_id' => $user->id,
            'plan_code' => $planCode,
            'amount' => $amount,
            'reference' => $paymentReference
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated successfully',
            'redirect_url' => route('billing.ucn-instructions', ['reference' => $paymentReference])
        ]);
    }

    /**
     * Process Stripe payment
     */
    private function processStripePayment($user, $planCode, $amount, $feature)
    {
        try {
            // Check if Stripe is configured
            $stripeKey = config('services.stripe.secret');
            if (empty($stripeKey)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe payment gateway is not configured. Please contact support or use another payment method.'
                ], 400);
            }

            Stripe::setApiKey($stripeKey);

            // Create payment intent record
            $paymentReference = 'STRIPE_' . time() . '_' . $user->id;
            DB::table('payment_intents')->insert([
                'user_id' => $user->id,
                'plan_code' => $planCode,
                'amount' => $amount,
                'payment_method' => 'stripe',
                'payment_reference' => $paymentReference,
                'feature' => $feature,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Get plan details
            $planNames = [
                'starter' => 'SafariChat Starter Plan',
                'pro' => 'SafariChat Pro Plan',
                'premium' => 'SafariChat Premium Plan'
            ];

            // Create Stripe Checkout Session
            $session = StripeSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => config('services.stripe.currency', 'usd'),
                        'product_data' => [
                            'name' => $planNames[$planCode] ?? 'SafariChat Plan Upgrade',
                            'description' => 'Upgrade to ' . ucfirst($planCode) . ' plan',
                        ],
                        'unit_amount' => (int)($amount * 100), // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('billing.stripe.success') . '?session_id={CHECKOUT_SESSION_ID}&reference=' . $paymentReference,
                'cancel_url' => route('billing.cancel') . '?plan=' . $planCode,
                'client_reference_id' => $paymentReference,
                'metadata' => [
                    'user_id' => $user->id,
                    'plan_code' => $planCode,
                    'feature' => $feature,
                    'reference' => $paymentReference
                ]
            ]);

            Log::info('Stripe checkout session created', [
                'user_id' => $user->id,
                'session_id' => $session->id,
                'reference' => $paymentReference
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Redirecting to Stripe payment...',
                'redirect_url' => $session->url
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe payment creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initialize Stripe payment. Please try again or contact support.'
            ], 500);
        }
    }

    /**
     * Process Flutterwave payment
     */
    private function processFlutterwavePayment($user, $planCode, $amount, $feature)
    {
        // Flutterwave is not configured
        Log::warning('Flutterwave payment attempted but not configured', [
            'user_id' => $user->id,
            'plan_code' => $planCode
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Flutterwave payment gateway is not available at the moment. Please use Stripe, UCN, or contact support for alternative payment methods.'
        ], 400);
    }

    /**
     * Complete the plan upgrade after successful payment
     */
    private function completeUpgrade($user, $planCode, $feature)
    {
        // Get billing account
        $billingAccount = $user->business->billingAccount ?? null;
        
        if (!$billingAccount) {
            Log::error('Billing account not found for user', ['user_id' => $user->id]);
            throw new \Exception('Billing account not found. Please contact support.');
        }

        $currentPlan = $billingAccount->subscription_plan ?? 'trial';

        // Update billing account with new subscription
        $billingAccount->update([
            'subscription_plan' => $planCode,
            'subscription_expires_at' => now()->addMonth(),
            'status' => 'active'
        ]);

        // Get plan limits from config and update credits
        $planConfig = config("safarichat_billing.plans.{$planCode}");
        if ($planConfig && isset($planConfig['limits']['ai_credits'])) {
            $newCredits = $planConfig['limits']['ai_credits'];
            $billingAccount->update([
                'ai_credits' => DB::raw("ai_credits + {$newCredits}")
            ]);
        }

        Log::info('Plan upgraded successfully', [
            'user_id' => $user->id,
            'old_plan' => $currentPlan,
            'new_plan' => $planCode,
            'feature_requested' => $feature
        ]);
    }

    /**
     * Handle Stripe payment success callback
     */
    public function stripeSuccess(Request $request)
    {
        try {
            $sessionId = $request->input('session_id');
            $reference = $request->input('reference');

            if (!$sessionId || !$reference) {
                Log::error('Missing Stripe session parameters', $request->all());
                return redirect()->route('billing.cancel')->with('error', 'Invalid payment session');
            }

            // Verify payment intent
            $paymentIntent = DB::table('payment_intents')
                ->where('payment_reference', $reference)
                ->first();

            if (!$paymentIntent) {
                Log::error('Payment intent not found', ['reference' => $reference]);
                return redirect()->route('billing.cancel')->with('error', 'Payment record not found');
            }

            // Check if already processed
            if ($paymentIntent->status === 'completed') {
                return redirect()->route('billing.success', ['plan' => $paymentIntent->plan_code])
                    ->with('info', 'Payment already processed');
            }

            // Verify the session with Stripe
            Stripe::setApiKey(config('services.stripe.secret'));
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status !== 'paid') {
                Log::error('Stripe payment not completed', [
                    'session_id' => $sessionId,
                    'payment_status' => $session->payment_status
                ]);
                return redirect()->route('billing.cancel')->with('error', 'Payment was not completed');
            }

            // Get user
            $user = \App\Models\User::find($paymentIntent->user_id);
            if (!$user) {
                Log::error('User not found for payment', ['user_id' => $paymentIntent->user_id]);
                return redirect()->route('billing.cancel')->with('error', 'User not found');
            }

            // Complete the upgrade
            $this->completeUpgrade($user, $paymentIntent->plan_code, $paymentIntent->feature);

            // Update payment intent status
            DB::table('payment_intents')
                ->where('payment_reference', $reference)
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'updated_at' => now(),
                    'payment_data' => json_encode([
                        'stripe_session_id' => $sessionId,
                        'payment_status' => $session->payment_status,
                        'amount_total' => $session->amount_total
                    ])
                ]);

            Log::info('Stripe payment completed successfully', [
                'user_id' => $user->id,
                'reference' => $reference,
                'plan_code' => $paymentIntent->plan_code
            ]);

            return redirect()->route('billing.success', ['plan' => $paymentIntent->plan_code])
                ->with('success', 'Payment completed successfully!');

        } catch (\Exception $e) {
            Log::error('Stripe success callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('billing.cancel')
                ->with('error', 'Payment verification failed. Please contact support with reference: ' . ($reference ?? 'N/A'));
        }
    }

    /**
     * Show payment success page
     */
    public function paymentSuccess(Request $request)
    {
        $plan = $request->input('plan', 'Unknown');
        return view('billing.success', ['plan' => $plan]);
    }

    /**
     * Show payment cancelled page
     */
    public function paymentCancel(Request $request)
    {
        return view('billing.cancel');
    }

    /**
     * Show UCN payment instructions
     */
    public function showUCNInstructions(Request $request, $reference)
    {
        $paymentIntent = DB::table('payment_intents')
            ->where('payment_reference', $reference)
            ->where('user_id', Auth::id())
            ->first();

        if (!$paymentIntent) {
            return redirect()->route('ai-agents.index')->with('error', 'Payment reference not found');
        }

        return view('billing.ucn-instructions', [
            'payment_intent' => $paymentIntent,
            'reference' => $reference
        ]);
    }

    /**
     * Show wallet management page
     */
    public function showWallet(Request $request)
    {
        $user = Auth::user();
        $billingAccount = $user->billingAccount;

        return view('billing.wallet', [
            'subscription_plan' => $billingAccount ? $billingAccount->subscription_plan : 'trial',
            'subscription_expires_at' => $billingAccount ? $billingAccount->subscription_expires_at : null,
            'ai_credits' => $billingAccount ? $billingAccount->ai_credits : 0
        ]);
    }
}