<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cs_conversation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // 'trial_upgrade' | 'subscription_upgrade' | 'credit_purchase'
            $table->string('context', 50);

            // 'awaiting_package' | 'awaiting_payment' | 'completed' | 'expired'
            $table->string('state', 50);

            // Holds: selected_package_id, invoice_id, amount, plan_code, etc.
            $table->jsonb('payload')->default('{}');

            // Sessions auto-expire after 30 minutes of inactivity
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index(['user_id', 'state']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cs_conversation_sessions');
    }
};
