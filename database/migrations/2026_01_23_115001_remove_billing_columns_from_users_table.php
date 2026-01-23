<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove deprecated billing columns - billing_accounts table is now the single source of truth
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop deprecated billing columns
            $table->dropColumn([
                'subscription_status',
                'subscription_plan',
                'subscription_expires_at',
                'ai_credits',
                'available_credits',
                'auto_renewal',
                'trial_ends_at',
                'last_billing_sync'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     * Restore billing columns if rollback is needed (data will be lost)
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Restore columns (data cannot be recovered)
            $table->string('subscription_status', 20)->nullable();
            $table->string('subscription_plan', 20)->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->bigInteger('ai_credits')->default(0);
            $table->bigInteger('available_credits')->default(0);
            $table->boolean('auto_renewal')->default(false);
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('last_billing_sync')->nullable();
        });
    }
};
