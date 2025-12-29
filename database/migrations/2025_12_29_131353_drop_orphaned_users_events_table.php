<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations - Drop orphaned users_events table
     * This table references the removed 'events' table and is no longer functional
     */
    public function up(): void
    {
        // Check if table exists before dropping (safety check)
        if (Schema::hasTable('users_events')) {
            // Archive any existing data if needed (currently only 4 records)
            $existingRecords = DB::table('users_events')->get();
            
            if ($existingRecords->count() > 0) {
                Log::info('Archiving users_events data before table drop', [
                    'record_count' => $existingRecords->count(),
                    'records' => $existingRecords->toArray()
                ]);
            }
            
            // Drop the orphaned table
            Schema::dropIfExists('users_events');
            
            Log::info('Dropped orphaned users_events table - no longer needed after events table removal');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We cannot reverse this migration meaningfully since the events table is gone
        // This migration is part of the final cleanup after database modernization
        throw new \Exception('Cannot reverse users_events table drop - this is part of final database modernization cleanup');
    }
};
