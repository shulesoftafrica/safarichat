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
        Schema::table('outgoing_messages', function (Blueprint $table) {
            // Check if events_guest_id column exists, if not add it
            if (!Schema::hasColumn('outgoing_messages', 'events_guest_id')) {
                $table->unsignedBigInteger('events_guest_id')->nullable()->after('user_id');
                $table->foreign('events_guest_id')->references('id')->on('events_guests')->onDelete('set null');
            }
            
            // Add other missing columns that the application expects
            if (!Schema::hasColumn('outgoing_messages', 'message')) {
                $table->text('message')->nullable()->after('phone_number');
            }
            
            // Ensure batch_id exists (might be missing in some environments)
            if (!Schema::hasColumn('outgoing_messages', 'batch_id')) {
                $table->string('batch_id')->nullable()->after('retry_count');
                $table->index('batch_id');
            }
            
            // Ensure queued_at exists (for queue job tracking)
            if (!Schema::hasColumn('outgoing_messages', 'queued_at')) {
                $table->timestamp('queued_at')->nullable()->after('scheduled_at');
            }
            
            // Add indexes for performance if they don't exist
            $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('outgoing_messages');
            
            if (!array_key_exists('outgoing_messages_events_guest_id_index', $indexes)) {
                $table->index('events_guest_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            // Drop foreign key and column if they exist
            if (Schema::hasColumn('outgoing_messages', 'events_guest_id')) {
                $table->dropForeign(['events_guest_id']);
                $table->dropColumn('events_guest_id');
            }
            
            if (Schema::hasColumn('outgoing_messages', 'message')) {
                $table->dropColumn('message');
            }
            
            if (Schema::hasColumn('outgoing_messages', 'batch_id')) {
                $table->dropIndex(['batch_id']);
                $table->dropColumn('batch_id');
            }
            
            if (Schema::hasColumn('outgoing_messages', 'queued_at')) {
                $table->dropColumn('queued_at');
            }
        });
    }
};
