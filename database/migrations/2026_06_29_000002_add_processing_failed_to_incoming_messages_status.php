<?php

use Illuminate\Database\Migrations\Migration;

class AddProcessingFailedToIncomingMessagesStatus extends Migration
{
    public function up()
    {
        // PostgreSQL stores Laravel enum() as a CHECK constraint.
        // The original constraint only allowed: received, processed, replied, ignored.
        // The AI processing pipeline also sets 'processing' (atomic claim) and
        // 'failed' (permanent job failure), so we extend the allowed set.
        DB::statement('ALTER TABLE incoming_messages DROP CONSTRAINT IF EXISTS incoming_messages_status_check');

        DB::statement("
            ALTER TABLE incoming_messages
            ADD CONSTRAINT incoming_messages_status_check
            CHECK (status IN ('received', 'processing', 'processed', 'replied', 'ignored', 'failed'))
        ");
    }

    public function down()
    {
        DB::statement('ALTER TABLE incoming_messages DROP CONSTRAINT IF EXISTS incoming_messages_status_check');

        DB::statement("
            ALTER TABLE incoming_messages
            ADD CONSTRAINT incoming_messages_status_check
            CHECK (status IN ('received', 'processed', 'replied', 'ignored'))
        ");
    }
}
