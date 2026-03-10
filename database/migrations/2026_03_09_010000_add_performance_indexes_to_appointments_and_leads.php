<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Performance indexes to optimize the View Composer query in AppServiceProvider
     * that runs on every page load with the navigation bar.
     *
     * @return void
     */
    public function up()
    {
        // Add index on appointments table for pending appointments query
        // This optimizes the query: WHERE status = 'pending' AND scheduled_at >= NOW()
        DB::statement('CREATE INDEX IF NOT EXISTS idx_appointments_status_scheduled 
            ON appointments(status, scheduled_at) 
            WHERE status = \'pending\'');
        
        // Add index on leads table for business_id lookups
        // This optimizes the JOIN/whereHas with leads table
        Schema::table('leads', function (Blueprint $table) {
            if (!$this->indexExists('leads', 'idx_leads_business_id')) {
                $table->index('business_id', 'idx_leads_business_id');
            }
        });
        
        // Additional composite index for the complete query pattern
        Schema::table('appointments', function (Blueprint $table) {
            if (!$this->indexExists('appointments', 'idx_appointments_lead_status_scheduled')) {
                $table->index(['lead_id', 'status', 'scheduled_at'], 'idx_appointments_lead_status_scheduled');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the partial index
        DB::statement('DROP INDEX IF EXISTS idx_appointments_status_scheduled');
        
        // Drop the business_id index on leads
        Schema::table('leads', function (Blueprint $table) {
            if ($this->indexExists('leads', 'idx_leads_business_id')) {
                $table->dropIndex('idx_leads_business_id');
            }
        });
        
        // Drop the composite index
        Schema::table('appointments', function (Blueprint $table) {
            if ($this->indexExists('appointments', 'idx_appointments_lead_status_scheduled')) {
                $table->dropIndex('idx_appointments_lead_status_scheduled');
            }
        });
    }

    /**
     * Check if an index exists on a table
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    private function indexExists($table, $index)
    {
        $indexes = DB::select("
            SELECT indexname 
            FROM pg_indexes 
            WHERE tablename = ? AND indexname = ?
        ", [$table, $index]);
        
        return !empty($indexes);
    }
};
