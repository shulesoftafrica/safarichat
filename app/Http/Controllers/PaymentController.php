<?php

namespace App\Http\Controllers;

use App\Services\PaymentGatewayService;
use App\Services\SubscriptionService;
use App\Services\CreditService;
use App\Models\AdminPackage;
use App\Models\AdminBooking;
use App\Models\AdminPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $paymentService,
        protected SubscriptionService $subscriptionService,
        protected CreditService $creditService
    ) {}

    /**
     * Initialize payment process
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:admin_packages,id',
            'amount' => 'required|numeric|min:0'
        ]);

        $user = Auth::user();
        $package = AdminPackage::findOrFail($request->package_id);
       
        // Check if booking already exists for this user and package
        $existingBooking = AdminBooking::where('user_id', $user->id)
            ->where('admin_package_id', $package->id)
            ->where('status', 0)
            ->first();
        
        if ($existingBooking) {
            $booking = $existingBooking;
        } else {
            // Get exchange rate and calculate USD amount
            $usdAmount = $this->paymentService->convertToUSD($request->amount, $user->country_code ?? 'TZ');
            $exchangeData = $this->paymentService->getExchangeRate($user->country_code === 'TZ' ? 'TZS' : 'TZS', 'USD');
            
            // Create booking record with currency fields
            $booking = AdminBooking::create([
                'user_id' => $user->id,
                'admin_package_id' => $package->id,
                'reference' => 'sub_' . time() . '_' . Str::random(6),
                'amount' => $request->amount,
                'base_currency' => 'TZS',
                'base_amount' => (int) $request->amount,
                'display_currency' => 'USD',
                'display_amount' => $usdAmount,
                'fx_rate' => $exchangeData['rate'],
                'fx_markup' => 1.0, // No markup for now
                'locked_at' => now(),
                'expires_at' => now()->addHours(2), // Payment expires in 2 hours
                'payment_status' => 'pending',
                'status' => 0
            ]);
        }
 
        // Determine payment method based on user's country
        // if ($user->country_code === 'TZ') {
        //     return $this->initializeLipaNamba($user, $booking, $request->amount);
        // } else {
        
        $usd_amount = $booking->display_amount ?? $this->paymentService->convertToUSD($request->amount, $user->country_code);
        return $this->initializeStripe($user, $booking, $usd_amount); // Convert TZS to USD
      //  }
    }

    /**
     * Initialize Lipa Namba payment for Tanzania users
     */
    public function initializeLipaNamba($user, $booking, $amount)
    {
        try {
            $merchantData = $this->paymentService->createLipaNumberMerchant($user, $amount);
            
            return view('payment.lipa-namba', [
                'merchant_id' => $merchantData['merchant_id'],
                'qr_code' => $merchantData['qr_code'],
                'amount' => $amount,
                'user' => $user,
                'booking' => $booking,
                'package' => $booking->adminPackage
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Initialize Stripe payment for international users
     */
    public function initializeStripe($user, $booking, $amount)
    {
        try {
            $sessionData = $this->paymentService->processStripePayment($user, $amount);
            
            return redirect($sessionData['checkout_url']);
        } catch (\Exception $e) {
            return back()->with('error', 'Payment initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful payment
     */
    public function success(Request $request)
    {
        $user = Auth::user();
        
        if ($request->has('session_id')) {
            // Stripe payment success
            return $this->handleStripeSuccess($request);
        } else {
            // Lipa Namba payment success
            return $this->handleLipaNambaSuccess($request);
        }
    }

    /**
     * Handle Stripe payment success
     */
    private function handleStripeSuccess(Request $request)
    {
        $sessionId = $request->session_id;
        
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
            
            $user = Auth::user();
            
            return view('payment.success', [
                'user' => $user,
                'payment_method' => 'Stripe',
                'amount' => $session->amount_total / 100,
                'currency' => strtoupper($session->currency)
            ]);
        } catch (\Exception $e) {
            return redirect()->route('subscription.index')
                ->with('error', 'Payment verification failed');
        }
    }

    /**
     * Handle Lipa Namba payment success
     */
    private function handleLipaNambaSuccess(Request $request)
    {
        $user = Auth::user();
        
        return view('payment.success', [
            'user' => $user,
            'payment_method' => 'Lipa Namba',
            'message' => 'Your payment is being processed. You will receive a confirmation shortly.'
        ]);
    }

    /**
     * Handle payment cancellation
     */
    public function cancel()
    {
        return redirect('/')->with('warning', 'Payment was cancelled');
    }

    /**
     * Check payment status via AJAX
     */
    public function checkStatus(Request $request)
    {
        $user = Auth::user();
        
        // Check if subscription is now active
        $isActive = $this->subscriptionService->isActive($user);
        
        return response()->json([
            'is_active' => $isActive,
            'status' => $user->subscription_status,
            'credit_balance' => $user->available_credits
        ]);
    }

    /**
     * Process credit top-up
     */
    public function topupCredits(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000' // Minimum 1000 TSH
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        
        // Create credit purchase booking
        $package = AdminPackage::where('package_type', 'winga')->first(); // Use base package
        
        $booking = AdminBooking::create([
            'user_id' => $user->id,
            'admin_package_id' => $package->id,
            'reference' => 'credits_' . time() . '_' . Str::random(6),
            'amount' => $amount,
            'status' => 'pending'
        ]);

        if ($user->country_code === 'TZ') {
            return $this->initializeLipaNamba($user, $booking, $amount);
        } else {
            return $this->initializeStripe($user, $booking, $amount);
        }
    }
}
