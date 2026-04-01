# SafariChat Credit Management System — Final Design

> **Status:** Approved Architecture  
> **Date:** April 2, 2026  
> **Author:** Engineering

---

## 1. Overview

SafariChat uses an AI-credit system to gate access to AI-powered features (auto-reply, lead scoring, conversation summaries, etc.). Every time the AI responds to a customer, OpenAI tokens are consumed and converted to AI credits deducted from the business's account balance.

This document defines the canonical design — the source of truth for how credits are granted, consumed, tracked, and audited. It also documents every known bug in the current implementation and the exact fix required for each.

---

## 2. Credit Lifecycle (How Credits Flow)

```
 ┌──────────────┐    webhook: subscription.created / renewed
 │ Billing Plat │───────────────────────────────────────────────────────────►┐
 └──────────────┘                                                             │
                                                                              ▼
 ┌──────────────┐    webhook: credits.purchased               ┌──────────────────────────┐
 │ Billing Plat │──────────────────────────────────────────►  │   billing_accounts        │
 └──────────────┘                                             │                          │
                                                              │  base_credits     (plan) │
 ┌──────────────┐    AI reply sent to customer                │  topup_credits    (buy)  │
 │ ConvEngine   │──► TokenUsageService ──► deduct credits ──► │  ai_credits_used  (used) │
 └──────────────┘                                             │  available_credits (calc)│
                                                              └──────────────────────────┘
                                                                              │
                                                              ┌───────────────▼──────────┐
                                                              │   credit_adjustments      │
                                                              │   (immutable audit log)   │
                                                              └──────────────────────────┘
```

---

## 3. Plan Credit Limits (Authoritative Values)

These values live in `config/safarichat_billing.php` AND are duplicated inside the PostgreSQL trigger (see Section 7) to avoid PHP/DB sync issues.

| Plan        | Monthly AI Credits | Contacts | Products | WhatsApp Channels |
|-------------|-------------------|----------|----------|-------------------|
| `free_trial`| 0                 | 10       | 1        | 1                 |
| `starter`   | 69,000            | 150      | 5        | 1                 |
| `pro`       | 149,000           | 350      | 50       | 3                 |
| `premium`   | 299,000           | 4,000    | 200      | 7                 |

**Token-to-credit conversion:**
```
1 AI credit = 4 OpenAI tokens
credits_used = ceil(total_tokens / 4)

Example — typical exchange:
  User message:  ~150 tokens
  AI reply:      ~300 tokens
  Total:          450 tokens ÷ 4 = 113 credits per exchange

At Pro (149,000 credits/month):
  149,000 ÷ 113 ≈ 1,318 full AI exchanges per month
```

---

## 4. Current Bugs (Must Fix)

### Bug 1 — Subscription renewal stacks credits instead of resetting

**File:** `app/Http/Controllers/BillingWebhookController.php`

```php
// CURRENT (BROKEN)
$billingAccount->increment('ai_credits', $credits);     // ← stacks on every renewal

// After month 1 (Pro):  ai_credits = 149,000
// After month 2 (Pro):  ai_credits = 298,000  ← wrong
// After month 3 (Pro):  ai_credits = 447,000  ← ballooning
```

**Why this breaks the platform:** A business that has been subscribed for 6 months would show 894,000 credits — 6x what they should have. They could use AI features for months after canceling. This is both a financial leak and a feature-access control failure.

**Fix:** Replace `increment` with a proper renewal calculation that sets `base_credits` to the plan limit and resets `ai_credits_used` to zero (handled by trigger in Section 7).

---

### Bug 2 — `available_credits` is manually updated in 3 places and goes out of sync

**Files:** `BillingWebhookController.php`, `TokenUsageService.php`, `BillingService.php`

```php
// Three different places doing this manually:
$billingAccount->update(['available_credits' => $billingAccount->ai_credits - $billingAccount->ai_credits_used]);
```

**Why this breaks the platform:** If any one of those three updates fails (network error, exception, race condition), the displayed credit balance is wrong. Users see incorrect numbers. Customer support gets tickets. Worse — `canUseAiFeatures()` reads `available_credits` directly, so a sync error can either block a paying customer or let an over-limit customer keep using AI.

**Fix:** Make `available_credits` a PostgreSQL `GENERATED ALWAYS AS` column so it is physically impossible for it to go out of sync. Remove all manual updates.

---

### Bug 3 — `ai_credits_used` is never reset at the start of a new billing cycle

**Current behavior:** `ai_credits_used` is only ever incremented, never reset. The value grows forever.

```
Month 1: ai_credits=149000, ai_credits_used=85000  → available=64000  ✅
Month 2 (renewal): ai_credits=149000, ai_credits_used=85000 (NOT reset!)
  → available = 149000 - 85000 = 64000 ← starts month 2 with 64k not 149k  ❌
Month 3: ai_credits_used=255000 (accumulated)
  → available = 149000 - 255000 = -106000 ← negative, AI stops working ❌
```

**Why this breaks the platform:** Every customer will eventually exhaust their credits and lose AI access mid-subscription — even if they are fully paid up. This is the highest-impact bug currently in the system.

**Fix:** Trigger resets `ai_credits_used = 0` on every detected renewal.

---

### Bug 4 — No subscription expiry enforcement

**Current behavior:** `subscription_expires_at` is stored in the DB but no scheduled job checks it. Expired accounts keep all features indefinitely.

**Why this breaks the platform:** A business that cancels or whose subscription lapses continues to get AI replies, WhatsApp automation, and full contact limits without paying. Direct revenue loss.

**Fix:** Add a scheduled command (see Section 8) that runs daily and downgrades expired accounts.

---

### Bug 5 — Renewal detection uses `last_payment_at` change (fragile)

**Current behavior:** The trigger (and webhook handler) uses `last_payment_at` changing as the signal for "this is a renewal."

```php
// Any of these would falsely trigger a credit reset:
$billingAccount->update(['last_payment_at' => $correctedDate]);  // admin correction
$billingAccount->update(['last_payment_at' => now()]);            // failed payment retry log
```

**Why this breaks the platform:** An admin correcting a payment timestamp would accidentally zero out a customer's usage counter mid-cycle. A retry webhook for a failed payment would grant fresh credits without actual payment.

**Fix:** Add a `billing_cycle_id` column. Only change it when a genuine renewal webhook arrives. Trigger fires on `billing_cycle_id` change, not `last_payment_at` change.

---

### Bug 6 — Top-up credits are wiped on renewal

**Current behavior:** When a renewal arrives, `ai_credits` is set to the plan limit. Any extra credits purchased mid-cycle are lost.

```
User buys 5,000 extra credits on Jan 20:  ai_credits = 154,000
Feb 1 renewal arrives:                    ai_credits = 149,000  ← 5,000 vanished ❌
```

**Why this breaks the platform:** Customers pay real money for top-up credits then lose them at renewal. This is a billing integrity issue that will generate chargebacks and support escalations.

**Fix:** Separate `base_credits` (plan grant, reset on renewal) from `topup_credits` (purchased, never wiped).

---

## 5. Multi-Month Subscription Scenario

### How the Billing Platform Behaves

The billing platform sends **one `subscription.renewed` webhook per month period**, regardless of how many months the customer paid upfront. A customer who pays for 2 months upfront receives:

```
Jan 1  — subscription.created  → billing_cycle_id="sub_abc_202501"
Feb 1  — subscription.renewed  → billing_cycle_id="sub_abc_202502"
```

This means each month boundary triggers a new webhook, which updates `billing_cycle_id`, which fires the DB trigger, which resets `base_credits` and `ai_credits_used=0` automatically. No scheduler intervention needed for credit resets.

```
  MONTH 1           MONTH 2           MONTH 3
  Jan 1 ─────────── Feb 1 ─────────── Mar 1 ─────────── Apr 1
  │                 │                 │                  │
  webhook fires     webhook fires     webhook fires      fully expired
  cycle_id changes  cycle_id changes  cycle_id changes
  used=0 ✅         used=0 ✅         used=0 ✅
  credits=149k      credits=149k      credits=149k
```

### Idempotency Guard — Handling Duplicate or Retry Webhooks

The billing platform may deliver the same webhook more than once (network retry, duplicate delivery). Without a guard, a duplicate `subscription.renewed` for the same month would fire the trigger again, wiping `ai_credits_used` mid-cycle.

The webhook handler must always check before updating `billing_cycle_id`:

```php
// In handleSubscriptionRenewed():
$newCycleId = $payload['subscription']['id'] . '_' . now()->format('Ym');

if ($billingAccount->billing_cycle_id === $newCycleId) {
    // Same billing_cycle_id — this is a duplicate/retry delivery for a cycle
    // we already processed. Ignore silently.
    Log::info('Duplicate renewal webhook ignored', [
        'billing_account_id' => $billingAccount->id,
        'cycle_id'           => $newCycleId,
    ]);
    return response()->json(['status' => 'already_processed']);
}

// New cycle — write signal columns, trigger handles the rest
$billingAccount->update([
    'billing_cycle_id'        => $newCycleId,
    'subscription_plan'       => $normalizedPlan,
    'subscription_status'     => 'active',
    'subscription_expires_at' => $payload['subscription']['ends_at'],
    'last_payment_at'         => now(),
    'last_transaction_id'     => $payload['transaction_id'],
    // trigger sets: base_credits=plan_limit, ai_credits_used=0
]);
```

The format `Ym` (e.g., `202502`) is the same value all day within a month, so even if the webhook arrives at 00:01 and a retry arrives at 23:59 the same calendar month, both produce the same `billing_cycle_id` and only the first one processes.

---

## 6. Recommended Table Schema Changes

```sql
-- Add to billing_accounts
ALTER TABLE billing_accounts
    ADD COLUMN IF NOT EXISTS base_credits     BIGINT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS topup_credits    BIGINT NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS billing_cycle_id VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS credits_rollover BOOLEAN NOT NULL DEFAULT FALSE;
-- Note: current_cycle_start / current_cycle_end / subscription_months are NOT needed.
-- The billing platform sends one webhook per month period, so each month's
-- credit reset is driven by the incoming webhook changing billing_cycle_id.

-- Migrate existing data to new columns
UPDATE billing_accounts SET
    base_credits  = COALESCE(ai_credits, 0),
    topup_credits = 0;

-- Replace manual available_credits with a generated column (impossible to desync)
ALTER TABLE billing_accounts DROP COLUMN IF EXISTS available_credits;
ALTER TABLE billing_accounts
    ADD COLUMN available_credits BIGINT
    GENERATED ALWAYS AS (
        GREATEST(0, base_credits + topup_credits - ai_credits_used)
    ) STORED;

-- Keep ai_credits as backward-compatible computed sum
-- (many existing queries read ai_credits — update them gradually)
```

---

## 7. Audit Table Schema

```sql
CREATE TABLE IF NOT EXISTS credit_adjustments (
    id                     BIGSERIAL PRIMARY KEY,
    billing_account_id     BIGINT      NOT NULL REFERENCES billing_accounts(id),
    adjustment_type        VARCHAR(32) NOT NULL,
        -- values: 'renewal' | 'upgrade' | 'downgrade' | 'topup' | 'correction' | 'expiry'
    plan_before            VARCHAR(32),
    plan_after             VARCHAR(32),
    base_credits_before    BIGINT,
    base_credits_after     BIGINT,
    topup_credits_before   BIGINT,
    topup_credits_after    BIGINT,
    ai_credits_used_before BIGINT,
    ai_credits_used_after  BIGINT,
    billing_cycle_id       VARCHAR(64),
    notes                  TEXT,
    created_at             TIMESTAMP WITHOUT TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_credit_adj_account ON credit_adjustments(billing_account_id);
CREATE INDEX idx_credit_adj_type    ON credit_adjustments(adjustment_type);
CREATE INDEX idx_credit_adj_created ON credit_adjustments(created_at DESC);
```

**Why this matters:** Without an immutable audit log you cannot:
- Answer "when exactly did this customer's credits reset?"
- Debug why a customer says they had credits yesterday but not today
- Produce an accurate billing dispute response

---

## 8. PostgreSQL Trigger (The Credit Brain)

This trigger runs `BEFORE UPDATE` on `billing_accounts` and is the single authoritative place where credit rules are enforced. No PHP code should set credit values directly — it updates the signal columns (`billing_cycle_id`, `topup_credits`, `subscription_plan`) and the trigger does the rest.

```sql
CREATE OR REPLACE FUNCTION billing_credits_manager()
RETURNS TRIGGER LANGUAGE plpgsql AS $$
DECLARE
    v_plan_limit       BIGINT;
    v_rollover_credits BIGINT;
    v_adjustment_type  VARCHAR(32);
BEGIN
    -- Guard: only manage credits on active subscriptions
    IF NEW.subscription_status != 'active' THEN
        RETURN NEW;
    END IF;

    -- ── Plan credit limit lookup ──────────────────────────────────────────────
    -- Duplicated from config/safarichat_billing.php intentionally.
    -- If plan limits change, update BOTH this trigger AND the PHP config.
    v_plan_limit := CASE NEW.subscription_plan
        WHEN 'free_trial' THEN 0
        WHEN 'starter'    THEN 69000
        WHEN 'pro'        THEN 149000
        WHEN 'premium'    THEN 299000
        ELSE 0
    END;

    -- ══════════════════════════════════════════════════════════════════════════
    -- SCENARIO 1: Subscription Renewal (same plan, new billing cycle)
    -- Signal: billing_cycle_id changed + plan unchanged
    -- ══════════════════════════════════════════════════════════════════════════
    IF NEW.billing_cycle_id IS DISTINCT FROM OLD.billing_cycle_id
       AND (NEW.subscription_plan = OLD.subscription_plan
            OR OLD.subscription_plan IS NULL) THEN

        v_adjustment_type := 'renewal';

        IF NEW.credits_rollover IS TRUE THEN
            -- Carry forward unused base credits only (not topup — those persist anyway)
            v_rollover_credits := GREATEST(0, OLD.base_credits - OLD.ai_credits_used);
            NEW.base_credits   := v_plan_limit + v_rollover_credits;
        ELSE
            NEW.base_credits   := v_plan_limit;
        END IF;

        -- Reset usage counter for new cycle
        NEW.ai_credits_used := 0;
        -- topup_credits are NEVER cleared on renewal

    -- ══════════════════════════════════════════════════════════════════════════
    -- SCENARIO 2: Plan Upgrade or Downgrade
    -- Signal: subscription_plan changed
    -- ══════════════════════════════════════════════════════════════════════════
    ELSIF NEW.subscription_plan IS DISTINCT FROM OLD.subscription_plan THEN

        v_adjustment_type := CASE
            WHEN v_plan_limit > COALESCE(OLD.base_credits, 0) THEN 'upgrade'
            ELSE 'downgrade'
        END;

        -- Fresh allocation on new plan — old cycle usage does NOT carry over.
        -- A customer upgrading from Starter to Pro gets the full 149k,
        -- not 149k minus whatever they used on Starter. This is correct:
        -- they paid for a new plan, they get a new plan's full credits.
        NEW.base_credits    := v_plan_limit;
        NEW.ai_credits_used := 0;
        -- topup_credits preserved on plan change too

    -- ══════════════════════════════════════════════════════════════════════════
    -- SCENARIO 3: Top-up Credit Purchase
    -- Signal: topup_credits increased
    -- No other fields need changing. available_credits auto-updates because
    -- it is a GENERATED column: (base + topup - used).
    -- ══════════════════════════════════════════════════════════════════════════
    ELSIF NEW.topup_credits > OLD.topup_credits THEN
        v_adjustment_type := 'topup';

    ELSE
        -- No credit-relevant field changed — nothing to do
        RETURN NEW;
    END IF;

    -- ── Sync legacy ai_credits column for backward compatibility ──────────────
    -- Many existing queries read ai_credits. Keep it as the sum until
    -- all call sites are updated to use available_credits.
    NEW.ai_credits := NEW.base_credits + NEW.topup_credits;

    -- ── Write immutable audit record ──────────────────────────────────────────
    INSERT INTO credit_adjustments (
        billing_account_id,
        adjustment_type,
        plan_before,            plan_after,
        base_credits_before,    base_credits_after,
        topup_credits_before,   topup_credits_after,
        ai_credits_used_before, ai_credits_used_after,
        billing_cycle_id,
        notes
    ) VALUES (
        NEW.id,
        v_adjustment_type,
        OLD.subscription_plan,  NEW.subscription_plan,
        OLD.base_credits,       NEW.base_credits,
        OLD.topup_credits,      NEW.topup_credits,
        OLD.ai_credits_used,    NEW.ai_credits_used,
        NEW.billing_cycle_id,
        'Managed by billing_credits_manager trigger v1.0'
    );

    RETURN NEW;
END;
$$;

-- Attach trigger to table
DROP TRIGGER IF EXISTS trg_billing_credits_manager ON billing_accounts;
CREATE TRIGGER trg_billing_credits_manager
    BEFORE UPDATE ON billing_accounts
    FOR EACH ROW EXECUTE FUNCTION billing_credits_manager();
```

---

## 9. Webhook Controller — How PHP Must Talk to the Trigger

The PHP webhook controller must stop doing credit arithmetic itself. Instead it writes the signal columns and lets the trigger calculate everything.

### subscription.created / subscription.renewed

```php
// BEFORE (broken — arithmetic in PHP):
$billingAccount->increment('ai_credits', $planCredits);
$billingAccount->update(['available_credits' => ...]);  // manual, breaks often

// AFTER (correct — signal columns only, trigger handles the rest):
$billingAccount->update([
    'subscription_plan'        => $normalizedPlan,           // signals plan change if changed
    'billing_cycle_id'         => $payload['subscription']['id'], // signals renewal
    'subscription_status'      => 'active',
    'subscription_expires_at'  => $payload['subscription']['ends_at'],
    'last_payment_at'          => now(),
    'last_transaction_id'      => $payload['transaction_id'],
    // trigger sets: base_credits, ai_credits_used=0, ai_credits, available_credits
]);
```

### credits.purchased

```php
// Increment topup_credits ONLY — never touch base_credits or available_credits directly
$billingAccount->increment('topup_credits', $payload['wallet_transaction']['units']);
// trigger detects topup_credits increased → logs to credit_adjustments
// available_credits auto-updates via GENERATED column
```

### Credit consumption (TokenUsageService)

```php
// Increment used counter only — available_credits recomputes automatically
$billingAccount->increment('ai_credits_used', $creditsConsumed);
// No need to update available_credits — it is a generated column

// Guard check reads the generated column
public function canUseAiFeatures(BillingAccount $account): bool
{
    return $account->available_credits > 0
        && $account->subscription_status === 'active'
        && $account->subscription_expires_at?->isFuture();
}
```

---

## 10. Expiry Enforcement Scheduler

The scheduler has a single responsibility: mark subscriptions as `expired` when `subscription_expires_at` is reached. Monthly credit resets are fully handled by incoming webhooks (Section 9) — the scheduler is not involved in that.

Add to `app/Console/Commands/EnforceSubscriptionExpiry.php`:

```php
// Runs: php artisan billing:enforce-expiry
// Schedule: daily at 00:05

public function handle(): void
{
    BillingAccount::where('subscription_status', 'active')
        ->where('subscription_expires_at', '<', now())
        ->each(function ($account) {
            $account->update(['subscription_status' => 'expired']);

            CreditAdjustment::create([
                'billing_account_id' => $account->id,
                'adjustment_type'    => 'expiry',
                'plan_before'        => $account->subscription_plan,
                'plan_after'         => $account->subscription_plan,
                'notes'              => 'Subscription fully expired — status set to expired',
            ]);

            $account->business->notify(new SubscriptionExpiredNotification());
        });
}
```

Register in `app/Console/Kernel.php`:
```php
$schedule->command('billing:enforce-expiry')->dailyAt('00:05');
```

---

## 11. Summary — Why Each Change is Critical

| # | Current Problem | Risk Level | Fix |
|---|---|---|---|
| 1 | Renewal stacks credits (never resets) | **CRITICAL** — Paying customers get infinite credits over time; cancelled customers keep working | Trigger resets `base_credits` to plan limit on `billing_cycle_id` change |
| 2 | `available_credits` manually synced in 3 places | **HIGH** — Any exception causes wrong balance display; AI blocks paying customers | `GENERATED ALWAYS AS` column — impossible to desync |
| 3 | `ai_credits_used` never reset | **CRITICAL** — Every customer eventually loses AI access mid-subscription | Trigger sets `ai_credits_used = 0` on renewal |
| 4 | Top-up credits wiped on renewal | **HIGH** — Customers lose purchased credits they paid for | Separate `topup_credits` column never cleared on renewal |
| 5 | Renewal detected by `last_payment_at` | **MEDIUM** — Admin corrections trigger accidental credit resets | Use `billing_cycle_id` (unique per subscription period) |
| 6 | No subscription expiry enforcement | **HIGH** — Lapsed accounts keep full AI and feature access | Daily `billing:enforce-expiry` scheduled command |
| 7 | No credit audit trail | **MEDIUM** — Cannot debug billing disputes or support tickets | `credit_adjustments` table written inside trigger |
| 8 | Duplicate webhook delivery could double-reset credits mid-cycle | **MEDIUM** — Billing platform retries cause `ai_credits_used` to reset unexpectedly | Idempotency guard in webhook handler: skip if `billing_cycle_id` already equals new value |

---

## 12. Implementation Order

1. **Run migration** — add `base_credits`, `topup_credits`, `billing_cycle_id`, `credits_rollover` columns; convert `available_credits` to generated column; create `credit_adjustments` table
2. **Deploy trigger** — `billing_credits_manager()` function + `trg_billing_credits_manager`
3. **Update BillingWebhookController** — remove all `increment('ai_credits', ...)` and manual `available_credits` calculations; write signal columns only; add idempotency guard on `billing_cycle_id` before every update
4. **Update TokenUsageService** — only `increment('ai_credits_used', ...)`, remove `available_credits` manual update
5. **Update BillingService::canUseAiFeatures()** — add `subscription_expires_at` check
6. **Create and register** `billing:enforce-expiry` console command (expiry only — no multi-month logic needed)
7. **Backfill** existing `billing_accounts` rows:
   ```sql
   UPDATE billing_accounts SET
       base_credits      = COALESCE(ai_credits, 0),
       topup_credits     = 0,
       billing_cycle_id  = 'legacy-' || id::text
   WHERE base_credits = 0;
   ```
8. **Test** these scenarios in staging before production deploy:
   - Monthly subscription: webhook arrives → credits reset ✅
   - 2-month subscription: two webhooks arrive one month apart → credits reset each time ✅
   - Duplicate webhook for same month → idempotency guard skips it, credits untouched ✅
   - Top-up mid-cycle → not wiped on next monthly reset ✅
   - Upgrade mid-cycle → gets full new plan credits ✅
   - Expiry → AI stops ✅
