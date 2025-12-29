<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2: Update models and drop legacy event tables
     */
    public function up(): void
    {
        // Step 1: Drop events_types table (after migrating data)
        echo "Dropping events_types table...\n";
        
        // First drop foreign key constraints
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['event_type_id']);
        });
        
        Schema::dropIfExists('events_types');
        
        // Step 2: Make event_type_id nullable in events (preparation for removal)
        Schema::table('events', function (Blueprint $table) {
            $table->integer('event_type_id')->nullable()->change();
        });
        
        // Step 3: Update users_events to be legacy-only (mark for future removal)
        Schema::table('users_events', function (Blueprint $table) {
            $table->boolean('is_legacy')->default(true)->after('status');
            $table->text('migration_notes')->nullable()->after('is_legacy');
        });
        
        echo "Events structure updated for business-centric approach.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate events_types table
        Schema::create('events_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        
        // Restore foreign key
        Schema::table('events', function (Blueprint $table) {
            $table->integer('event_type_id')->nullable(false)->change();
            $table->foreign('event_type_id')->references('id')->on('events_types')->onDelete('cascade');
        });
        
        // Remove legacy columns from users_events
        Schema::table('users_events', function (Blueprint $table) {
            $table->dropColumn(['is_legacy', 'migration_notes']);
        });
    }
};