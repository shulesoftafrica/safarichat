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
        DB::statement("ALTER TABLE conversations DROP CONSTRAINT IF EXISTS conversations_status_check");
        
        // Add the updated constraint with the active status
        DB::statement("ALTER TABLE conversations ADD CONSTRAINT conversations_status_check CHECK (status IN ('pending', 'processing', 'completed', 'failed', 'active'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original constraint
        DB::statement("ALTER TABLE conversations DROP CONSTRAINT IF EXISTS conversations_status_check");
        DB::statement("ALTER TABLE conversations ADD CONSTRAINT conversations_status_check CHECK (status IN ('pending', 'processing', 'completed', 'failed'))");
    }
};