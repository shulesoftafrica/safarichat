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
        // Add instance tracking to outgoing_messages
        Schema::table('outgoing_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('whatsapp_instance_id')->nullable()->after('user_id');
            $table->foreign('whatsapp_instance_id')->references('id')->on('whatsapp_instances')->onDelete('set null');
            $table->index('whatsapp_instance_id', 'idx_outgoing_messages_instance');
        });

        // Add instance tracking to incoming_messages
        Schema::table('incoming_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('whatsapp_instance_id')->nullable()->after('user_id');
            $table->foreign('whatsapp_instance_id')->references('id')->on('whatsapp_instances')->onDelete('set null');
            $table->index('whatsapp_instance_id', 'idx_incoming_messages_instance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_instance_id']);
            $table->dropIndex('idx_outgoing_messages_instance');
            $table->dropColumn('whatsapp_instance_id');
        });

        Schema::table('incoming_messages', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_instance_id']);
            $table->dropIndex('idx_incoming_messages_instance');
            $table->dropColumn('whatsapp_instance_id');
        });
    }
};
