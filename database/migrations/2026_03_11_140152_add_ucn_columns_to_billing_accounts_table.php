<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->string('subscription_ucn')->nullable()->after('subscription_expires_at');
            $table->string('credit_ucn')->nullable()->after('ai_credits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->dropColumn(['subscription_ucn', 'credit_ucn']);
        });
    }
};
