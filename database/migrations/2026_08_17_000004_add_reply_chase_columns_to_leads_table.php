<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add reply/chase tracking columns the Lead model already declares in $fillable
 * but that were missing from the leads table.
 *
 * Their absence broke inbound AI replies: processing an incoming message updates
 * leads.last_reply_at, which threw "column last_reply_at does not exist", the
 * exception was caught, and the controller sent nothing back — so the AI appeared
 * to never respond. NoReplyChaseCommand also relies on last_chase_at / chase_count.
 *
 * Purely additive and idempotent (each column guarded by hasColumn).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'last_reply_at')) {
                $table->timestamp('last_reply_at')->nullable();
            }
            if (!Schema::hasColumn('leads', 'last_chase_at')) {
                $table->timestamp('last_chase_at')->nullable();
            }
            if (!Schema::hasColumn('leads', 'chase_count')) {
                $table->unsignedInteger('chase_count')->default(0);
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            foreach (['last_reply_at', 'last_chase_at', 'chase_count'] as $col) {
                if (Schema::hasColumn('leads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
