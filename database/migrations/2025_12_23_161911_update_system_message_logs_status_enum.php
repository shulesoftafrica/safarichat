<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to drop and recreate the enum constraint
        DB::statement("ALTER TABLE system_message_logs DROP CONSTRAINT IF EXISTS system_message_logs_status_check");
        DB::statement("ALTER TABLE system_message_logs ADD CONSTRAINT system_message_logs_status_check CHECK (status IN ('queued', 'sent', 'failed', 'delivered', 'read'))");
        
        // Update the default value to 'queued'
        DB::statement("ALTER TABLE system_message_logs ALTER COLUMN status SET DEFAULT 'queued'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original constraint
        DB::statement("ALTER TABLE system_message_logs DROP CONSTRAINT IF EXISTS system_message_logs_status_check");
        DB::statement("ALTER TABLE system_message_logs ADD CONSTRAINT system_message_logs_status_check CHECK (status IN ('sent', 'failed', 'delivered', 'read'))");
        
        // Revert default value
        DB::statement("ALTER TABLE system_message_logs ALTER COLUMN status SET DEFAULT 'sent'");
    }
};
