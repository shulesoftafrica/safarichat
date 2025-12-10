<?php

namespace App\Http\Controllers;

use App\Services\PaymentGatewayService;
use App\Services\SubscriptionService;
use App\Services\CreditService;
use App\Services\SubscriptionNotificationService;
use App\Models\AdminBooking;
use App\Models\AdminPayment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $paymentService,
        protected SubscriptionService $subscriptionService,
        protected CreditService $creditService,
        protected SubscriptionNotificationService $notificationService
    ) {}

    /**
     * Handle Lipa Namba webhook
     */
    public function lipaNamba(Request $request)
    {
        Log::info('Lipa Namba webhook received', $request->all());

        try {
            $payload = $request->all();
            
            // Validate required fields
            if (!isset($payload['reference'], $payload['amount'], $payload['status'])) {
                Log::error('Lipa Namba webhook missing required fields', $payload);
                return response()->json(['error' => 'Missing required fields'], 400);
            }

            // Find the booking
            $booking = AdminBooking::where('reference', $payload['reference'])->first();
            
            if (!$booking) {
                Log::warning('Lipa Namba webhook: Booking not found', ['reference' => $payload['reference']]);
                return response()->json(['error' => 'Booking not found'], 404);
            }

            $user = $booking->user;
            $package = $booking->adminPackage;

            // Create payment record
            $payment = AdminPayment::create([
                'user_id' => $user->id,
                'amount' => $payload['amount'],
                'transaction_id' => $payload['transaction_id'] ?? $payload['reference'],
                'payment_gateway' => 'lipa_number',
                'gateway_reference' => $payload['reference'],
                'status' => $payload['status'] === 'completed' ? 'completed' : 'failed'
            ]);

            if ($payload['status'] === 'completed') {
                $this->processSuccessfulPayment($user, $payment, $package, $payload['amount']);
            }

            // Update booking status
            $booking->update(['status' => $payload['status']]);

            return response()->json(['message' => 'Webhook processed successfully']);

        } catch (\Exception $e) {
            Log::error('Lipa Namba webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function stripe(Request $request)
    {
        Log::info('Stripe webhook received', ['type' => $request->input('type')]);

        try {
            $payload = $request->all();
            $event = $payload['type'];
            $data = $payload['data']['object'];

            switch ($event) {
                case 'checkout.session.completed':
                    return $this->handleStripeCheckoutCompleted($data);
                case 'payment_intent.succeeded':
                    return $this->handleStripePaymentSucceeded($data);
                case 'invoice.payment_succeeded':
                    return $this->handleStripeInvoicePaymentSucceeded($data);
                default:
                    Log::info('Unhandled Stripe webhook event', ['type' => $event]);
                    return response()->json(['message' => 'Event ignored']);
            }

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);
            
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle successful Stripe checkout session
     */
    private function handleStripeCheckoutCompleted(array $data): \Illuminate\Http\JsonResponse
    {
        $userId = $data['metadata']['user_id'] ?? null;
        
        if (!$userId) {
            Log::error('Stripe checkout: No user ID in metadata', $data);
            return response()->json(['error' => 'No user ID'], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('Stripe checkout: User not found', ['user_id' => $userId]);
            return response()->json(['error' => 'User not found'], 404);
        }

        // Create payment record
        $payment = AdminPayment::create([
            'user_id' => $user->id,
            'amount' => $data['amount_total'] / 100, // Convert from cents
            'transaction_id' => $data['payment_intent'],
            'payment_gateway' => 'stripe',
            'gateway_reference' => $data['id'],
            'status' => 'completed'
        ]);

        // Find the package (could be stored in booking or metadata)
        $package = $this->findPackageForUser($user);
        
        $this->processSuccessfulPayment($user, $payment, $package, $payment->amount);

        return response()->json(['message' => 'Checkout processed successfully']);
    }

    /**
     * Handle successful Stripe payment intent
     */
    private function handleStripePaymentSucceeded(array $data): \Illuminate\Http\JsonResponse
    {
        $userId = $data['metadata']['user_id'] ?? null;
        
        if (!$userId) {
            return response()->json(['message' => 'No user metadata']);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Check if payment already processed
        $existingPayment = AdminPayment::where('transaction_id', $data['id'])->first();
        if ($existingPayment) {
            return response()->json(['message' => 'Payment already processed']);
        }

        $payment = AdminPayment::create([
            'user_id' => $user->id,
            'amount' => $data['amount'] / 100,
            'transaction_id' => $data['id'],
            'payment_gateway' => 'stripe',
            'gateway_reference' => $data['id'],
            'status' => 'completed'
        ]);

        $package = $this->findPackageForUser($user);
        $this->processSuccessfulPayment($user, $payment, $package, $payment->amount);

        return response()->json(['message' => 'Payment processed successfully']);
    }

    /**
     * Handle Stripe invoice payment (for subscriptions)
     */
    private function handleStripeInvoicePaymentSucceeded(array $data): \Illuminate\Http\JsonResponse
    {
        // Handle recurring subscription payments
        Log::info('Stripe subscription payment received', $data);
        
        return response()->json(['message' => 'Subscription payment processed']);
    }

    /**
     * Process successful payment
     */
    private function processSuccessfulPayment(User $user, AdminPayment $payment, $package, float $amount): void
    {
        try {
            // Calculate credits from excess payment
            $creditData = $this->creditService->calculateCreditsFromPayment($amount, $package);
            
            // Update payment with credit information
            $payment->update([
                'credit_amount' => $creditData['credits'],
                'subscription_months' => 1,
                'excess_amount' => $creditData['excess_amount']
            ]);

            // Activate subscription
            $this->subscriptionService->activateSubscription($user, $payment);
            
            // Add credits if excess payment
            if ($creditData['credits'] > 0) {
                $this->creditService->addCredits(
                    $user, 
                    $creditData['credits'], 
                    'payment_excess', 
                    $payment
                );
            }

            // Send payment confirmation
            $this->notificationService->sendPaymentConfirmation($user, $payment);

            Log::info('Payment processed successfully', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'amount' => $amount,
                'credits_added' => $creditData['credits']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process successful payment', [
                'user_id' => $user->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Find appropriate package for user
     */
    private function findPackageForUser(User $user)
    {
        // Try to find recent booking
        $recentBooking = AdminBooking::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subHours(2))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($recentBooking) {
            return $recentBooking->adminPackage;
        }

        // Default to Winga package
        return \App\Models\AdminPackage::where('package_type', 'winga')->first();
    }
}
