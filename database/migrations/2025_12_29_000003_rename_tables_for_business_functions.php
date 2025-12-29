<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 3: Rename tables to reflect actual business functions
     */
    public function up(): void
    {
        // Step 1: Rename events_guests to business_contacts (better reflects actual function)
        if (Schema::hasTable('events_guests')) {
            Schema::rename('events_guests', 'business_contacts');
        }
        
        // Step 2: Rename event_guest_categories to business_contact_categories  
        if (Schema::hasTable('event_guest_categories')) {
            Schema::rename('event_guest_categories', 'business_contact_categories');
        }
        
        // Step 3: Update foreign key column names in business_contacts
        if (Schema::hasTable('business_contacts')) {
            Schema::table('business_contacts', function (Blueprint $table) {
                // Rename event_guest_category_id to contact_category_id
                if (Schema::hasColumn('business_contacts', 'event_guest_category_id')) {
                    $table->renameColumn('event_guest_category_id', 'contact_category_id');
                }
            });
        }
        
        // Step 4: Update related tables that reference the renamed tables
        if (Schema::hasTable('incoming_messages')) {
            Schema::table('incoming_messages', function (Blueprint $table) {
                if (Schema::hasColumn('incoming_messages', 'events_guest_id')) {
                    $table->renameColumn('events_guest_id', 'business_contact_id');
                }
            });
        }
        
        if (Schema::hasTable('messages')) {
            Schema::table('messages', function (Blueprint $table) {
                if (Schema::hasColumn('messages', 'events_guests_id')) {
                    $table->renameColumn('events_guests_id', 'business_contact_id');
                }
            });
        }
        
        if (Schema::hasTable('outgoing_messages')) {
            Schema::table('outgoing_messages', function (Blueprint $table) {
                if (Schema::hasColumn('outgoing_messages', 'events_guest_id')) {
                    $table->renameColumn('events_guest_id', 'business_contact_id');
                }
            });
        }
        
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table) {
                if (Schema::hasColumn('leads', 'events_guest_id')) {
                    $table->renameColumn('events_guest_id', 'business_contact_id');
                }
            });
        }
        
        echo "Tables renamed to reflect business functions:\n";
        echo "- events_guests → business_contacts\n";
        echo "- event_guest_categories → business_contact_categories\n";
        echo "- Updated all foreign key references\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert all table and column renames
        if (Schema::hasTable('business_contacts')) {
            Schema::rename('business_contacts', 'events_guests');
        }
        
        if (Schema::hasTable('business_contact_categories')) {
            Schema::rename('business_contact_categories', 'event_guest_categories');
        }
        
        // Revert column renames
        $tableColumnMappings = [
            'events_guests' => ['contact_category_id' => 'event_guest_category_id'],
            'incoming_messages' => ['business_contact_id' => 'events_guest_id'],
            'messages' => ['business_contact_id' => 'events_guests_id'],
            'outgoing_messages' => ['business_contact_id' => 'events_guest_id'],
            'leads' => ['business_contact_id' => 'events_guest_id']
        ];
        
        foreach ($tableColumnMappings as $table => $columns) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $tableSchema) use ($columns) {
                    foreach ($columns as $newName => $oldName) {
                        if (Schema::hasColumn($tableSchema->getTable(), $newName)) {
                            $tableSchema->renameColumn($newName, $oldName);
                        }
                    }
                });
            }
        }
    }
};