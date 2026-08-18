# SafariChat Billing Integration Reference (Laravel)

## 1) Purpose of this document

This document explains exactly how payment and subscription billing is integrated in this project, so the same integration can be replicated in another Laravel application.

Focus areas covered:

1. Product design and billing domain model
2. How invoice creation is done against the external billing platform
3. What is stored locally, and where
4. How webhook updates are received, validated, made idempotent, and applied
5. Practical replication checklist for another Laravel app

This implementation is centered on external billing APIs (Shulesoft/SafariBank billing endpoints), local billing account state, and webhook-driven state synchronization.

---

## 2) Product design model

The billing model is business-centric, not user-centric.

1. Billing owner = Business
2. Subscription plan = one of: trial, starter, pro, premium
3. Credits = usage currency for AI operations
4. Limits = per-plan feature and capacity gates (contacts, products, channels, reports, etc.)
5. External billing is source of truth for payment events; local app is source of truth for feature gating and runtime checks

### 2.1 Plan configuration

Plan limits and prices are defined locally in configuration:

1. config/safarichat_billing.php
2. plans.trial/starter/pro/premium
3. limits.ai_credits, max_contacts, max_products, whatsapp_channels, feature booleans
4. token_pricing for AI credit economics

This local config is used for:

1. UI display
2. Fallback behavior when billing API is unavailable
3. Feature gating checks
4. Trigger logic alignment (credit reset and rollover logic)

---

## 3) Core architecture and components

### 3.1 Main components

1. BillingController
2. BillingApiController
3. BillingWebhookController
4. BillingService
5. ShulesoftAuthService (OAuth token provider used by BillingService/BillingApiController)
6. BillingWebhookRequest (payload validation)
7. ValidateBillingWebhookIP middleware

### 3.2 Main route surfaces

Web routes:

1. GET /billing/payment
2. POST /billing/process-payment
3. GET /billing/stripe/success
4. GET /billing/success
5. GET /billing/cancel
6. GET /billing/ucn-instructions/{reference}

API routes:

1. POST /api/billing/webhook
2. GET /api/billing/products
3. GET /api/billing/customers/{customerId}/complete-status
4. POST /api/billing/sync-credits
5. POST /api/billing/verify-credits
6. POST /api/billing/refresh-status
7. POST /api/billing/emergency-refresh

Webhook endpoint protections:

1. throttle:60,1
2. billing.webhook.ip middleware
3. signature validation in controller
4. payload schema validation via BillingWebhookRequest
5. idempotency via billing_webhook_events unique key

### 3.3 URL map (internal and external)

Internal URLs used by this app:

1. POST /api/billing/webhook
2. GET /billing/payment
3. POST /billing/process-payment
4. GET /billing/stripe/success
5. GET /billing/success
6. GET /billing/cancel
7. GET /billing/wallet
8. POST /api/billing/wallet/get-ucn
9. POST /api/billing/upgrade
10. POST /api/billing/renew

External billing platform URLs (base from config/services.php -> services.billing.api_url):

1. POST {BILLING_API_URL}/invoices
2. GET {BILLING_API_URL}/invoices/{invoice_id}/payment-gateways
3. GET {BILLING_API_URL}/customers/{customer_id}/complete-status (through service-level status loaders)
4. OAuth/token flow is managed through ShulesoftAuthService used by BillingService

---

## 4) Local persistence design (what is stored)

### 4.1 billing_accounts table (primary local billing state)

Stores:

1. business_id
2. subscription_plan
3. subscription_status
4. subscription_started_at
5. subscription_expires_at
6. external_subscription_id
7. subscription_ucn
8. credit_ucn
9. ai_credits
10. ai_credits_used
11. base_credits
12. topup_credits
13. available_credits (generated column)
14. billing_cycle_id
15. plan feature limits and switches
16. last_payment_at, last_payment_amount, last_transaction_id

Key behavior:

1. available_credits is computed, not manually written
2. PostgreSQL trigger controls reset/reallocation on renewal/upgrade/downgrade
3. topup_credits are persistent across renewals

### 4.2 billing_webhook_events table (audit + idempotency)

Stores:

1. event_type
2. transaction_id (idempotency key)
3. billing_account_id
4. payload JSON
5. processing_status (processing/success/failed/unresolved)
6. error_message
7. signature, source_ip, processed_at

Unique constraint:

1. unique(transaction_id, event_type)

This is critical for replay safety and duplicate delivery handling.

### 4.3 payment_intents table (local checkout tracking)

Used in local payment flow (especially Stripe/UCN fallback UX):

1. user_id
2. plan_code
3. amount
4. payment_method
5. payment_reference
6. status
7. payment_data

### 4.4 Important note about invoices

There is no dedicated local invoices table in this implementation.

Invoice objects live on the external billing platform.

Locally, only invoice-derived references are persisted:

1. UCN (subscription_ucn / credit_ucn)
2. external_subscription_id
3. webhook payload snapshots in billing_webhook_events
4. payment_intents for local checkout workflow

---

## 5) Invoice creation flow (clearly separated)

This project has two invoice categories that must not be mixed:

1. Subscription invoices (renew, upgrade, recurring plan lifecycle)
2. One-time invoices (wallet/credit top-up)

---

### 5.1 Subscription invoices

Used for:

1. Plan renewal
2. Plan upgrade

Entry URLs:

1. POST /api/billing/renew
2. POST /api/billing/upgrade
3. GET /billing/payment

Primary methods involved:

1. BillingController::showPayment(Request $request)
2. BillingService::createSubscriptionInvoice($business, $planCode, $amount, $paymentGateway, $successUrl, $cancelUrl)
3. BillingService::upgradeSubscription($subscriptionId, $newPlanCode, $amount, $paymentGateway, $successUrl, $cancelUrl)
4. BillingService::getCustomerLatestInvoice($customerId)
5. BillingApiController::renewPlan(Request $request)
6. BillingApiController::upgradePlan(Request $request)

Subscription invoice processing sequence:

1. Validate plan and amount
2. Resolve external price plan id for selected plan
3. Build external invoice payload and POST to /invoices
4. Fetch payment gateways with GET /invoices/{invoice_id}/payment-gateways
5. Extract payment links (Stripe/Flutterwave/UCN)
6. Persist subscription references locally:
7. subscription_ucn
8. external_subscription_id (if returned)
9. Redirect/render payment page with links

### 5.2 One-time invoices

Used for:

1. Wallet top-up
2. Credit purchase not tied to plan change

Entry URLs:

1. GET /billing/wallet
2. POST /api/billing/wallet/get-ucn
3. API wallet top-up paths in BillingApiController

Primary methods involved:

1. BillingController::showWallet(Request $request)
2. BillingController::getWalletInfo(Request $request)
3. BillingApiController::getWalletInfo(Request $request)
4. BillingApiController::topUpWallet(Request $request)
5. BillingApiController::getWalletUCN(Request $request)
6. BillingApiController::purchaseCredits(Request $request)

One-time invoice processing sequence:

1. Validate amount
2. Use credits price plan id (services.billing.credits_price_plan_id)
3. POST invoice to /invoices with credit product/price plan
4. GET payment gateways for that invoice
5. Extract UCN and save credit_ucn in billing_accounts
6. Complete payment externally
7. Wait for webhook (credits.purchased or payment.success with credit-only invoice items)
8. Increase topup_credits locally

### 5.3 External invoice request payload shape used

Representative structure sent to billing platform:

1. organization_id
2. customer.name
3. customer.email
4. customer.phone
5. products[].price_plan_id
6. products[].amount
7. description
8. currency
9. status (issued)
10. payment_gateway
11. success_url
12. cancel_url

### 5.4 BillingService request methods used by invoice flows

1. BillingService::makeAuthenticatedRequest($method, $endpoint, $data = null)
2. BillingService::makeBillingRequest($method, $endpoint, $data = null)
3. BillingApiController::makeAuthenticatedRequest($method, $url, $data = null)
4. BillingApiController::getHttpClient(array $headers = [])

### 5.5 Customer identity strategy for invoice creation

The app uses business identity, with stable fallback email generation:

1. Use business email if available
2. Else generate business-{id}@safarichat.ai

This fallback pattern is intentional and later used by webhook resolver to map billing payloads back to the correct business.

---

## 6) What gets stored immediately at invoice time

### 6.1 Subscription invoice storage

At subscription invoice generation time, local app stores:

1. subscription_ucn when available
2. external_subscription_id when available
3. payment_intents row in local checkout paths

### 6.2 One-time invoice storage

At one-time wallet/credits invoice generation time, local app stores:

1. credit_ucn when available
2. payment/intention metadata where applicable

### 6.3 Cross-cutting storage rule

The invoice itself is not duplicated into a local invoices table.

Only references and webhook snapshots are persisted.

---

## 7) Webhook receive and update flow

### 7.1 Endpoint

POST /api/billing/webhook

### 7.2 Validation and security pipeline

1. Route middleware throttle:60,1
2. Route middleware billing.webhook.ip (currently permissive, logs and allows)
3. BillingWebhookRequest validates payload schema
4. Controller validates signature using BILLING_WEBHOOK_SECRET fallback logic

Signature behavior in this implementation:

1. If secret is missing, signature check is skipped (warning logged)
2. If signature header missing, check is skipped (warning logged)
3. If present, supports raw signature and prefixed signature formats (e.g. sha256=...)
4. Compares hash_hmac(sha256, rawBody, secret)

### 7.3 Idempotency model

Idempotency key selection:

1. event_id preferred
2. fallback to payment.transaction_id

Processing behavior:

1. Check if transaction_id + event_type already succeeded
2. If yes: return success without reapplying
3. Else updateOrCreate billing_webhook_events row with processing state
4. Run event handler
5. Mark success/failed/unresolved accordingly

### 7.4 Event routing and handlers

Handled event types:

1. payment.success
2. payment.failed
3. subscription.created
4. subscription.renewed
5. subscription.upgraded
6. subscription.cancelled
7. subscription.expired
8. credits.purchased

### 7.5 Customer/business resolution during webhook

Resolution order:

1. business_id direct lookup
2. customer.email
3. generated pattern business-{id}@safarichat.ai extraction
4. user email fallback
5. customer.phone fallback
6. customer_id interpreted as users.id fallback

If unresolved:

1. webhook event stored as unresolved
2. returns 422 with clear message
3. no subscription mutation applied

---

## 8) How webhook updates billing state

### 8.1 payment.success and subscription.created

1. Determine if event is credit-only purchase from invoice items
2. For credit-only purchase: increment topup_credits, update last payment fields
3. For subscription activation:
4. normalize plan
5. require ends_at/current_period_end from payload
6. compute billing_cycle_id
7. if same cycle id, treat as duplicate and skip
8. else update subscription status/plan/dates/features/payment metadata

### 8.2 subscription.renewed

1. Require ends_at/current_period_end
2. Normalize plan
3. Build new billing_cycle_id
4. If same cycle id: idempotent no-op
5. Else write status, plan, billing_cycle_id, expiry, payment metadata
6. DB trigger resets base credits and usage for new cycle

### 8.3 subscription.upgraded

1. Resolve new plan from upgrade.new_plan.name or subscription plan fields
2. Require ends_at/current_period_end
3. Build billing_cycle_id
4. Update plan, cycle, dates, feature limits, payment metadata
5. DB trigger handles credit allocation reset for plan change

### 8.4 subscription.cancelled

1. Set subscription_status=cancelled
2. Preserve ends_at so access continues until period end

### 8.5 subscription.expired

1. Set subscription_status=expired

### 8.6 credits.purchased

1. Validate wallet_type=ai_credits
2. Increment topup_credits by wallet_transaction.units
3. Update last payment metadata

---

## 9) Credit engine design (critical)

The system uses database-level credit arithmetic with trigger control.

Important columns:

1. base_credits = current cycle plan allocation
2. topup_credits = purchased extra credits
3. ai_credits_used = consumed credits
4. available_credits = generated as max(0, base + topup - used)

Trigger scenarios in billing_credits_manager:

1. Renewal: new billing_cycle_id same plan -> reset base, reset used, optional rollover
2. Upgrade: plan change higher -> fresh base, reset used
3. Downgrade: plan change lower -> fresh base, reset used
4. Topup: topup_credits increase -> leave cycle/usage model intact

This design prevents drift and makes credit behavior auditable.

---

## 10) Configuration required in a new Laravel app

### 10.1 services config

Define in config/services.php:

1. billing.api_url
2. billing.access_token
3. billing.organization_id
4. billing.product_id
5. billing.credits_price_plan_id
6. billing.wallet_credits_price_plan_id
7. billing.webhook_secret
8. billing.price_plans.starter/pro/premium
9. shulesoft_billing.api_url and OAuth auth credentials
10. shulesoft_billing timeout and SSL settings

### 10.2 environment variables

Minimum env set:

1. BILLING_API_URL
2. BILLING_ACCESS_TOKEN (or OAuth stack via SHULESOFT_AUTH_*)
3. BILLING_ORGANIZATION_ID
4. BILLING_WEBHOOK_SECRET
5. BILLING_PRICE_PLAN_STARTER
6. BILLING_PRICE_PLAN_PRO
7. BILLING_PRICE_PLAN_PREMIUM
8. BILLING_CREDITS_PRICE_PLAN_ID

### 10.3 middleware registration

Register alias billing.webhook.ip in app/Http/Kernel.php.

---

## 11) Replication blueprint for another Laravel app

Use this exact order.

1. Create billing_accounts, billing_webhook_events, payment_intents tables
2. Add credit management columns and generated available_credits
3. Add billing_credits_manager trigger and credit_adjustments audit table
4. Create BillingAccount and BillingWebhookEvent models with proper casts
5. Add Business billing relation and getOrCreateBillingAccount logic
6. Add fallback billing email method business-{id}@yourdomain
7. Implement BillingService API wrapper with OAuth token refresh support
8. Add BillingWebhookRequest payload validator
9. Add BillingWebhookController with signature check + idempotency
10. Add webhook route POST /api/billing/webhook with throttle + middleware
11. Implement subscription invoice flow methods:
12. BillingController::showPayment
13. BillingService::createSubscriptionInvoice
14. BillingService::upgradeSubscription
15. Implement one-time invoice flow methods:
16. BillingApiController::topUpWallet
17. BillingApiController::getWalletUCN
18. Parse and persist UCN fields separately:
19. subscription_ucn for subscriptions
20. credit_ucn for one-time credit invoices
21. Use webhook events as the only trusted subscription-state mutation path
22. Add feature gates and credit checks from billing_accounts

---

## 12) Recommended operational safeguards

1. Never mutate subscription expiry using guessed dates
2. Always require ends_at from webhook payload for state changes
3. Keep webhook idempotency table forever (or long retention)
4. Log unresolved webhooks for manual support handling
5. Keep generated billing email stable and deterministic
6. Treat external billing API as producer, local app as state consumer
7. Keep trigger logic and local plan config synchronized

---

## 13) Known design decisions in this implementation

1. Invoice records are external; local app stores only references and webhook payloads
2. IP middleware exists but currently allows all IPs and relies mainly on signature validation
3. Billing API integration includes fallback modes for API outages
4. Subscription and credit state updates are webhook-first for consistency
5. topup_credits are intentionally not wiped by renewal logic

---

## 14) Quick event-to-update matrix

1. payment.success
2. subscription.created
3. subscription.renewed
4. subscription.upgraded
5. subscription.cancelled
6. subscription.expired
7. credits.purchased

Applied updates:

1. status transitions: active/cancelled/expired
2. plan updates: trial/starter/pro/premium
3. period updates: started_at/expires_at
4. payment metadata: last_payment_at/amount/transaction_id
5. credits: topup_credits increase or trigger-driven cycle reset
6. feature limits: max_contacts/products/channels and capability flags

---

## 15) Implementation summary for AI reuse

If an AI agent imports this document and needs to reproduce the integration in a new Laravel system, the minimum deterministic pattern is:

1. Build a business-level billing account model
2. Create invoices in external billing API from local upgrade/renew actions
3. Persist only invoice references (UCN/subscription id), not full invoice tables
4. Process all billing state transitions via webhook controller
5. Enforce idempotency with transaction_id + event_type unique key
6. Use DB trigger for credit arithmetic and generated available balance
7. Apply feature gating from billing_accounts current plan and limits

That is the same architecture used in this project.

---

## 16) Method reference list (quick scan)

Subscription invoice methods:

1. BillingController::showPayment(Request $request)
2. BillingController::upgrade(Request $request)
3. BillingController::renew(Request $request)
4. BillingApiController::upgradePlan(Request $request)
5. BillingApiController::renewPlan(Request $request)
6. BillingService::createSubscriptionInvoice($business, $planCode, $amount, $paymentGateway, $successUrl, $cancelUrl)
7. BillingService::upgradeSubscription($subscriptionId, $newPlanCode, $amount, $paymentGateway, $successUrl, $cancelUrl)
8. BillingService::getCustomerLatestInvoice($customerId)

One-time invoice methods:

1. BillingController::showWallet(Request $request)
2. BillingController::getWalletInfo(Request $request)
3. BillingApiController::getWalletInfo(Request $request)
4. BillingApiController::topUpWallet(Request $request)
5. BillingApiController::getWalletUCN(Request $request)
6. BillingApiController::purchaseCredits(Request $request)

Webhook methods:

1. BillingWebhookController::handle(BillingWebhookRequest $request)
2. BillingWebhookController::validateSignature($payload, $signature)
3. BillingWebhookController::handlePaymentSuccess($payload, $billingAccount)
4. BillingWebhookController::handlePaymentFailed($payload, $billingAccount)
5. BillingWebhookController::handleSubscriptionCreated($payload, $billingAccount)
6. BillingWebhookController::handleSubscriptionRenewed($payload, $billingAccount)
7. BillingWebhookController::handleSubscriptionUpgraded($payload, $billingAccount)
8. BillingWebhookController::handleSubscriptionCancelled($payload, $billingAccount)
9. BillingWebhookController::handleSubscriptionExpired($payload, $billingAccount)
10. BillingWebhookController::handleCreditsPurchased($payload, $billingAccount)

