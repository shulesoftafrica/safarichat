<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Credit Management System — Step 3
 *
 * Creates the PostgreSQL BEFORE UPDATE trigger on billing_accounts.
 *
 * This trigger is the single authoritative place where credit rules are enforced.
 * PHP code writes signal columns only; the trigger does all credit arithmetic.
 *
 * Scenarios handled:
 *   1. Renewal  — billing_cycle_id changes, same plan → reset base_credits to plan limit,
 *                 ai_credits_used = 0 (optionally carry forward unused credits if rollover=true)
 *   2. Upgrade  — subscription_plan changes to higher tier → fresh plan limit, usage reset
 *   3. Downgrade — subscription_plan changes to lower tier → new plan limit, usage reset
 *   4. Top-up   — topup_credits increased → log audit entry, no other changes needed
 *                 (available_credits auto-updates via GENERATED column)
 *
 * Plan limits are intentionally duplicated from config/safarichat_billing.php.
 * If plan limits change: update BOTH this trigger AND the PHP config.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Create the trigger function ───────────────────────────────────────────
        DB::unprepared("
CREATE OR REPLACE FUNCTION billing_credits_manager()
RETURNS TRIGGER LANGUAGE plpgsql AS \$\$
DECLARE
    v_plan_limit       BIGINT;
    v_rollover_credits BIGINT;
    v_adjustment_type  VARCHAR(32);
BEGIN
    -- Guard: only manage credits on active subscriptions.
    -- Cancelled, expired, or trial accounts are not touched.
    IF NEW.subscription_status != 'active' THEN
        RETURN NEW;
    END IF;

    -- ── Plan credit limit lookup ──────────────────────────────────────────────
    -- Values must mirror config/safarichat_billing.php exactly.
    -- Update BOTH when changing plan limits.
    v_plan_limit := CASE NEW.subscription_plan
        WHEN 'free_trial' THEN 0
        WHEN 'starter'    THEN 69000
        WHEN 'pro'        THEN 149000
        WHEN 'premium'    THEN 299000
        ELSE 0
    END;

    -- ══════════════════════════════════════════════════════════════════════════
    -- SCENARIO 1: Subscription Renewal (same plan, new billing cycle)
    -- Signal: billing_cycle_id changed AND plan is the same (or first subscription)
    -- ══════════════════════════════════════════════════════════════════════════
    IF NEW.billing_cycle_id IS DISTINCT FROM OLD.billing_cycle_id
       AND (NEW.subscription_plan = OLD.subscription_plan
            OR OLD.subscription_plan IS NULL
            OR OLD.billing_cycle_id LIKE 'legacy-%') THEN

        v_adjustment_type := 'renewal';

        IF NEW.credits_rollover IS TRUE THEN
            -- Carry forward only unused BASE credits (topup_credits persist anyway)
            v_rollover_credits := GREATEST(0, OLD.base_credits - OLD.ai_credits_used);
            NEW.base_credits   := v_plan_limit + v_rollover_credits;
        ELSE
            NEW.base_credits := v_plan_limit;
        END IF;

        -- Reset usage counter for new cycle
        NEW.ai_credits_used := 0;
        -- topup_credits are NEVER cleared on renewal — they carry forward indefinitely

    -- ══════════════════════════════════════════════════════════════════════════
    -- SCENARIO 2 & 3: Plan Upgrade or Downgrade
    -- Signal: subscription_plan changed
    -- ══════════════════════════════════════════════════════════════════════════
    ELSIF NEW.subscription_plan IS DISTINCT FROM OLD.subscription_plan THEN

        v_adjustment_type := CASE
            WHEN v_plan_limit > COALESCE(OLD.base_credits, 0) THEN 'upgrade'
            ELSE 'downgrade'
        END;

        -- Fresh allocation on new plan.
        -- Upgrade: customer pays for new plan, gets full new plan credits — old usage
        --          does NOT carry over (they paid for a new plan).
        -- Downgrade: same principle — fresh start on new lower plan.
        NEW.base_credits    := v_plan_limit;
        NEW.ai_credits_used := 0;
        -- topup_credits preserved through plan changes too

    -- ══════════════════════════════════════════════════════════════════════════
    -- SCENARIO 4: Top-up Credit Purchase
    -- Signal: topup_credits increased
    -- available_credits auto-updates via GENERATED column — no other changes needed.
    -- ══════════════════════════════════════════════════════════════════════════
    ELSIF NEW.topup_credits > OLD.topup_credits THEN
        v_adjustment_type := 'topup';

    ELSE
        -- No credit-relevant field changed — nothing to do, skip audit log
        RETURN NEW;
    END IF;

    -- ── Sync legacy ai_credits column for backward compatibility ──────────────
    -- Many existing queries and PHP code paths read ai_credits directly.
    -- Keep it as the sum of base + topup until all call sites are migrated
    -- to use available_credits.
    NEW.ai_credits := COALESCE(NEW.base_credits, 0) + COALESCE(NEW.topup_credits, 0);

    -- ── Write immutable audit record ──────────────────────────────────────────
    INSERT INTO credit_adjustments (
        billing_account_id,
        adjustment_type,
        plan_before,             plan_after,
        base_credits_before,     base_credits_after,
        topup_credits_before,    topup_credits_after,
        ai_credits_used_before,  ai_credits_used_after,
        billing_cycle_id,
        notes
    ) VALUES (
        NEW.id,
        v_adjustment_type,
        OLD.subscription_plan,   NEW.subscription_plan,
        OLD.base_credits,        NEW.base_credits,
        OLD.topup_credits,       NEW.topup_credits,
        OLD.ai_credits_used,     NEW.ai_credits_used,
        NEW.billing_cycle_id,
        'Auto-managed by billing_credits_manager trigger v1.0'
    );

    RETURN NEW;
END;
\$\$;
        ");

        // ── Attach trigger to billing_accounts table ──────────────────────────────
        DB::unprepared("
DROP TRIGGER IF EXISTS trg_billing_credits_manager ON billing_accounts;
CREATE TRIGGER trg_billing_credits_manager
    BEFORE UPDATE ON billing_accounts
    FOR EACH ROW EXECUTE FUNCTION billing_credits_manager();
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS trg_billing_credits_manager ON billing_accounts");
        DB::unprepared("DROP FUNCTION IF EXISTS billing_credits_manager()");
    }
};
