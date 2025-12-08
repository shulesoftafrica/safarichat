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
        // First, add the business_id column if it doesn't exist
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'business_id')) {
                $table->unsignedBigInteger('business_id')->nullable()->after('user_id');
            }
        });

        // Migrate data from event_id to business_id
        // For each lead, find the business_id based on the events_guest relationship
        DB::statement('
            UPDATE leads 
            SET business_id = (
                SELECT eg.business_id 
                FROM events_guests eg 
                WHERE eg.id = leads.events_guest_id
            )
            WHERE leads.events_guest_id IS NOT NULL
        ');

        // For any leads that might have direct event_id but no events_guest_id relationship
        // Find business through user->business relationship (only if user_id column exists)
        if (Schema::hasColumn('leads', 'user_id')) {
            DB::statement('
                UPDATE leads 
                SET business_id = (
                    SELECT b.id 
                    FROM businesses b 
                    WHERE b.user_id = leads.user_id
                    LIMIT 1
                )
                WHERE leads.business_id IS NULL AND leads.user_id IS NOT NULL
            ');
        }

        // Add foreign key constraint for business_id
        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->index('business_id');
        });

        // Remove the event_id column if it exists
        if (Schema::hasColumn('leads', 'event_id')) {
            Schema::table('leads', function (Blueprint $table) {
                $table->dropColumn('event_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add event_id column back
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('event_id')->nullable()->after('user_id');
        });

        // Migrate data back from business_id to event_id
        // This is a simplified reverse - we'll use user's first event
        DB::statement('
            UPDATE leads 
            SET event_id = (
                SELECT ue.event_id 
                FROM users_events ue 
                WHERE ue.user_id = leads.user_id
                LIMIT 1
            )
            WHERE leads.user_id IS NOT NULL
        ');

        // Remove business_id column and its constraints
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });
    }
};
