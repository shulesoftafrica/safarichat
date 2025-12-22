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
        Schema::table('handoffs', function (Blueprint $table) {
            // Add default value for reason_code to prevent constraint violations
            $table->string('reason_code')->default('GENERAL_ESCALATION')->change();
            
            // Add default values for other commonly null fields
            $table->string('priority_level')->default('medium')->change();
            $table->string('status')->default('pending')->change();
            
            // Add indexes for better performance
            $table->index(['status', 'priority_level'], 'handoffs_status_priority_index');
            $table->index(['reason_code'], 'handoffs_reason_code_index');
            $table->index(['created_at'], 'handoffs_created_at_index');
        });
        
        // Update any existing records with null reason_code
        \DB::table('handoffs')
            ->whereNull('reason_code')
            ->update(['reason_code' => 'GENERAL_ESCALATION']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('handoffs', function (Blueprint $table) {
            // Remove the indexes
            $table->dropIndex('handoffs_status_priority_index');
            $table->dropIndex('handoffs_reason_code_index');
            $table->dropIndex('handoffs_created_at_index');
            
            // Remove default values (revert to nullable/original state)
            $table->string('reason_code')->nullable()->change();
            $table->string('priority_level')->nullable()->change();
            $table->string('status')->nullable()->change();
        });
    }
};
