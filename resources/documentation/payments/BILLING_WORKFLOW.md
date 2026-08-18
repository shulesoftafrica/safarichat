# SafariChat Billing Workflow — Canonical Reference

**Purpose:** This document describes how billing, subscriptions, and AI credits are
implemented in SafariChat so that **every other subsystem (multi-channel, bookings,
campaigns, future products) follows the same workflow** instead of inventing its own
money/limit logic.

If you are adding a feature that needs to be *gated by plan*, *charge credits*, or
*react to a payment*, do **not** write new billing logic — plug into the pieces
described here.

---

## 1. Core principles (read these first)

1. **`billing_accounts` is the single source of truth.** Every limit, feature flag,
   credit balance, and subscription status lives on one row. Never store subscription
   or credit state on `users`, `businesses`, `conversations`, etc. Those columns were
   deliberately removed (see migrations `2026_01_23_*`).

2. **The billing *unit* is the Business, not the User.** A `billing_account` belongs to
   a `Business` (`business_id`). Users reach it through their business
   (`User::billingAccount()` is a `hasOneThrough`). Limits (contacts, products,
   channels) are counted at business scope.

3. **The external billing platform owns the money; SafariChat mirrors state.** Payments,
   invoices, and gateways live on the Shulesoft / SafariBank platform
   (`https://api.safaribank.africa/api/v1`). SafariChat **never** processes card/mobile-money
   payments itself. It (a) *asks* the platform to create invoices, and (b) *listens* for
   webhooks to update the local mirror.

4. **The database trigger is the only place that does credit arithmetic.** PHP writes
   *signal columns* (`billing_cycle_id`, `subscription_plan`, `topup_credits`); the
   PostgreSQL trigger `billing_credits_manager` computes `base_credits`,
   `ai_credits_used`, and `ai_credits`. **PHP must never hand-calculate a credit balance
   on renewal/upgrade.** This is the most important rule to preserve.

5. **Runtime reads are cache-first, never API-first.** Feature gating during normal
   requests reads a cached status blob (2h TTL) with a conservative fallback built from
   the local `billing_account`. No billing API call happens on the hot path.

---

## 2. Component map

| Layer | File | Responsibility |
|-------|------|----------------|
| **Config — plans** | `config/safarichat_billing.php` | Plan catalog: prices, limits, feature flags, credit allocations, rollover. |
| **Config — platform** | `config/services.php` (`billing`, `shulesoft_billing`) | API URL, OAuth creds, webhook secret, price-plan IDs, SSL. |
| **Model (source of truth)** | `app/Models/BillingAccount.php` | Reads limits/credits, `deductCredits()`, `addCredits()`, `isActive()`, `changePlan()`, `hasFeature()`. |
| **DB trigger** | `database/migrations/2026_04_02_000003_create_billing_credits_trigger.php` | `billing_credits_manager()` — all credit math on `billing_accounts` UPDATE. |
| **Audit log** | `app/Models/CreditAdjustment.php` + `credit_adjustments` table | Immutable record of every credit change (written by trigger). |
| **Outbound API** | `app/Services/BillingService.php` | Talks to the platform: create/upgrade/downgrade invoices, fetch products, fetch status. Also the credit facade (`deductCredits`, `hasCredits`). |
| **OAuth** | `app/Services/ShulesoftAuthService.php` | Access-token mint/refresh for the platform API. |
| **Inbound webhook** | `app/Http/Controllers/Api/BillingWebhookController.php` | Receives platform events, resolves the account, writes signal columns. |
| **Webhook guards** | `app/Http/Requests/BillingWebhookRequest.php`, `app/Http/Middleware/ValidateBillingWebhookIP.php`, `app/Models/BillingWebhookEvent.php` | Payload validation, IP filter, HMAC signature, idempotency. |
| **Runtime cache + fallback** | `BillingService::getCachedStatus()` / `getFallbackStatus()` | 2h cached status blob for hot-path gating. |
| **Local validators** | `app/Services/LocalBillingValidator.php` | Pure functions: `canAddContact`, `canUseAI`, `hasFeaturePermission`, etc. — operate on the cached blob only. |
| **Credit reservation** | `app/Services/LocalCreditManager.php` | Reserve → finalize/release pattern for AI ops (cache-level). |
| **Expiry job** | `app/Console/Commands/EnforceSubscriptionExpiry.php` | Daily 00:05 — flips past-due `active` accounts to `expired`. |

---

## 3. Data model

### `billing_accounts` (the row that matters)

Grouped by purpose:

- **Identity:** `business_id`
- **Subscription:** `subscription_plan` (`trial|starter|pro|premium`),
  `subscription_status` (`active|cancelled|expired|trial|inactive`),
  `subscription_started_at`, `subscription_expires_at`, `billing_cycle_id`,
  `external_subscription_id`, `trial_ends_at`
- **Credits (the important part):**
  - `base_credits` — plan allocation for the current cycle (**trigger-managed**)
  - `topup_credits` — purchased credits that **never expire on renewal** (PHP increments this)
  - `ai_credits_used` — usage counter for the cycle (`deductCredits` increments this)
  - `available_credits` — **`GENERATED ALWAYS AS GREATEST(0, base_credits + topup_credits - ai_credits_used)`**. Read-only in PHP; always accurate.
  - `ai_credits` — legacy mirror = `base + topup`, kept in sync by the trigger for old call sites.
- **Limits / feature flags** (mirrored from the plan config): `max_contacts`,
  `max_products`, `whatsapp_channels`, `customer_followups`, `customer_categorization`,
  `booking_calendars`, `sales_reports`, `unlimited_messages`
- **Last payment:** `last_transaction_id`, `last_payment_at`, `last_payment_amount`

### Supporting tables

- **`credit_adjustments`** — immutable audit trail. Every renewal/upgrade/downgrade/topup/expiry
  writes a before/after snapshot. Written by the trigger (and manually for `expiry`).
- **`billing_webhook_events`** — one row per received webhook, keyed by
  `(transaction_id, event_type)` for idempotency, with `processing_status`
  (`processing|success|failed|unresolved`).

---

## 4. The credit engine (the signal-column pattern)

This is the single most reusable idea in the system. **PHP writes intent; the DB computes balances.**

### What PHP is allowed to write

| Intent | Column PHP writes | Trigger scenario |
|--------|-------------------|------------------|
| New subscription / renewal (same plan, new period) | `billing_cycle_id` (new value) | **SCENARIO 1** — `base_credits = plan limit`, `ai_credits_used = 0` (carries unused base if `credits_rollover`). |
| Upgrade / downgrade | `subscription_plan` (new value) | **SCENARIO 2/3** — `base_credits = new plan limit`, `ai_credits_used = 0`. |
| Credit top-up purchase | `increment('topup_credits', n)` | **SCENARIO 4** — audit log only; `available_credits` recomputes automatically. |
| Consume credits | `increment('ai_credits_used', n)` via `BillingAccount::deductCredits()` | (no scenario — usage counter) |

The trigger (`billing_credits_manager`) **only acts on `subscription_status = 'active'`**
rows and always writes a `credit_adjustments` audit row.

> ⚠️ **Never** set `ai_credits`, `base_credits`, or `ai_credits_used` directly to reset a
> balance in PHP. Change a signal column and let the trigger do it. The webhook handlers
> and `BillingAccount::changePlan()` are the reference implementations.

> ⚠️ **Plan credit limits are duplicated** in two places that must stay in sync:
> `config/safarichat_billing.php` **and** the `CASE` block inside the trigger migration.
> Change both together.

### Consuming credits (the pattern every feature uses)

```php
use App\Services\BillingService;

// 1. Gate before doing paid work
if (!BillingService::hasCredits($user, $estimatedCredits)) {
    // block / show upgrade prompt
}

// 2. Do the work (AI call, etc.)

// 3. Deduct the ACTUAL amount used
BillingService::deductCredits($user, $actualCredits, "Reason for audit log");
```

`BillingService::deductCredits()` → `BillingAccount::deductCredits()` refreshes from DB,
checks `available_credits`, and does an **atomic** `increment('ai_credits_used')` (safe
under concurrency). It also triggers low-credit notifications at 20% / 10% / 0% thresholds.
Reference consumer: `app/Services/OpenAiService.php:269`.

---

## 5. Runtime feature gating (hot path — no API calls)

For per-request checks (can this user add a contact? use AI? open a booking calendar?):

1. `BillingService::getCachedStatus($customerId)` returns a status blob from cache
   (`billing_status_{id}`, 2h TTL). If the cache is missing/expired/corrupt it builds a
   **fallback** blob directly from the local `billing_account` (`getFallbackStatus()`),
   cached for 30 min.
2. Pass that blob to a pure validator in `LocalBillingValidator`:
   `canAddContact()`, `canAddProduct()`, `canSendMessage()`, `canUseAI()`,
   `canAddWhatsAppChannel()`, `hasFeaturePermission($status, $feature)`.

The blob shape is stable — `subscription`, `limits`, `permissions` keys. New features
should add a permission in **both** `enrichStatusData()` (API path) and
`getFallbackStatus()` (offline path) of `BillingService`, and derive it from the plan
config, so gating works in both modes.

For simple server-side checks you can also read the model directly:
`$billingAccount->isActive()`, `->hasCredits($n)`, `->canUseAiFeatures()`,
`->hasFeature('booking_calendars')`.

---

## 6. End-to-end lifecycle

```
                    ┌─────────────────────────────────────────────┐
                    │  Billing Platform (api.safaribank.africa)   │
                    │  invoices · gateways · subscriptions        │
                    └─────────────────────────────────────────────┘
                        ▲  create invoice           │ webhook (event)
                        │  (BillingService,          ▼
                        │   OAuth token)      ┌──────────────────────────┐
   ┌──────────────┐     │                     │ BillingWebhookController │
   │  User / UI   │─────┘                     │  verify → resolve →      │
   └──────────────┘                           │  write signal columns    │
        │  feature gate                       └──────────────────────────┘
        ▼                                                │ UPDATE
   ┌──────────────────────┐   cache blob     ┌───────────────────────────┐
   │ LocalBillingValidator │◄────────────────│  billing_accounts (row)   │
   │ BillingService::hasCredits              │  + billing_credits_manager│
   └──────────────────────┘                  │    trigger → credits math │
                                             │  + credit_adjustments log │
                                             └───────────────────────────┘
```

### 6.1 Account creation
`Business::getOrCreateBillingAccount()` (or `User::getOrCreateBillingAccount()`) creates a
`trial` account with the plan's credit allocation. Every business gets a **stable billing
email** `business-{id}@safarichat.ai` (`Business::getBillingEmail()`) — this is the immutable
identifier used to resolve webhooks back to the right business.

### 6.2 Purchase / renewal / upgrade (outbound)
- `BillingService::createSubscriptionInvoice($user, $pricePlanId, $amount, $gateway, ...)`
  POSTs to `/invoices` and returns payment links + UCN. Customer info sent is
  `{name, email, phone}` only — **no `business_id`** (the email pattern is the resolver).
- `BillingService::upgradeSubscription(...)` → `/invoices/plan-upgrade`
- `BillingService::downgradeSubscription(...)` → `/invoices/plan-downgrade`
- Price-plan IDs come from `config('services.billing.price_plans')`.
- All requests go through `makeAuthenticatedRequest()`, which attaches the OAuth bearer
  token (`ShulesoftAuthService`) and retries once on `401` after a refresh.

### 6.3 Webhook receipt (inbound — the critical path)
Route: `POST /api/billing/webhook` (`routes/api.php`), middleware
`throttle:60,1` + `billing.webhook.ip`.

`BillingWebhookController::handle()` steps:
1. **Log** the raw payload.
2. **Signature** — HMAC-SHA256 of the raw body against `BILLING_WEBHOOK_SECRET`
   (falls back to allowing if no secret/header configured; relies on IP allowlist then).
3. **Idempotency key** = `event_id` (preferred) or `payment.transaction_id`.
   If a `success` row already exists for `(key, event_type)` → return 200 immediately.
4. **Upsert** a `billing_webhook_events` row as `processing` (handles retries of failed deliveries).
5. **Route** on `event`:
   `payment.success` · `payment.failed` · `subscription.created` · `subscription.renewed` ·
   `subscription.upgraded` · `subscription.cancelled` · `subscription.expired` ·
   `credits.purchased`.
6. Each handler runs in a **`DB::transaction`**, resolves the account, and **writes signal
   columns only** (never credit math). A second per-cycle idempotency guard compares
   `billing_cycle_id` = `{event_id|sub_id|txn}_{YYYYMM}` to skip duplicate monthly deliveries.
7. Mark the event `success` / `failed` / `unresolved` (customer not found → 422, recorded for later).

**Account resolution order** (`getOrCreateBillingAccount`):
`business_id` → `business-{id}@safarichat.ai` email pattern → `businesses.email` →
`users.email` → `customer.phone` (business then user) → `customer_id` as `users.id`.

**Credit vs subscription detection:** if all invoice items are "Credit" packages, or the
event is `credits.purchased`/`wallet_transaction`, PHP increments `topup_credits` (never
wiped on renewal). Otherwise it's a subscription activation → writes `billing_cycle_id` and
lets the trigger allocate `base_credits`.

**Plan-name normalization:** `normalizePlanName()` strips `" package"/" plan"` and maps
aliases (`professional→pro`, `standard/basic→starter`, `enterprise→premium`,
`business→pro`) to the local config keys.

**Expiry safety:** handlers **throw** if `subscription.ends_at` is missing rather than
guessing an expiry date. Missing credits/features are derived from `safarichat_billing.php`.

### 6.4 Expiry enforcement
`php artisan billing:enforce-expiry` (daily 00:05 via `Console/Kernel`) flips `active`
accounts past `subscription_expires_at` to `expired`, writes an `expiry` audit row, and
notifies the owner. **Monthly credit resets are NOT done here** — they come from the
platform's `subscription.renewed` webhook.

---

## 7. Security checklist for the webhook

- ✅ HMAC-SHA256 signature (`X-Webhook-Signature`) vs `BILLING_WEBHOOK_SECRET`.
- ✅ Rate limit `throttle:60,1`.
- ✅ Idempotency at two levels (event row + `billing_cycle_id`).
- ✅ Form-request payload validation (`BillingWebhookRequest`).
- ✅ Every mutation inside a DB transaction.
- ⚠️ IP allowlist middleware exists but is **currently pass-through**
  (`ValidateBillingWebhookIP` returns `next()` for all IPs) — security rests on the
  signature. Re-enable the allowlist if the platform publishes stable IPs.

---

## 8. How a NEW subsystem should plug in (the recipe)

Follow this and you inherit the whole billing workflow for free:

1. **Add plan config, don't hardcode.** Put any new limit/feature/credit cost in
   `config/safarichat_billing.php` under each plan's `limits`. If it's a credit limit that
   resets per cycle, also add it to the trigger `CASE` block.

2. **Add a column to `billing_accounts`** (migration) if the feature needs a persisted
   limit/flag, and add it to the model's `$fillable`/`$casts`. Mirror it in
   `syncLimitsFromPlan()`.

3. **Gate reads through the existing entry points:**
   - Hot path / UI: `BillingService::getCachedStatus()` + a `LocalBillingValidator`
     method (add one if needed, and surface the permission in both `enrichStatusData()`
     and `getFallbackStatus()`).
   - Server-side: `$billingAccount->hasFeature(...)`, `->isActive()`, `->hasCredits($n)`.

4. **Charge credits the standard way:** `BillingService::hasCredits()` before,
   `BillingService::deductCredits()` after (with a reason string for the audit log).
   Never touch `ai_credits`/`base_credits` directly.

5. **React to money via webhooks, not polling.** If a new paid product needs a new event,
   add a handler in `BillingWebhookController` that follows the same shape:
   `DB::transaction` → resolve account → write *signal columns* → let the trigger/audit run.

6. **Create charges through `BillingService`**, which handles OAuth + retry. Add a method
   there rather than calling `Http` from your feature code.

7. **Keep the two credit-limit copies in sync** (config + trigger) and **write an audit row**
   for any status-only change the trigger can't see (see the `expiry` example).

---

## 9. Key file references

- Plan catalog: `config/safarichat_billing.php`
- Platform config: `config/services.php` (`billing`, `shulesoft_billing`)
- Source-of-truth model: `app/Models/BillingAccount.php`
- Credit trigger: `database/migrations/2026_04_02_000003_create_billing_credits_trigger.php`
- Outbound + credit facade: `app/Services/BillingService.php`
- Inbound webhook: `app/Http/Controllers/Api/BillingWebhookController.php`
- Runtime validators: `app/Services/LocalBillingValidator.php`
- Reservation manager: `app/Services/LocalCreditManager.php`
- Expiry job: `app/Console/Commands/EnforceSubscriptionExpiry.php`
- Consumer example: `app/Services/OpenAiService.php`
- Webhook route: `routes/api.php` (`POST /api/billing/webhook`)
