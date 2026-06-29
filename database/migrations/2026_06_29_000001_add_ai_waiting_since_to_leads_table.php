<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add ai_waiting_since to leads table.
     *
     * This column drives the WAITING_FOR_USER state machine:
     *   - Set to now() immediately after the AI sends any outbound message.
     *   - Cleared (null) when the user sends a new inbound message.
     *
     * Any code path that would generate an AI response MUST check this field
     * before firing. If it is non-null the conversation is still waiting for
     * the user to reply and no new AI message should be sent (exception: a
     * scheduled reminder after 24+ hours have elapsed).
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('ai_waiting_since')->nullable()->after('last_reply_at');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('ai_waiting_since');
        });
    }
};
