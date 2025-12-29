<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - SafariChat Billing System Tables
     */
    public function up(): void
    {
        // Credit usage log for audit and reconciliation
        Schema::create('credit_usage_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->decimal('amount', 10, 3); // Credits used
            $table->string('description');
            $table->json('metadata')->nullable(); // Store reservation details, etc.
            $table->timestamp('synced_at')->nullable(); // When it was synced to billing API
            $table->timestamps();
            
            $table->index(['customer_id', 'created_at']);
            $table->index('synced_at');
        });
        
        // Billing reconciliation log for manual review
        Schema::create('billing_reconciliation_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->json('reconciliation_data'); // Store all reconciliation details
            $table->enum('status', ['pending_manual_review', 'reviewed', 'resolved']);
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['customer_id', 'status']);
        });
        
        // Subscription management (extend existing users table functionality)
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'subscription_plan')) {
                $table->enum('subscription_plan', ['trial', 'starter', 'pro', 'premium'])->default('trial');
            }
            if (!Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable();
            }
            if (!Schema::hasColumn('users', 'ai_credits')) {
                $table->unsignedBigInteger('ai_credits')->default(0);
            }
            if (!Schema::hasColumn('users', 'auto_renewal')) {
                $table->boolean('auto_renewal')->default(false);
            }
            if (!Schema::hasColumn('users', 'last_billing_sync')) {
                $table->timestamp('last_billing_sync')->nullable();
            }
        });
        
        // Business plan tracking (extend existing business table)
        if (Schema::hasTable('businesses')) {
            Schema::table('businesses', function (Blueprint $table) {
                if (!Schema::hasColumn('businesses', 'subscription_plan')) {
                    $table->enum('subscription_plan', ['trial', 'starter', 'pro', 'premium'])->default('trial');
                }
                if (!Schema::hasColumn('businesses', 'subscription_expires_at')) {
                    $table->timestamp('subscription_expires_at')->nullable();
                }
                if (!Schema::hasColumn('businesses', 'ai_credits')) {
                    $table->unsignedBigInteger('ai_credits')->default(0);
                }
            });
        }
        
        // Billing events for cache refresh triggers
        Schema::create('billing_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('event_type'); // payment_received, plan_changed, etc.
            $table->json('event_data')->nullable();
            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['customer_id', 'processed']);
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_events');
        Schema::dropIfExists('billing_reconciliation_log');
        Schema::dropIfExists('credit_usage_log');
        
        // Remove billing columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_expires_at', 'ai_credits', 'auto_renewal', 'last_billing_sync']);
        });
        
        // Remove billing columns from business table if it exists
        if (Schema::hasTable('businesses')) {
            Schema::table('businesses', function (Blueprint $table) {
                $table->dropColumn(['subscription_plan', 'subscription_expires_at', 'ai_credits']);
            });
        }
    }
};
