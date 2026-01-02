<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
            $currentPlan = $user->subscription_plan ?? 'trial';
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
        // TODO: Implement Stripe payment processing
        // For now, simulate immediate success for testing
        $this->completeUpgrade($user, $planCode, $feature);

        return response()->json([
            'success' => true,
            'message' => 'Payment completed successfully (Stripe - Demo Mode)',
            'redirect_url' => route('billing.success', ['plan' => $planCode])
        ]);
    }

    /**
     * Process Flutterwave payment
     */
    private function processFlutterwavePayment($user, $planCode, $amount, $feature)
    {
        // TODO: Implement Flutterwave payment processing
        // For now, simulate immediate success for testing
        $this->completeUpgrade($user, $planCode, $feature);

        return response()->json([
            'success' => true,
            'message' => 'Payment completed successfully (Flutterwave - Demo Mode)',
            'redirect_url' => route('billing.success', ['plan' => $planCode])
        ]);
    }

    /**
     * Complete the plan upgrade after successful payment
     */
    private function completeUpgrade($user, $planCode, $feature)
    {
        $currentPlan = $user->subscription_plan ?? 'trial';

        // Update user subscription
        $user->update([
            'subscription_plan' => $planCode,
            'subscription_expires_at' => now()->addMonth(),
            'subscription_status' => 'active'
        ]);

        // Also update business if exists
        if ($user->business) {
            $user->business->update([
                'subscription_plan' => $planCode,
                'subscription_expires_at' => now()->addMonth()
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
}