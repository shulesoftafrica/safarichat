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
            
            // Extract idempotency key.
            // Primary: event_id (globally unique per billing platform docs).
            // Fallback: payment.transaction_id (for payment events without event_id).
            // Subscription events send payment: null — event_id is the ONLY safe key for those.
            $eventId       = $request->input('event_id');
            $transactionId = $request->input('payment.transaction_id')
                ?? $request->input('transaction_id');
            $idempotencyKey = $eventId ?? $transactionId;
            $eventType = $request->input('event');
            
            // IDEMPOTENCY CHECK: Prevent duplicate webhook processing
            if ($idempotencyKey) {
                $existing = BillingWebhookEvent::where('transaction_id', $idempotencyKey)
                    ->where('event_type', $eventType)
                    ->first();
                
                if ($existing && $existing->processing_status === 'success') {
                    Log::info('Duplicate webhook detected - already processed successfully', [
                        'idempotency_key' => $idempotencyKey,
                        'event'           => $eventType,
                        'ip'              => $request->ip(),
                    ]);
                    return response()->json([
                        'success'         => true,
                        'message'         => 'Webhook already processed (idempotency)',
                        'idempotency_key' => $idempotencyKey,
                    ], 200);
                }
            }
            
            // Upsert webhook event record — handles retries of previously failed deliveries
            // without hitting the unique_transaction_event constraint
            $webhookEvent = BillingWebhookEvent::updateOrCreate(
                [
                    'transaction_id'    => $idempotencyKey,
                    'event_type'        => $eventType,
                ],
                [
                    'payload'           => $request->all(),
                    'signature'         => $request->header('X-Webhook-Signature'),
                    'source_ip'         => $request->ip(),
                    'processing_status' => 'processing',
                    'error_message'     => null,
                    'processed_at'      => null,
                ]
            );
            
            Log::info('Processing webhook event', [
                'webhook_event_id' => $webhookEvent->id,
                'event'            => $eventType,
                'idempotency_key'  => $idempotencyKey,
            ]);
            
            $event = $request->input('event');
            
            // Route to appropriate handler based on event type
            try {
                $result = match($event) {
                    'payment.success'        => $this->handlePaymentSuccess($request),
                    'payment.failed'         => $this->handlePaymentFailed($request),
                    'subscription.created'   => $this->handleSubscriptionCreated($request),
                    'subscription.renewed'   => $this->handleSubscriptionRenewed($request),
                    'subscription.upgraded'  => $this->handleSubscriptionUpgraded($request),
                    'subscription.cancelled' => $this->handleSubscriptionCancelled($request),
                    'subscription.expired'   => $this->handleSubscriptionExpired($request),
                    'credits.purchased'      => $this->handleCreditsPurchased($request),
                    default                  => $this->handleUnknownEvent($request, $event)
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
        
        // Always prefer the real secret; fall back to test secret only when real one is absent
        $secret = config('services.billing.webhook_secret')
            ?: config('services.billing.webhook_test_secret');
        
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
            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId, $request);
            
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

            // Map plan name.
            // Actual payload: subscription.plan is an OBJECT {id, name, price, interval}
            // Legacy payloads may send subscription.plan as a plain string or use price_plan_name.
            $rawPlanName = (is_array($subscription['plan'] ?? null)
                    ? ($subscription['plan']['name'] ?? null)
                    : (is_string($subscription['plan'] ?? null) ? $subscription['plan'] : null))
                ?? $subscription['price_plan_name']
                ?? $subscription['plan_id']
                ?? 'starter';
            $plan = $this->normalizePlanName($rawPlanName);

            // expires_at: must come from the billing platform — never fall back to a guess.
            // Actual payload field: subscription.ends_at
            // Legacy field name:    subscription.current_period_end
            $endsAt = $subscription['ends_at'] ?? $subscription['current_period_end'] ?? null;
            if (!$endsAt) {
                throw new \Exception(
                    "Webhook payload missing subscription.ends_at — cannot set expiry safely. "
                    . "Event: {$request->input('event')}, customer_id: {$customerId}"
                );
            }
            $expiresAt = Carbon::parse($endsAt);

            // starts_at: use billing platform value when present.
            $startsAt = Carbon::parse($subscription['starts_at'] ?? now());

            // AI credits: use explicit payload value; derive from local config when absent.
            $aiCredits = (int) ($subscription['ai_credits'] ?? $request->input('credits', 0));
            if ($aiCredits === 0) {
                $aiCredits = (int) config("safarichat_billing.plans.{$plan}.limits.ai_credits", 0);
                if ($aiCredits > 0) {
                    Log::info('AI credits derived from local config (not in payload)', [
                        'plan' => $plan, 'ai_credits' => $aiCredits,
                    ]);
                }
            }

            // Features: use explicit payload block; derive from local config when absent.
            $features = $subscription['features'] ?? [];
            if (empty($features)) {
                $planLimits = config("safarichat_billing.plans.{$plan}.limits", []);
                $features = [
                    'max_contacts'            => $planLimits['max_contacts']            ?? $billingAccount->max_contacts,
                    'max_products'            => $planLimits['max_products']            ?? $billingAccount->max_products,
                    'whatsapp_channels'       => $planLimits['whatsapp_channels']       ?? $billingAccount->whatsapp_channels,
                    'customer_followups'      => $planLimits['customer_followups']      ?? $billingAccount->customer_followups,
                    'customer_categorization' => $planLimits['customer_categorization'] ?? $billingAccount->customer_categorization,
                    'booking_calendars'       => $planLimits['booking_calendars']       ?? $billingAccount->booking_calendars,
                    'sales_reports'           => $planLimits['sales_reports']           ?? $billingAccount->sales_reports,
                ];
                Log::info('Features derived from local config (not in payload)', [
                    'plan' => $plan, 'features' => $features,
                ]);
            }

            // Update billing account (ai_credits incremented separately below)
            $billingAccount->update([
                'subscription_status'     => 'active',
                'subscription_plan'       => $plan,
                'subscription_started_at' => $startsAt,
                'subscription_expires_at' => $expiresAt,
                'max_contacts'            => $features['max_contacts'],
                'max_products'            => $features['max_products'],
                'whatsapp_channels'       => $features['whatsapp_channels'],
                'customer_followups'      => $features['customer_followups'],
                'customer_categorization' => $features['customer_categorization'],
                'booking_calendars'       => $features['booking_calendars'],
                'sales_reports'           => $features['sales_reports'],
                'last_payment_at'         => now(),
                'last_payment_amount'     => $payment['amount'] ?? 0,
                'last_transaction_id'     => $payment['transaction_id'] ?? null,
            ]);

            // Increment AI credits separately
            if ($aiCredits > 0) {
                $billingAccount->increment('ai_credits', $aiCredits);
            }

            Log::info('Payment success processed', [
                'billing_account_id' => $billingAccount->id,
                'customer_id'        => $customerId,
                'plan'               => $plan,
                'starts_at'          => $startsAt->toDateTimeString(),
                'expires_at'         => $expiresAt->toDateTimeString(),
                'credits_added'      => $aiCredits,
                'transaction_id'     => $payment['transaction_id'] ?? null,
            ]);

            return [
                'success'            => true,
                'message'            => 'Payment processed successfully',
                'billing_account_id' => $billingAccount->id,
                'subscription'       => [
                    'plan'       => $plan,
                    'status'     => 'active',
                    'expires_at' => $expiresAt->toISOString(),
                ],
            ];
        });
    }
    
    /**
     * Handle failed payment webhook
     */
    private function handlePaymentFailed(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $customerId = $request->input('customer_id');
            $businessId = $request->input('business_id');
            $payment    = $request->input('payment', []);

            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId, $request);

            if (!$billingAccount) {
                throw new \Exception("Could not find billing account for customer {$customerId}");
            }

            // Don't downgrade subscription status on a failed payment —
            // the billing platform will send subscription.cancelled/expired if needed.
            Log::warning('Payment failed', [
                'billing_account_id' => $billingAccount->id,
                'customer_id'        => $customerId,
                'transaction_id'     => $payment['transaction_id'] ?? null,
                'amount'             => $payment['amount'] ?? 0,
            ]);

            return [
                'success'            => true,
                'message'            => 'Payment failure recorded',
                'billing_account_id' => $billingAccount->id,
            ];
        });
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
            
            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId, $request);
            
            if (!$billingAccount) {
                throw new \Exception("Could not find billing account for customer {$customerId}");
            }
            
            // expires_at: must come from billing platform — never fall back to a guess.
            $endsAt = $subscription['ends_at'] ?? $subscription['current_period_end'] ?? null;
            if (!$endsAt) {
                throw new \Exception(
                    "Webhook payload missing subscription.ends_at — cannot set expiry safely. "
                    . "Event: subscription.renewed, customer_id: {$customerId}"
                );
            }
            $newExpiresAt = Carbon::parse($endsAt);

            // AI credits: use payload value; derive from local config when absent.
            $plan = $this->normalizePlanName(
                (is_array($subscription['plan'] ?? null)
                    ? ($subscription['plan']['name'] ?? '')
                    : (is_string($subscription['plan'] ?? null) ? $subscription['plan'] : ''))
                ?: ($subscription['price_plan_name'] ?? $billingAccount->subscription_plan ?? 'starter')
            );
            $aiCredits = (int) ($subscription['ai_credits'] ?? 0);
            if ($aiCredits === 0) {
                $aiCredits = (int) config("safarichat_billing.plans.{$plan}.limits.ai_credits", 0);
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
     *
     * Cancellations are typically "cancel at period end" — the subscription
     * remains accessible until subscription.ends_at, then expires naturally.
     * We mark status as 'cancelled' but preserve the ends_at date so access
     * continues until the paid period runs out.
     */
    private function handleSubscriptionCancelled(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $customerId   = $request->input('customer_id');
            $businessId   = $request->input('business_id');
            $subscription = $request->input('subscription', []);
            $cancellation = $request->input('cancellation', []);

            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId, $request);

            if (!$billingAccount) {
                throw new \Exception("Could not find billing account for customer {$customerId}");
            }

            // Preserve ends_at so access continues until the paid period runs out.
            // The subscription.expired webhook will set status to 'expired' when the time comes.
            $updateData = ['subscription_status' => 'cancelled'];
            if (!empty($subscription['ends_at'])) {
                $updateData['subscription_expires_at'] = Carbon::parse($subscription['ends_at']);
            }

            $billingAccount->update($updateData);

            Log::info('Subscription cancelled', [
                'billing_account_id' => $billingAccount->id,
                'customer_id'        => $customerId,
                'access_until'       => $subscription['ends_at'] ?? 'unknown',
                'reason'             => $cancellation['reason'] ?? null,
                'cancelled_at'       => $cancellation['cancelled_at'] ?? null,
            ]);

            return [
                'success'            => true,
                'message'            => 'Subscription cancelled',
                'billing_account_id' => $billingAccount->id,
                'access_until'       => $subscription['ends_at'] ?? null,
            ];
        });
    }
    
    /**
     * Handle subscription expired webhook
     */
    private function handleSubscriptionExpired(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $customerId = $request->input('customer_id');
            $businessId = $request->input('business_id');

            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId, $request);

            if (!$billingAccount) {
                throw new \Exception("Could not find billing account for customer {$customerId}");
            }

            $billingAccount->update(['subscription_status' => 'expired']);

            Log::info('Subscription expired', [
                'billing_account_id' => $billingAccount->id,
                'customer_id'        => $customerId,
            ]);

            return [
                'success'            => true,
                'message'            => 'Subscription expired',
                'billing_account_id' => $billingAccount->id,
            ];
        });
    }
    
    /**
     * Handle subscription upgrade webhook.
     *
     * Upgrade = plan change mid-cycle (e.g. Starter → Pro).
     * Key differences from renewal:
     *   - subscription_plan changes
     *   - feature limits are updated to the new plan
     *   - ai_credits are SET to the new plan allocation (not accumulated),
     *     preserving any surplus the user already has above the new plan limit
     *   - expiry is recalculated from today (new billing cycle starts now)
     */
    private function handleSubscriptionUpgraded(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $customerId   = $request->input('customer_id');
            $businessId   = $request->input('business_id');
            $payment      = $request->input('payment', []);
            $subscription = $request->input('subscription', []);

            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId, $request);

            if (!$billingAccount) {
                throw new \Exception("Could not find billing account for customer {$customerId}");
            }

            // Resolve new plan name.
            // upgrade.new_plan.name (upgrade event) → subscription.plan.name (object) → legacy strings
            $rawPlanName = $request->input('upgrade.new_plan.name')
                ?? (is_array($subscription['plan'] ?? null) ? ($subscription['plan']['name'] ?? null) : null)
                ?? $subscription['price_plan_name']
                ?? (is_string($subscription['plan'] ?? null) ? $subscription['plan'] : null)
                ?? $subscription['plan_id']
                ?? 'starter';
            // normalizePlanName handles aliases: "Professional Package" → "pro", etc.
            $newPlan = $this->normalizePlanName($rawPlanName);

            // expires_at: must come from billing platform — never fall back to a guess.
            $endsAt = $subscription['ends_at'] ?? $subscription['current_period_end'] ?? null;
            if (!$endsAt) {
                throw new \Exception(
                    "Webhook payload missing subscription.ends_at — cannot set expiry safely. "
                    . "Event: subscription.upgraded, customer_id: {$customerId}"
                );
            }
            $expiresAt = Carbon::parse($endsAt);

            // Feature limits: payload block first, then local config, then keep existing.
            $features = $subscription['features'] ?? [];
            if (empty($features)) {
                $planLimits = config("safarichat_billing.plans.{$newPlan}.limits", []);
                $features = [
                    'max_contacts'            => $planLimits['max_contacts']            ?? $billingAccount->max_contacts,
                    'max_products'            => $planLimits['max_products']            ?? $billingAccount->max_products,
                    'whatsapp_channels'       => $planLimits['whatsapp_channels']       ?? $billingAccount->whatsapp_channels,
                    'customer_followups'      => $planLimits['customer_followups']      ?? $billingAccount->customer_followups,
                    'customer_categorization' => $planLimits['customer_categorization'] ?? $billingAccount->customer_categorization,
                    'booking_calendars'       => $planLimits['booking_calendars']       ?? $billingAccount->booking_calendars,
                    'sales_reports'           => $planLimits['sales_reports']           ?? $billingAccount->sales_reports,
                ];
            }

            // Credits: SET to new plan allocation (not accumulated).
            // When not in payload, derive from local config for the new plan.
            // If user somehow has MORE credits than the new plan grants, keep their balance.
            $newPlanCredits = (int) ($subscription['ai_credits'] ?? 0);
            if ($newPlanCredits === 0) {
                $newPlanCredits = (int) config("safarichat_billing.plans.{$newPlan}.limits.ai_credits", 0);
                if ($newPlanCredits > 0) {
                    Log::info('Upgrade AI credits derived from local config (not in payload)', [
                        'plan' => $newPlan, 'ai_credits' => $newPlanCredits,
                    ]);
                }
            }
            $currentCredits = (int) $billingAccount->ai_credits;
            $creditsToSet   = max($newPlanCredits, $currentCredits);

            // starts_at: use billing platform value when present.
            $startsAt = Carbon::parse($subscription['starts_at'] ?? now());

            $billingAccount->update([
                'subscription_status'     => 'active',
                'subscription_plan'       => $newPlan,
                'subscription_started_at' => $startsAt,
                'subscription_expires_at' => $expiresAt,
                'ai_credits'              => $creditsToSet,
                'max_contacts'            => $features['max_contacts']            ?? $billingAccount->max_contacts,
                'max_products'            => $features['max_products']            ?? $billingAccount->max_products,
                'whatsapp_channels'       => $features['whatsapp_channels']       ?? $billingAccount->whatsapp_channels,
                'customer_followups'      => $features['customer_followups']      ?? $billingAccount->customer_followups,
                'customer_categorization' => $features['customer_categorization'] ?? $billingAccount->customer_categorization,
                'booking_calendars'       => $features['booking_calendars']       ?? $billingAccount->booking_calendars,
                'sales_reports'           => $features['sales_reports']           ?? $billingAccount->sales_reports,
                'last_payment_at'         => now(),
                'last_payment_amount'     => $payment['amount'] ?? 0,
                'last_transaction_id'     => $payment['transaction_id'] ?? null,
            ]);

            Log::info('Subscription upgraded', [
                'billing_account_id' => $billingAccount->id,
                'customer_id'        => $customerId,
                'new_plan'           => $newPlan,
                'expires_at'         => $expiresAt->toDateTimeString(),
                'credits_set_to'     => $creditsToSet,
                'transaction_id'     => $payment['transaction_id'] ?? null,
            ]);

            return [
                'success'            => true,
                'message'            => 'Subscription upgraded successfully',
                'billing_account_id' => $billingAccount->id,
                'subscription'       => [
                    'plan'       => $newPlan,
                    'status'     => 'active',
                    'expires_at' => $expiresAt->toISOString(),
                    'ai_credits' => $creditsToSet,
                ],
            ];
        });
    }

    /**
     * Handle credits purchase webhook (standalone credit purchase without subscription)
     *
     * Actual payload structure (wallet_transaction):
     * {
     *   "wallet_transaction": {
     *     "wallet_type": "ai_credits",
     *     "units": 1000,
     *     "unit_price": 50.0,
     *     "amount": 50000.00,
     *     "plan_name": "1000 AI Credits Pack",
     *     "invoice_number": "INV20260331001",
     *     "purchased_at": "2026-03-31T09:49:58+00:00"
     *   },
     *   "payment": { "transaction_id": "...", "amount": 50000.00 }
     * }
     */
    private function handleCreditsPurchased(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $customerId      = $request->input('customer_id');
            $businessId      = $request->input('business_id');
            $payment         = $request->input('payment', []);
            $walletTx        = $request->input('wallet_transaction', []);

            // Actual payload: wallet_transaction.units = number of credits to add
            // wallet_transaction.wallet_type must be "ai_credits" — guard against
            // other wallet types (e.g. cash wallet) accidentally adding AI credits.
            $walletType = $walletTx['wallet_type'] ?? 'ai_credits';
            if ($walletType !== 'ai_credits') {
                Log::warning('credits.purchased: unexpected wallet_type — skipping credit increment', [
                    'wallet_type' => $walletType,
                    'customer_id' => $customerId,
                ]);
                // Still resolve the account and record the payment, just don't add credits
                $credits = 0;
            } else {
                $credits = (int) ($walletTx['units'] ?? 0);
            }

            $billingAccount = $this->getOrCreateBillingAccount($customerId, $businessId, $request);

            if (!$billingAccount) {
                throw new \Exception("Could not find billing account for customer {$customerId}");
            }

            if ($credits > 0) {
                $billingAccount->increment('ai_credits', $credits);
            }

            $billingAccount->update([
                'last_payment_at'     => now(),
                'last_payment_amount' => $payment['amount'] ?? 0,
                'last_transaction_id' => $payment['transaction_id'] ?? null,
            ]);

            Log::info('Credits purchased', [
                'billing_account_id' => $billingAccount->id,
                'customer_id'        => $customerId,
                'wallet_type'        => $walletType,
                'credits_added'      => $credits,
                'new_balance'        => $billingAccount->fresh()->ai_credits,
                'plan_name'          => $walletTx['plan_name'] ?? null,
                'invoice_number'     => $walletTx['invoice_number'] ?? null,
                'transaction_id'     => $payment['transaction_id'] ?? null,
            ]);

            return [
                'success'            => true,
                'message'            => 'Credits added successfully',
                'billing_account_id' => $billingAccount->id,
                'credits_added'      => $credits,
                'new_balance'        => $billingAccount->fresh()->ai_credits,
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
     * Normalize a plan name from the billing platform to the local config key.
     *
     * Strips common suffixes (" package", " plan") then applies an alias map
     * so billing platform display names map to our safarichat_billing.php config keys.
     *
     * Examples:
     *   "Starter Package"      → "starter"
     *   "Professional Package" → "pro"
     *   "Premium Package"      → "premium"
     *   "Pro Plan"             → "pro"
     */
    private function normalizePlanName(string $raw): string
    {
        $slug = strtolower(trim(str_ireplace([' package', ' plan'], '', $raw)));

        // Map billing platform display names to local config keys
        $aliases = [
            'professional' => 'pro',
            'standard'     => 'starter',
            'basic'        => 'starter',
            'enterprise'   => 'premium',
            'business'     => 'pro',
        ];

        return $aliases[$slug] ?? $slug;
    }

    /**
     * Get or create billing account for customer/business
     * 
     * customer_id in the webhook is the BILLING PLATFORM's customer ID, not users.id.
     * Resolution order:
     *   1. business_id  → Business::find
     *   2. customer.email from payload → User::where('email')
     *   3. customer_id as users.id fallback (for legacy/matching IDs)
     */
    private function getOrCreateBillingAccount($customerId, $businessId, ?Request $request = null): ?BillingAccount
    {
        // 1. Try to find by business_id if the billing platform echoes it back.
        //    Note: business_id is NOT sent in the invoice payload (email/phone are
        //    the only unique identifiers the billing API accepts), so this step only
        //    fires when the platform happens to include it in its webhook.
        if ($businessId) {
            $business = Business::find($businessId);
            if ($business) {
                return $business->billingAccount ?? $business->getOrCreateBillingAccount();
            }
        }

        // 2. Look up by email from customer object in payload (most reliable)
        if ($request) {
            $email = $request->input('customer.email');
            if ($email) {
                // 2a-early. Generated fallback emails follow the pattern business-{id}@safarichat.ai
                // Extract the business ID directly — no DB column needed.
                if (preg_match('/^business-(\d+)@safarichat\.ai$/i', $email, $m)) {
                    $business = Business::find((int) $m[1]);
                    if ($business) {
                        Log::debug('Webhook: resolved business by generated billing email pattern', [
                            'email'       => $email,
                            'business_id' => $business->id,
                        ]);
                        return $business->billingAccount ?? $business->getOrCreateBillingAccount();
                    }
                }

                // 2a. Check businesses.email first — invoices created by SafariChat use
                //     business-{id}@safarichat.africa as the billing identifier
                $business = Business::where('email', $email)->first();
                if ($business) {
                    Log::debug('Webhook: resolved business by billing email', [
                        'email'           => $email,
                        'business_id'     => $business->id,
                        'billing_cust_id' => $customerId,
                    ]);
                    return $business->billingAccount ?? $business->getOrCreateBillingAccount();
                }

                // 2b. Fall back to users.email (legacy invoices created before billing_email was introduced)
                $user = User::where('email', $email)->first();
                if ($user) {
                    Log::debug('Webhook: resolved user by email (legacy)', [
                        'email'          => $email,
                        'user_id'        => $user->id,
                        'billing_cust_id' => $customerId,
                    ]);
                    return $user->billingAccount ?? $user->getOrCreateBillingAccount();
                }
            }

            // 3. Try phone number as fallback
            $phone = $request->input('customer.phone');
            if ($phone) {
                // 3a. Check business phone first
                $business = Business::where('phone', $phone)->first();
                if ($business) {
                    Log::debug('Webhook: resolved business by phone', [
                        'phone'          => $phone,
                        'business_id'    => $business->id,
                        'billing_cust_id' => $customerId,
                    ]);
                    return $business->billingAccount ?? $business->getOrCreateBillingAccount();
                }

                // 3b. Fall back to user phone
                $user = User::where('phone', $phone)->first();
                if ($user) {
                    Log::debug('Webhook: resolved user by phone', [
                        'phone'          => $phone,
                        'user_id'        => $user->id,
                        'billing_cust_id' => $customerId,
                    ]);
                    return $user->billingAccount ?? $user->getOrCreateBillingAccount();
                }
            }
        }

        // 4. Last resort: treat customer_id as users.id (only works when IDs match)
        if ($customerId) {
            $user = User::find($customerId);
            if ($user) {
                return $user->billingAccount ?? $user->getOrCreateBillingAccount();
            }
        }

        return null;
    }}