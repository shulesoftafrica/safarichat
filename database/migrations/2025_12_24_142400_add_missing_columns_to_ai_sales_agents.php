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
        Schema::table('ai_sales_agents', function (Blueprint $table) {
            // Add is_active column that the code expects
            if (!Schema::hasColumn('ai_sales_agents', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
                $table->index('is_active');
            }
            
            // Add allow_outreach column that the code expects
            if (!Schema::hasColumn('ai_sales_agents', 'allow_outreach')) {
                $table->boolean('allow_outreach')->default(true)->after('is_active');
                $table->index('allow_outreach');
            }
        });

        // Update existing records to set is_active based on status
        DB::statement("UPDATE ai_sales_agents SET is_active = CASE WHEN status = 'active' THEN true ELSE false END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_sales_agents', function (Blueprint $table) {
            if (Schema::hasColumn('ai_sales_agents', 'is_active')) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            }
            
            if (Schema::hasColumn('ai_sales_agents', 'allow_outreach')) {
                $table->dropIndex(['allow_outreach']);
                $table->dropColumn('allow_outreach');
            }
        });
    }
};