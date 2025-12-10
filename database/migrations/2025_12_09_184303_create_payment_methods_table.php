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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('gateway_type', ['lipa_number', 'stripe']);
            $table->string('merchant_id', 255)->nullable(); // For Lipa Number
            $table->string('stripe_customer_id', 255)->nullable(); // For Stripe
            $table->string('stripe_payment_method_id', 255)->nullable(); // For stored Stripe methods
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Store gateway-specific data
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['user_id', 'is_active'], 'idx_user_payment_methods');
            $table->unique(['user_id', 'is_default', 'is_active'], 'unique_user_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
