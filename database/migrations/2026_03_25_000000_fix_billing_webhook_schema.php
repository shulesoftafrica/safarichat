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
        // Add missing fields to billing_accounts table
        Schema::table('billing_accounts', function (Blueprint $table) {
            // Add subscription_status field (controller references this instead of 'status')
            $table->string('subscription_status', 20)->default('active')->after('status');
            
            // Add transaction tracking fields
            $table->string('last_transaction_id', 255)->nullable()->after('external_subscription_id');
            $table->timestamp('last_payment_at')->nullable()->after('last_transaction_id');
            $table->decimal('last_payment_amount', 10, 2)->nullable()->after('last_payment_at');
            
            // Index for idempotency checks
            $table->index('last_transaction_id');
        });
        
        // Create webhook events audit trail table
        Schema::create('billing_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50)->index();
            $table->string('transaction_id', 255)->nullable();
            $table->unsignedBigInteger('billing_account_id')->nullable();
            $table->json('payload');
            $table->string('processing_status', 20)->default('processing')->index();
            $table->text('error_message')->nullable();
            $table->string('signature', 255)->nullable();
            $table->ipAddress('source_ip')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            // Indexes for fast lookups
            $table->index('transaction_id');
            $table->index('billing_account_id');
            $table->index('created_at');
            
            // Prevent duplicate processing
            $table->unique(['transaction_id', 'event_type'], 'unique_transaction_event');
            
            // Foreign key
            $table->foreign('billing_account_id')
                ->references('id')
                ->on('billing_accounts')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->dropIndex(['last_transaction_id']);
            $table->dropColumn([
                'subscription_status',
                'last_transaction_id',
                'last_payment_at',
                'last_payment_amount'
            ]);
        });
        
        Schema::dropIfExists('billing_webhook_events');
    }
};
