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
        Schema::create('billing_accounts', function (Blueprint $table) {
            $table->id();
            
            // Polymorphic relationship - can belong to User or Business
            $table->string('owner_type', 50); // 'App\Models\User' or 'App\Models\Business'
            $table->unsignedBigInteger('owner_id');
            $table->index(['owner_type', 'owner_id'], 'billing_owner_index');
            
            // Subscription details
            $table->string('subscription_plan', 20)->default('trial'); // trial, starter, pro, premium
            $table->timestamp('subscription_started_at')->nullable();
            $table->timestamp('subscription_expires_at')->nullable();
            $table->timestamp('last_billing_date')->nullable();
            $table->timestamp('next_billing_date')->nullable();
            
            // Credits and usage
            $table->bigInteger('ai_credits')->default(0); // Available AI credits
            $table->bigInteger('ai_credits_used')->default(0); // Total credits used (for analytics)
            $table->bigInteger('available_credits')->default(0); // Alternative credit field (deprecated, keeping for compatibility)
            
            // Feature limits (cached from config for performance)
            $table->integer('max_contacts')->default(10);
            $table->integer('max_products')->default(1);
            $table->integer('whatsapp_channels')->default(1);
            $table->boolean('customer_followups')->default(false);
            $table->boolean('customer_categorization')->default(false);
            $table->boolean('booking_calendars')->default(false);
            $table->boolean('sales_reports')->default(false);
            $table->boolean('unlimited_messages')->default(false);
            
            // Billing status
            $table->string('status', 20)->default('active'); // active, suspended, cancelled, expired
            $table->boolean('credits_rollover')->default(false);
            $table->text('notes')->nullable(); // Admin notes
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete for audit trail
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_accounts');
    }
};
