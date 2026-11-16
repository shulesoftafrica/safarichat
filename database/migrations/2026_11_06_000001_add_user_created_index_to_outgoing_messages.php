<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Per-client message history (the ShuleSoft admin panel's SafariChat client page reads the
 * latest messages for one user) had no index to use on outgoing_messages (~1M rows).
 * Built CONCURRENTLY so sending is never blocked, which cannot run inside a transaction.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS outgoing_messages_user_id_created_at_index ON outgoing_messages (user_id, created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX CONCURRENTLY IF EXISTS outgoing_messages_user_id_created_at_index');
    }
};
