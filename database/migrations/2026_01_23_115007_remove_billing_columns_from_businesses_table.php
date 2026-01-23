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
        Schema::table('businesses', function (Blueprint $table) {
            // Drop deprecated billing columns
            $table->dropColumn([
                'subscription_plan',
                'subscription_expires_at',
                'ai_credits'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     * Restore billing columns if rollback is needed (data will be lost)
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Restore columns (data cannot be recovered)
            $table->string('subscription_plan', 20)->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->bigInteger('ai_credits')->default(0);
        });
    }
};
