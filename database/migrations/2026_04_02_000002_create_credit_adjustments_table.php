<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Credit Management System — Step 2
 *
 * Creates the credit_adjustments audit table.
 *
 * Every time the trigger adjusts credits (renewal, plan change, top-up),
 * it inserts one immutable row here. This gives us:
 *   - Full history of when each customer's credits were reset
 *   - Before/after snapshot for billing dispute resolution
 *   - Machine-readable trail for Customer Success dashboards
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_account_id')
                  ->constrained('billing_accounts')
                  ->onDelete('cascade');
            $table->string('adjustment_type', 32);
            // adjustment_type values:
            //   'renewal'    — monthly cycle reset (same plan, new billing_cycle_id)
            //   'upgrade'    — plan changed to a higher tier
            //   'downgrade'  — plan changed to a lower tier
            //   'topup'      — standalone credit purchase added to topup_credits
            //   'correction' — manual admin correction
            //   'expiry'     — subscription expired, written by scheduler

            $table->string('plan_before', 32)->nullable();
            $table->string('plan_after', 32)->nullable();
            $table->bigInteger('base_credits_before')->nullable();
            $table->bigInteger('base_credits_after')->nullable();
            $table->bigInteger('topup_credits_before')->nullable();
            $table->bigInteger('topup_credits_after')->nullable();
            $table->bigInteger('ai_credits_used_before')->nullable();
            $table->bigInteger('ai_credits_used_after')->nullable();
            $table->string('billing_cycle_id', 64)->nullable();
            $table->text('notes')->nullable();

            // Intentionally no updated_at — this is an append-only audit log
            $table->timestamp('created_at')->useCurrent();
        });

        // Indexes for fast lookups from the billing history UI and support queries
        DB::statement('CREATE INDEX idx_credit_adj_account ON credit_adjustments(billing_account_id)');
        DB::statement('CREATE INDEX idx_credit_adj_type    ON credit_adjustments(adjustment_type)');
        DB::statement('CREATE INDEX idx_credit_adj_created ON credit_adjustments(created_at DESC)');
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_adjustments');
    }
};
