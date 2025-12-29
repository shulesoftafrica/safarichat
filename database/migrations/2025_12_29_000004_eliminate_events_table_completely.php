<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 4: Complete elimination of events table
     * All event functionality is now handled through businesses table
     */
    public function up(): void
    {
        // Step 1: Remove event_id column from business_contacts (no longer needed - we have business_id)
        if (Schema::hasTable('business_contacts') && Schema::hasColumn('business_contacts', 'event_id')) {
            Schema::table('business_contacts', function (Blueprint $table) {
                // Try to drop foreign key constraints if they exist
                $foreignKeys = [
                    'business_contacts_event_id_foreign',
                    'events_guests_event_id_foreign' // old name
                ];
                
                foreach ($foreignKeys as $fkName) {
                    try {
                        DB::statement("ALTER TABLE business_contacts DROP CONSTRAINT IF EXISTS {$fkName}");
                    } catch (Exception $e) {
                        // Constraint doesn't exist, continue
                    }
                }
                
                // Drop the event_id column
                $table->dropColumn('event_id');
            });
            echo "✓ Removed event_id column from business_contacts table\n";
        }

        // Step 2: Drop foreign key constraints on other tables that might reference events
        $tablesToUpdate = ['users_events', 'event_business_mapping', 'business_contact_categories'];
        
        foreach ($tablesToUpdate as $tableName) {
            if (Schema::hasTable($tableName)) {
                // Try different possible foreign key constraint names
                $possibleConstraints = [
                    "{$tableName}_event_id_foreign",
                    "event_guest_category_event_id_foreign", // specific constraint found in error
                    "event_guest_categories_event_id_foreign"
                ];
                
                foreach ($possibleConstraints as $constraint) {
                    try {
                        DB::statement("ALTER TABLE {$tableName} DROP CONSTRAINT IF EXISTS {$constraint}");
                    } catch (Exception $e) {
                        // Constraint doesn't exist, continue
                    }
                }
            }
        }

        // Step 3: Archive any remaining event data to event_business_mapping for historical reference
        if (Schema::hasTable('events')) {
            DB::statement("
                INSERT INTO event_business_mapping (event_id, business_id, created_at, updated_at)
                SELECT e.id, b.id, NOW(), NOW()
                FROM events e
                LEFT JOIN businesses b ON (
                    b.campaign_name = e.name 
                    OR b.id IN (SELECT business_id FROM event_business_mapping WHERE event_id = e.id)
                )
                WHERE b.id IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1 FROM event_business_mapping 
                    WHERE event_id = e.id AND business_id = b.id
                )
            ");
            echo "✓ Archived remaining event-business relationships\n";
        }

        // Step 4: Drop the events table - no longer needed (use CASCADE to handle remaining dependencies)
        if (Schema::hasTable('events')) {
            DB::statement('DROP TABLE IF EXISTS events CASCADE');
            echo "✓ Dropped events table - functionality moved to businesses table\n";
        }

        // Step 5: Add comment to users_events for future reference
        if (Schema::hasTable('users_events')) {
            DB::statement("COMMENT ON TABLE users_events IS 'LEGACY TABLE: Event-user relationships. Consider migrating to business-user relationships via businesses table'");
            echo "✓ Marked users_events as legacy table\n";
        }

        echo "\n=== EVENTS TABLE ELIMINATION COMPLETE ===\n";
        echo "- events table dropped successfully\n";
        echo "- business_contacts no longer depends on events\n";
        echo "- All event functionality consolidated into businesses table\n";
        echo "- Historical event-business mappings preserved in event_business_mapping\n";
        echo "- users_events marked as legacy for future cleanup\n";
    }

    /**
     * Reverse the migrations (recreation would be complex due to data consolidation)
     */
    public function down(): void
    {
        // WARNING: This rollback is complex as we've consolidated data
        // We can restore table structure but not necessarily all original data
        
        // Step 1: Recreate events table structure
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('event_type_id')->nullable();
                $table->string('name');
                $table->date('date');
                $table->string('whatsapp_api_url')->nullable();
                $table->string('whatsapp_token')->nullable();
                $table->unsignedBigInteger('district_id')->nullable();
                $table->string('uid')->nullable();
                $table->string('url')->nullable();
                $table->string('location')->nullable();
                $table->timestamps();
            });
        }

        // Step 2: Add event_id back to business_contacts
        if (Schema::hasTable('business_contacts') && !Schema::hasColumn('business_contacts', 'event_id')) {
            Schema::table('business_contacts', function (Blueprint $table) {
                $table->unsignedBigInteger('event_id')->nullable()->after('id');
            });
        }

        // Step 3: Attempt to restore some event data from event_business_mapping
        if (Schema::hasTable('event_business_mapping') && Schema::hasTable('businesses')) {
            DB::statement("
                INSERT INTO events (id, name, date, created_at, updated_at)
                SELECT 
                    ebm.event_id,
                    b.campaign_name,
                    COALESCE(b.campaign_start_date, NOW()),
                    b.created_at,
                    b.updated_at
                FROM event_business_mapping ebm
                JOIN businesses b ON b.id = ebm.business_id
                WHERE NOT EXISTS (SELECT 1 FROM events WHERE id = ebm.event_id)
                GROUP BY ebm.event_id, b.campaign_name, b.campaign_start_date, b.created_at, b.updated_at
            ");

            // Update business_contacts with event_id from mapping
            DB::statement("
                UPDATE business_contacts bc
                SET event_id = ebm.event_id
                FROM event_business_mapping ebm
                WHERE bc.business_id = ebm.business_id
                AND bc.event_id IS NULL
            ");
        }

        echo "Events table structure restored (data may be incomplete)\n";
    }
};