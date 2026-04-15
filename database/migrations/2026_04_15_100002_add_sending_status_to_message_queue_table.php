<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // PostgreSQL CHECK constraints must be dropped and recreated to add a new allowed value.
        // The original constraint was created by Laravel's enum() with the name message_queue_status_check.
        DB::statement('ALTER TABLE message_queue DROP CONSTRAINT IF EXISTS message_queue_status_check');
        DB::statement("
            ALTER TABLE message_queue
            ADD CONSTRAINT message_queue_status_check
            CHECK (status IN (
                'staged',
                'analyzing',
                'refined',
                'scheduled',
                'sending',
                'sent',
                'failed',
                'human_review',
                'opted_out',
                'cancelled'
            ))
        ");
    }

    public function down()
    {
        DB::statement('ALTER TABLE message_queue DROP CONSTRAINT IF EXISTS message_queue_status_check');
        DB::statement("
            ALTER TABLE message_queue
            ADD CONSTRAINT message_queue_status_check
            CHECK (status IN (
                'staged',
                'analyzing',
                'refined',
                'scheduled',
                'sent',
                'failed',
                'human_review',
                'opted_out',
                'cancelled'
            ))
        ");
    }
};
