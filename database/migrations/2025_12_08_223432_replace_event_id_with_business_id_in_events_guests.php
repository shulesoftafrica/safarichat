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
        // Step 1: Add business_id column
        Schema::table('events_guests', function (Blueprint $table) {
            if (!Schema::hasColumn('events_guests', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable()->after('event_id');
                $table->index('business_id');
            }
        });

        // Step 2: Migrate data from event_id to business_id
        echo "Migrating data from event-based to business-based architecture...\n";
        
        // Strategy 1: Map events to businesses via users_events -> users -> businesses
        DB::statement('
            UPDATE events_guests 
            SET business_id = b.id
            FROM events e 
            JOIN users_events ue ON ue.event_id = e.id
            JOIN businesses b ON b.user_id = ue.user_id
            WHERE e.id = events_guests.event_id 
            AND events_guests.business_id IS NULL
        ');
        
        // Strategy 2: Create missing business records for orphaned events
        $orphanedEvents = DB::select('
            SELECT DISTINCT e.id as event_id, ue.user_id, u.name, u.email, u.phone
            FROM events_guests eg
            JOIN events e ON e.id = eg.event_id
            JOIN users_events ue ON ue.event_id = e.id
            JOIN users u ON u.id = ue.user_id
            LEFT JOIN businesses b ON b.user_id = ue.user_id
            WHERE eg.business_id IS NULL AND b.id IS NULL
        ');
        
        foreach ($orphanedEvents as $event) {
            // Create business record for the user
            $businessId = DB::table('businesses')->insertGetId([
                'name' => $event->name ?? 'Business for ' . $event->email,
                'phone' => $event->phone ?? '',
                'email' => $event->email ?? '',
                'user_id' => $event->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Update events_guests with new business_id for this event
            DB::table('events_guests')
                ->where('event_id', $event->event_id)
                ->whereNull('business_id')
                ->update(['business_id' => $businessId]);
            
            echo "Created business ID $businessId for event ID {$event->event_id}\n";
        }
        
        // Step 3: Add foreign key constraint
        Schema::table('events_guests', function (Blueprint $table) {
            if (Schema::hasColumn('events_guests', 'business_id')) {
                $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            }
        });
        
        // Step 4: Verify data migration
        $totalRecords = DB::table('events_guests')->count();
        $withBusinessId = DB::table('events_guests')->whereNotNull('business_id')->count();
        
        echo "Migration completed:\n";
        echo "- Total events_guests records: $totalRecords\n";
        echo "- Records with business_id: $withBusinessId\n";
        
        if ($totalRecords > 0 && $withBusinessId < $totalRecords) {
            echo "Warning: " . ($totalRecords - $withBusinessId) . " records still missing business_id\n";
        }
        
        // Step 5: Optional - Drop event_id column (uncomment when ready)
        // Schema::table('events_guests', function (Blueprint $table) {
        //     $table->dropForeign(['event_id']);
        //     $table->dropColumn('event_id');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key constraint first
        Schema::table('events_guests', function (Blueprint $table) {
            if (Schema::hasColumn('events_guests', 'business_id')) {
                $table->dropForeign(['business_id']);
            }
        });
        
        // Drop business_id column
        Schema::table('events_guests', function (Blueprint $table) {
            if (Schema::hasColumn('events_guests', 'business_id')) {
                $table->dropColumn('business_id');
            }
        });
        
        // Note: event_id column remains as it was the original column
        // Data migration cannot be perfectly reversed without data loss
        
        echo "Rollback completed: business_id column removed, event_id column preserved\n";
    }
};
