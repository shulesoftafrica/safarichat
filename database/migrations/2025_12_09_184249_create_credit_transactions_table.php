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
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('admin_payment_id')->nullable();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->enum('transaction_type', ['purchase', 'usage', 'bonus', 'rollover', 'refund', 'sms_purchase', 'sms_usage']);
            $table->integer('credits_amount'); // Can be negative for usage
            $table->integer('tokens_consumed')->nullable(); // For AI usage transactions
            $table->integer('sms_count')->nullable(); // For SMS transactions
            $table->integer('balance_before');
            $table->integer('balance_after');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_payment_id')->references('id')->on('admin_payments')->onDelete('set null');
            $table->foreign('conversation_id')->references('id')->on('conversations')->onDelete('set null');
            
            $table->index(['user_id', 'created_at'], 'idx_user_credits');
            $table->index('transaction_type', 'idx_transaction_type');
            $table->index('admin_payment_id', 'idx_payment_credits');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
