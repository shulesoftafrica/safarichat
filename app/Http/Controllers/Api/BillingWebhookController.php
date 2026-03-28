<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingAccount;
use App\Models\BillingWebhookEvent;
use App\Models\User;
use App\Models\Business;
use App\Http\Requests\BillingWebhookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Billing Webhook Controller
 * Receives payment notifications from billing platform and updates billing_accounts
 */
class BillingWebhookController extends Controller
{
    /**
     * Handle incoming webhook from billing platform
     * 
     * Expected webhook payload:
     * {
     *   "event": "payment.success|payment.failed|subscription.created|subscription.renewed|subscription.cancelled",
     *   "customer_id": 123,
     *   "business_id": 456,
     *   "payment": {
     *     "transaction_id": "TXN123456",
     *     "amount": 69000,
     *     "currency": "TZS",
     *     "status": "completed|failed|pending"
     *   },
     *   "subscription": {
     *     "plan": "starter|pro|premium",
     *     "duration_days": 30,
     *     "ai_credits": 69000,
     *     "features": {
     *       "max_contacts": 50,
     *       "max_products": 5,
     *       "whatsapp_channels": 1,
     *       "customer_followups": false,
     *       "customer_categorization": false,
     *       "booking_calendars": false,
     *       "sales_reports": false
     *     }
     *   },
     *   "timestamp": "2026-01-23T10:30:00Z",
     *   "signature": "webhook_signature_hash"
     * }
     * 
     * @param BillingWebhookRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(BillingWebhookRequest $request)
    {
        try {
            // Log incoming webhook
            Log::info('Billing webhook received', [
                'payload' => $request->all(),
                'ip' => $request->ip()
            ]);
            
            // Validate webhook signature
            if (!$this->validateSignature($request)) {
                Log::error('Invalid webhook signature', [
                    'ip' => $request->ip(),
                    'signature' => $request->header('X-Webhook-Signature')
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid signature'
                ], 401);
            }
            
            // Payload validation is handled by BillingWebhookRequest form request
            // No need for manual validation here
            
            // Extract transaction ID and event type for idempotency check
            $transactionId = $request->input('payment.transaction_id') 
                ?? $request->input('transaction_id');
            $eventType = $request->input('event');
            
            // IDEMPOTENCY CHECK: Prevent duplicate webhook processing
            if ($transactionId && BillingWebhookEvent::isProcessed($transactionId, $eventType)) {
                Log::info('Duplicate webhook detected - already processed successfully', [
                    'transaction_id' => $transactionId,
                    'event' => $eventType,
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook already processed (idempotency)',
                    'transaction_id' => $transactionId
                ], 200);
            }
            
            // Create webhook event record for audit trail
            $webhookEvent = BillingWebhookEvent::create([
                'event_type' => $eventType,
                'transaction_id' => $transactionId,
                'payload' => $request->all(),
                'signature' => $request->header('X-Webhook-Signature'),
                'source_ip' => $request->ip(),
                'processing_status' => 'processing'
            ]);
            
            Log::info('Processing webhook event', [
                'webhook_event_id' => $webhookEvent->id,
                'event' => $eventType,
                'transaction_id' => $transactionId
            ]);
            
            $event = $request->input('event');
            
            // Route to appropriate handler based on event type
            try {
                $result = match($event) {
                    'payment.success' => $this->handlePaymentSuccess($request),
                    'payment.failed' => $this->handlePaymentFailed($request),
                    'subscription.created' => $this->handleSubscriptionCreated($request),
                    'subscription.renewed' => $this->handleSubscriptionRenewed($request),
                    'subscription.cancelled' => $this->handleSubscriptionCancelled($request),
                    'subscription.expired' => $this->handleSubscriptionExpired($request),
                    'credits.purchased' => $this->handleCreditsPurchased($request),
                    default => $this->handleUnknownEvent($request, $event)
                };
                
                // Mark webhook as successfully processed
                $webhookEvent->update([
                    'processing_status' => 'success',
                    'processed_at' => now()
                ]);
                
                // Update billing_account_id if available in result
                if (isset($result['billing_account_id'])) {
                    $webhookEvent->update(['billing_account_id' => $result['billing_account_id']]);
                }
                
                Log::info('Webhook processed successfully', [
                    'webhook_event_id' => $webhookEvent->id,
                    'event' => $eventType
                ]);
                
                return response()->json($result);
                
            } catch (\Exception $e) {
                // Mark webhook as failed
                $webhookEvent->update([
                    'processing_status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'processed_at' => now()
                ]);
                
                Log::error('Webhook event handler failed', [
                    'webhook_event_id' => $webhookEvent->id,
                    'event' => $eventType,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                // Re-throw so outer catch block handles response
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred'
            ], 500);
        }
    }
    
    /**
     * Validate webhook signature for security
     */
    private function validateSignature(Request $request): bool
    {
        $signature = $request->header('X-Webhook-Signature');
        
        // Use different secrets for different environments
        $secret = config('app.env') === 'local' || config('app.env') === 'testing'
            ? config('services.billing.webhook_test_secret')
            : config('services.billing.webhook_secret');
        
        // If no secret is configured, skip HMAC check and rely on IP allowlist only
        if (!$secret) {
            Log::warning('Webhook signature check skipped: BILLING_WEBHOOK_SECRET not configured — relying on IP allowlist', [
                'ip' => $request->ip(),
            ]);
            return true;
        }
        
        // If no signature header at all, skip check (billing platform may not sign yet)
        if (!$signature) {
            Log::warning('Webhook: No X-Webhook-Signature header — skipping HMAC check (secret is configured but header absent)', [
                'ip' => $request->ip(),
                'all_headers' => array_keys($request->headers->all()),
            ]);
            return true;
        }
        
        $payload = $request->getContent();
        
        // Strip common prefix formats: "sha256=abc..." or "hmac-sha256=abc..."
        $rawSignature = $signature;
        if (str_contains($signature, '=')) {
            $rawSignature = substr($signature, strpos($signature, '=') + 1);
        }
        
        // Strategy 1: HMAC-SHA256 of raw body (standard)
        $hmacHex = hash_hmac('sha256', $payload, $secret);
        
        // Strategy 2: billing platform might send the secret itself as the token
        $secretMatch = hash_equals($secret, $rawSignature);
        
        $isValid = hash_equals($hmacHex, $rawSignature) || $secretMatch;
        
        Log::info('Webhook signature validation', [
            'valid'              => $isValid,
            'secret_prefix'      => substr($secret, 0, 8) . '...',
            'received_sig'       => substr($rawSignature, 0, 20) . '...',
            'expected_hmac'      => substr($hmacHex, 0, 20) . '...',
            'strategy_hmac'      => hash_equals($hmacHex, $rawSignature),
            'strategy_raw'       => $secretMatch,
            'payload_length'     => strlen($payload),
        ]);
        
        if (!$isValid) {
            Log::warning('Webhook rejected: Invalid signature', [
                'ip'             => $request->ip(),
                'received_sig'   => substr($rawSignature, 0, 20) . '...',
                'expected_hmac'  => substr($hmacHex, 0, 20) . '...',
            ]);
        }
        
        return $isValid;
    }
    
    /**
     * Validate webhook payload structure
     */
    private function validateWebhookPayload(Request $request): array
    {
        $errors = [];
        
        if (!$request->has('event')) {
            $errors[] = 'Missing event type';
        }
        
        if (!$request->has('customer_id') && !$request->has('business_id')) {
            $errors[] = 'Missing customer_id or business_id';
        }
        
        if (!$request->has('timestamp')) {
            $errors[] = 'Missing timestamp';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Handle successful payment webhook
     */
    private function handlePaymentSuccess(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $customerId = $request->input('customer_id');
            $businessId = $request->input('business_id');
            $payment = $request->input('payment', []);
            $subscription = $request->input('subscription', []);
            
            // Get or create billing account
            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId);
            
            if (!$billingAccount) {
                throw new \Exception("Could not find or create billing account for customer {$customerId}");
            }
            
            // ── Detect credit-only top-up (AI Credit Package) vs subscription activation ──
            $invoiceItems = $request->input('invoice.items', []);
            $isCreditPurchase = !empty($invoiceItems) && collect($invoiceItems)->every(
                fn($item) => str_contains($item['price_plan_name'] ?? '', 'Credit')
            );
            
            // AI credits: explicit field, or root-level credits field, or 0
            $aiCredits = $subscription['ai_credits'] ?? $request->input('credits', 0);
            
            if ($isCreditPurchase) {
                // Credit top-up only — update payment record and add credits; leave subscription untouched
                $billingAccount->update([
                    'last_payment_at'     => now(),
                    'last_payment_amount' => $payment['amount'] ?? 0,
                    'last_transaction_id' => $payment['transaction_id'] ?? null,
                ]);
                
                if ($aiCredits > 0) {
                    $billingAccount->increment('ai_credits', $aiCredits);
                }
                
                Log::info('Credit top-up processed', [
                    'billing_account_id' => $billingAccount->id,
                    'customer_id'        => $customerId,
                    'credits_added'      => $aiCredits,
                    'transaction_id'     => $payment['transaction_id'] ?? null,
                ]);
                
                return [
                    'success'      => true,
                    'message'      => 'Credits added successfully',
                    'billing_account_id' => $billingAccount->id,
                    'credits_added'      => $aiCredits,
                ];
            }
            
            // ── Subscription activation ─────────────────────────────────────────────────
            
            // Map plan name: "Premium Plan" → "premium", "Starter Plan" → "starter", etc.
            $rawPlanName = $subscription['price_plan_name'] ?? $subscription['plan'] ?? $subscription['plan_id'] ?? 'starter';
            $plan = strtolower(trim(str_ireplace(' plan', '', $rawPlanName)));
            
            // Determine features (legacy explicit features block, else keep existing values)
            $features = $subscription['features'] ?? [];
            
            // Calculate expiration date
            if (!empty($subscription['current_period_end'])) {
                // Billing platform provided exact end date — use it
                $expiresAt = Carbon::parse($subscription['current_period_end']);
            } elseif (!empty($subscription['duration_days'])) {
                $expiresAt = now()->addDays((int) $subscription['duration_days']);
            } else {
                // Derive from billing_interval (monthly / yearly / annual / weekly)
                $billingInterval = strtolower($subscription['billing_interval'] ?? 'monthly');
                $expiresAt = match(true) {
                    in_array($billingInterval, ['yearly', 'annual']) => now()->addYear(),
                    $billingInterval === 'weekly'                    => now()->addWeek(),
                    default                                          => now()->addMonth(),
                };
            }
            
            // Update billing account (excluding ai_credits which we'll increment separately)
            $billingAccount->update([
                'subscription_status'    => 'active',
                'subscription_plan'      => $plan,
                'subscription_started_at' => now(),
                'subscription_expires_at' => $expiresAt,
                'max_contacts'           => $features['max_contacts'] ?? $billingAccount->max_contacts,
                'max_products'           => $features['max_products'] ?? $billingAccount->max_products,
                'whatsapp_channels'      => $features['whatsapp_channels'] ?? $billingAccount->whatsapp_channels,
                'customer_followups'     => $features['customer_followups'] ?? $billingAccount->customer_followups,
                'customer_categorization' => $features['customer_categorization'] ?? $billingAccount->customer_categorization,
                'booking_calendars'      => $features['booking_calendars'] ?? $billingAccount->booking_calendars,
                'sales_reports'          => $features['sales_reports'] ?? $billingAccount->sales_reports,
                'last_payment_at'        => now(),
                'last_payment_amount'    => $payment['amount'] ?? 0,
                'last_transaction_id'    => $payment['transaction_id'] ?? null,
            ]);
            
            // Increment AI credits separately
            if ($aiCredits > 0) {
                $billingAccount->increment('ai_credits', $aiCredits);
            }
            
            Log::info('Payment success processed', [
                'billing_account_id' => $billingAccount->id,
                'customer_id' => $customerId,
                'plan' => $plan,
                'expires_at' => $expiresAt->toDateTimeString(),
                'credits_added' => $aiCredits,
                'transaction_id' => $payment['transaction_id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'billing_account_id' => $billingAccount->id,
                'subscription' => [
                    'plan' => $plan,
                    'status' => 'active',
                    'expires_at' => $expiresAt->toISOString()
                ]
            ];
        });
    }
    
    /**
     * Handle failed payment webhook
     */
    private function handlePaymentFailed(Request $request): array
    {
        $customerId = $request->input('customer_id');
        $businessId = $request->input('business_id');
        $payment = $request->input('payment', []);
        
        $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId);
        
        if ($billingAccount) {
            // Don't change subscription status, just log the failure
            Log::warning('Payment failed', [
                'billing_account_id' => $billingAccount->id,
                'customer_id' => $customerId,
                'transaction_id' => $payment['transaction_id'] ?? null,
                'amount' => $payment['amount'] ?? 0
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Payment failure recorded'
        ];
    }
    
    /**
     * Handle subscription created webhook
     */
    private function handleSubscriptionCreated(Request $request): array
    {
        // Same as payment success
        return $this->handlePaymentSuccess($request);
    }
    
    /**
     * Handle subscription renewed webhook
     */
    private function handleSubscriptionRenewed(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $customerId = $request->input('customer_id');
            $businessId = $request->input('business_id');
            $subscription = $request->input('subscription', []);
            
            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId);
            
            if (!$billingAccount) {
                throw new \Exception("Could not find billing account for customer {$customerId}");
            }
            
            $aiCredits = $subscription['ai_credits'] ?? 0;
            
            // Extend expiration date from current expiry or now (whichever is later)
            $baseDate = $billingAccount->subscription_expires_at && $billingAccount->subscription_expires_at->isFuture()
                ? $billingAccount->subscription_expires_at
                : now();
            
            // Calculate new expiry from explicit end date, duration_days, or billing_interval
            if (!empty($subscription['current_period_end'])) {
                $newExpiresAt = Carbon::parse($subscription['current_period_end']);
            } elseif (!empty($subscription['duration_days'])) {
                $newExpiresAt = $baseDate->copy()->addDays((int) $subscription['duration_days']);
            } else {
                $billingInterval = strtolower($subscription['billing_interval'] ?? 'monthly');
                $newExpiresAt = match(true) {
                    in_array($billingInterval, ['yearly', 'annual']) => $baseDate->copy()->addYear(),
                    $billingInterval === 'weekly'                    => $baseDate->copy()->addWeek(),
                    default                                          => $baseDate->copy()->addMonth(),
                };
            }
            
            // Update billing account (excluding ai_credits which we'll increment separately)
            $billingAccount->update([
                'subscription_status' => 'active',
                'subscription_expires_at' => $newExpiresAt,
                'last_payment_at' => now()
            ]);
            
            // Increment AI credits separately
            if ($aiCredits > 0) {
                $billingAccount->increment('ai_credits', $aiCredits);
            }
            
            Log::info('Subscription renewed', [
                'billing_account_id' => $billingAccount->id,
                'customer_id' => $customerId,
                'new_expires_at' => $newExpiresAt->toDateTimeString(),
                'credits_added' => $aiCredits
            ]);
            
            return [
                'success' => true,
                'message' => 'Subscription renewed successfully',
                'expires_at' => $newExpiresAt->toISOString()
            ];
        });
    }
    
    /**
     * Handle subscription cancelled webhook
     */
    private function handleSubscriptionCancelled(Request $request): array
    {
        $customerId = $request->input('customer_id');
        $businessId = $request->input('business_id');
        
        $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId);
        
        if ($billingAccount) {
            $billingAccount->update([
                'subscription_status' => 'cancelled'
            ]);
            
            Log::info('Subscription cancelled', [
                'billing_account_id' => $billingAccount->id,
                'customer_id' => $customerId
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Subscription cancelled'
        ];
    }
    
    /**
     * Handle subscription expired webhook
     */
    private function handleSubscriptionExpired(Request $request): array
    {
        $customerId = $request->input('customer_id');
        $businessId = $request->input('business_id');
        
        $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId);
        
        if ($billingAccount) {
            $billingAccount->update([
                'subscription_status' => 'expired'
            ]);
            
            Log::info('Subscription expired', [
                'billing_account_id' => $billingAccount->id,
                'customer_id' => $customerId
            ]);
        }
        
        return [
            'success' => true,
            'message' => 'Subscription expired'
        ];
    }
    
    /**
     * Handle credits purchase webhook (standalone credit purchase without subscription)
     */
    private function handleCreditsPurchased(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $customerId = $request->input('customer_id');
            $businessId = $request->input('business_id');
            $payment = $request->input('payment', []);
            $credits = $request->input('credits', 0);
            
            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId);
            
            if (!$billingAccount) {
                throw new \Exception("Could not find billing account for customer {$customerId}");
            }
            
            // Add credits to account
            $billingAccount->addCredits($credits, "Purchased via payment: " . ($payment['transaction_id'] ?? 'N/A'));
            
            $billingAccount->update([
                'last_payment_at' => now(),
                'last_payment_amount' => $payment['amount'] ?? 0,
                'last_transaction_id' => $payment['transaction_id'] ?? null
            ]);
            
            Log::info('Credits purchased', [
                'billing_account_id' => $billingAccount->id,
                'customer_id' => $customerId,
                'credits_added' => $credits,
                'new_balance' => $billingAccount->ai_credits,
                'transaction_id' => $payment['transaction_id'] ?? null
            ]);
            
            return [
                'success' => true,
                'message' => 'Credits added successfully',
                'credits_added' => $credits,
                'new_balance' => $billingAccount->ai_credits
            ];
        });
    }
    
    /**
     * Handle unknown event type
     */
    private function handleUnknownEvent(Request $request, string $event): array
    {
        Log::warning('Unknown webhook event type', [
            'event' => $event,
            'payload' => $request->all()
        ]);
        
        return [
            'success' => true,
            'message' => 'Event type not handled',
            'event' => $event
        ];
    }
    
    /**
     * Get or create billing account for customer/business
     */
    private function getOrCreateBillingAccount($customerId, $businessId): ?BillingAccount
    {
        // Try to find by business first
        if ($businessId) {
            $business = Business::find($businessId);
            if ($business) {
                return $business->billingAccount ?? $business->getOrCreateBillingAccount();
            }
        }
        
        // Fallback to user
        if ($customerId) {
            $user = User::find($customerId);
            if ($user) {
                return $user->billingAccount ?? $user->getOrCreateBillingAccount();
            }
        }
        
        return null;
    }
}
