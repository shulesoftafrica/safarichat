<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add trial_ends_at to billing_accounts.
     *
     * billing_accounts stores trial expiry in subscription_expires_at for historical
     * reasons.  CS cron commands need an explicit trial_ends_at column so that
     * WHERE clauses can reference it unambiguously (PostgreSQL rejects bare column
     * names when multiple tables are joined in a subquery).
     *
     * Backfill rule: for rows where subscription_plan = 'trial', copy
     * subscription_expires_at → trial_ends_at.  Paid rows stay NULL.
     */
    public function up(): void
    {
        Schema::table('billing_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_accounts', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')
                      ->nullable()
                      ->after('subscription_expires_at')
                      ->comment('When the trial period ends; NULL for paid plans');

                $table->index('trial_ends_at', 'idx_billing_trial_ends');
            }
        });

        // Backfill existing trial rows
        DB::statement("
            UPDATE billing_accounts
            SET trial_ends_at = subscription_expires_at
            WHERE subscription_plan = 'trial'
              AND subscription_expires_at IS NOT NULL
              AND trial_ends_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->dropIndex('idx_billing_trial_ends');
            $table->dropColumn('trial_ends_at');
        });
    }
};
