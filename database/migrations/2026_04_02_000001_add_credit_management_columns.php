<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Credit Management System — Step 1
 *
 * Adds the three new signal columns:
 *   base_credits     — plan allocation for the current cycle (reset by trigger on renewal)
 *   topup_credits    — purchased extras (NEVER reset on renewal)
 *   billing_cycle_id — unique ID per billing period; trigger fires when this changes
 *
 * Converts available_credits from a manually-maintained column to a PostgreSQL
 * GENERATED ALWAYS AS column so it is physically impossible for it to go out of sync.
 *
 * Formula: GREATEST(0, base_credits + topup_credits - ai_credits_used)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Add new signal columns ────────────────────────────────────────────────
        Schema::table('billing_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_accounts', 'base_credits')) {
                $table->bigInteger('base_credits')->default(0)->after('ai_credits_used');
            }
            if (!Schema::hasColumn('billing_accounts', 'topup_credits')) {
                $table->bigInteger('topup_credits')->default(0)->after('base_credits');
            }
            if (!Schema::hasColumn('billing_accounts', 'billing_cycle_id')) {
                $table->string('billing_cycle_id', 64)->nullable()->after('topup_credits');
            }
            if (!Schema::hasColumn('billing_accounts', 'credits_rollover')) {
                $table->boolean('credits_rollover')->default(false)->after('billing_cycle_id');
            }
        });

        // ── Backfill base_credits from current ai_credits balance ─────────────────
        // ai_credits currently holds the full "total granted" value — that maps to base_credits.
        // topup_credits starts at 0 for all existing accounts.
        DB::statement("
            UPDATE billing_accounts
            SET base_credits  = COALESCE(ai_credits, 0),
                topup_credits = 0
            WHERE base_credits = 0
        ");

        // ── Backfill billing_cycle_id for legacy rows ─────────────────────────────
        // Existing rows get a 'legacy-{id}' cycle ID so the trigger can correctly
        // detect future billing_cycle_id changes as genuine new cycles.
        DB::statement("
            UPDATE billing_accounts
            SET billing_cycle_id = CONCAT('legacy-', id::text)
            WHERE billing_cycle_id IS NULL
        ");

        // ── Convert available_credits to PostgreSQL GENERATED ALWAYS AS column ────
        //
        // Regular columns CANNOT be altered to generated columns in PostgreSQL.
        // Strategy: DROP the old column, re-ADD it as GENERATED ALWAYS AS STORED.
        //
        // GENERATED ALWAYS AS STORED means the value is:
        //   - Computed from the formula at write time
        //   - Stored on disk (not recalculated on every read)
        //   - Impossible to set manually → impossible to go out of sync
        //
        // GREATEST(0, ...) ensures we never return a negative balance, even if
        // ai_credits_used somehow exceeds base_credits + topup_credits.
        DB::statement("ALTER TABLE billing_accounts DROP COLUMN IF EXISTS available_credits");
        DB::statement("
            ALTER TABLE billing_accounts
            ADD COLUMN available_credits BIGINT
            GENERATED ALWAYS AS (
                GREATEST(0, base_credits + topup_credits - ai_credits_used)
            ) STORED
        ");
    }

    public function down(): void
    {
        // Restore available_credits as a regular writable column
        DB::statement("ALTER TABLE billing_accounts DROP COLUMN IF EXISTS available_credits");

        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->integer('available_credits')->default(0)->after('ai_credits_used');
        });

        Schema::table('billing_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('billing_accounts', 'base_credits')) {
                $table->dropColumn('base_credits');
            }
            if (Schema::hasColumn('billing_accounts', 'topup_credits')) {
                $table->dropColumn('topup_credits');
            }
            if (Schema::hasColumn('billing_accounts', 'billing_cycle_id')) {
                $table->dropColumn('billing_cycle_id');
            }
        });
    }
};
