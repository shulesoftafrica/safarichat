<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('outgoing_messages')) {
            Schema::table('outgoing_messages', function (Blueprint $table) {
                if (!Schema::hasColumn('outgoing_messages', 'selected_channel')) {
                    $table->string('selected_channel', 30)->nullable();
                }
                if (!Schema::hasColumn('outgoing_messages', 'channel_selection_reason')) {
                    $table->string('channel_selection_reason', 120)->nullable();
                }
                if (!Schema::hasColumn('outgoing_messages', 'fallback_chain')) {
                    $table->json('fallback_chain')->nullable();
                }
                if (!Schema::hasColumn('outgoing_messages', 'channel_attempt')) {
                    $table->unsignedTinyInteger('channel_attempt')->default(1);
                }
                if (!Schema::hasColumn('outgoing_messages', 'transport_endpoint')) {
                    $table->string('transport_endpoint')->nullable();
                }
                if (!Schema::hasColumn('outgoing_messages', 'transport_payload')) {
                    $table->json('transport_payload')->nullable();
                }
            });
        }

        if (Schema::hasTable('message_queue')) {
            Schema::table('message_queue', function (Blueprint $table) {
                if (!Schema::hasColumn('message_queue', 'selected_channel')) {
                    $table->string('selected_channel', 30)->nullable();
                }
                if (!Schema::hasColumn('message_queue', 'channel_selection_reason')) {
                    $table->string('channel_selection_reason', 120)->nullable();
                }
                if (!Schema::hasColumn('message_queue', 'fallback_chain')) {
                    $table->json('fallback_chain')->nullable();
                }
                if (!Schema::hasColumn('message_queue', 'dispatch_attempt')) {
                    $table->unsignedTinyInteger('dispatch_attempt')->default(1);
                }
                if (!Schema::hasColumn('message_queue', 'transport_endpoint')) {
                    $table->string('transport_endpoint')->nullable();
                }
                if (!Schema::hasColumn('message_queue', 'transport_payload')) {
                    $table->json('transport_payload')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('outgoing_messages')) {
            Schema::table('outgoing_messages', function (Blueprint $table) {
                $columns = [
                    'selected_channel',
                    'channel_selection_reason',
                    'fallback_chain',
                    'channel_attempt',
                    'transport_endpoint',
                    'transport_payload',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('outgoing_messages', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('message_queue')) {
            Schema::table('message_queue', function (Blueprint $table) {
                $columns = [
                    'selected_channel',
                    'channel_selection_reason',
                    'fallback_chain',
                    'dispatch_attempt',
                    'transport_endpoint',
                    'transport_payload',
                ];

                foreach ($columns as $column) {
                    if (Schema::hasColumn('message_queue', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
