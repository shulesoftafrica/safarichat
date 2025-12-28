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
        Schema::table('conversations', function (Blueprint $table) {
            // Add detailed token tracking fields
            if (!Schema::hasColumn('conversations', 'input_tokens')) {
                $table->integer('input_tokens')->default(0)->after('tokens_used');
            }
            
            if (!Schema::hasColumn('conversations', 'output_tokens')) {
                $table->integer('output_tokens')->default(0)->after('input_tokens');
            }
            
            // Add billing-related fields
            if (!Schema::hasColumn('conversations', 'credits_deducted')) {
                $table->integer('credits_deducted')->default(0)->after('output_tokens');
            }
            
            if (!Schema::hasColumn('conversations', 'ai_model')) {
                $table->string('ai_model', 50)->nullable()->after('credits_deducted');
            }
            
            if (!Schema::hasColumn('conversations', 'cost_in_credits')) {
                $table->decimal('cost_in_credits', 10, 3)->default(0)->after('ai_model');
            }
            
            if (!Schema::hasColumn('conversations', 'billing_status')) {
                $table->enum('billing_status', ['pending', 'charged', 'failed'])->default('pending')->after('cost_in_credits');
            }
            
            // Add indexes for billing queries
            $table->index(['lead_id', 'billing_status', 'created_at'], 'idx_conversations_billing');
            $table->index(['tokens_used', 'created_at'], 'idx_conversations_tokens_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_conversations_billing');
            $table->dropIndex('idx_conversations_tokens_date');
            
            // Drop columns
            if (Schema::hasColumn('conversations', 'billing_status')) {
                $table->dropColumn('billing_status');
            }
            
            if (Schema::hasColumn('conversations', 'cost_in_credits')) {
                $table->dropColumn('cost_in_credits');
            }
            
            if (Schema::hasColumn('conversations', 'ai_model')) {
                $table->dropColumn('ai_model');
            }
            
            if (Schema::hasColumn('conversations', 'credits_deducted')) {
                $table->dropColumn('credits_deducted');
            }
            
            if (Schema::hasColumn('conversations', 'output_tokens')) {
                $table->dropColumn('output_tokens');
            }
            
            if (Schema::hasColumn('conversations', 'input_tokens')) {
                $table->dropColumn('input_tokens');
            }
        });
    }
};