<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            // Why this failed — drives whether/when to retry
            // Values: instance_disconnected | instance_expired | rate_limited
            //         | invalid_number | bug | unknown
            if (!Schema::hasColumn('outgoing_messages', 'failure_reason')) {
                $table->string('failure_reason', 50)->nullable()->after('error_message');
            }

            // false = permanent failure, never re-queue (e.g. invalid phone number)
            if (!Schema::hasColumn('outgoing_messages', 'retryable')) {
                $table->boolean('retryable')->default(true)->after('failure_reason');
            }

            // When the last manual/automatic retry was dispatched
            if (!Schema::hasColumn('outgoing_messages', 'last_retry_at')) {
                $table->timestamp('last_retry_at')->nullable()->after('retryable');
            }

            // Cap — the retry command won't re-queue past this
            if (!Schema::hasColumn('outgoing_messages', 'max_retries')) {
                $table->unsignedTinyInteger('max_retries')->default(5)->after('last_retry_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            $table->dropColumn(['failure_reason', 'retryable', 'last_retry_at', 'max_retries']);
        });
    }
};
