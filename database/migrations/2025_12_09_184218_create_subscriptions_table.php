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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('admin_package_id');
            $table->enum('status', ['trial', 'active', 'suspended', 'cancelled', 'expired'])->default('trial');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('grace_period_ends')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('admin_package_id')->references('id')->on('admin_packages');
            
            $table->index(['user_id', 'status'], 'idx_user_subscription');
            $table->index(['ends_at', 'status'], 'idx_expiry_check');
            $table->index('trial_ends_at', 'idx_trial_expiry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
