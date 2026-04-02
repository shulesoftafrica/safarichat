<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Add 'critical_alert' to the notification_queue category CHECK constraint.
     *
     * PostgreSQL CHECK constraints cannot be altered in-place — we drop the old
     * one and re-add it with the new value included.  Laravel's enum() helper is
     * NOT used here because change() on a CHECK-based enum is unreliable in Postgres.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE notification_queue DROP CONSTRAINT IF EXISTS notification_queue_category_check');

        DB::statement("
            ALTER TABLE notification_queue
            ADD CONSTRAINT notification_queue_category_check
            CHECK (category::text = ANY (ARRAY[
                'expiry_warning'::text,
                'payment_success'::text,
                'missed_opportunity'::text,
                'daily_summary'::text,
                'final_warning'::text,
                'critical_alert'::text
            ]))
        ");
    }

    /**
     * Restore the original constraint (without 'critical_alert').
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE notification_queue DROP CONSTRAINT IF EXISTS notification_queue_category_check');

        DB::statement("
            ALTER TABLE notification_queue
            ADD CONSTRAINT notification_queue_category_check
            CHECK (category::text = ANY (ARRAY[
                'expiry_warning'::text,
                'payment_success'::text,
                'missed_opportunity'::text,
                'daily_summary'::text,
                'final_warning'::text
            ]))
        ");
    }
};
