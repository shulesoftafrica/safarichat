<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateWhatsappInstancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('whatsapp_instances', 'access_token')) {
                $table->string('access_token')->nullable()->after('instance_id');
            }
            if (!Schema::hasColumn('whatsapp_instances', 'connect_status')) {
                $table->enum('connect_status', ['disconnected', 'connecting', 'ready', 'error'])->default('disconnected')->after('status');
            }
            if (!Schema::hasColumn('whatsapp_instances', 'webhook_verified')) {
                $table->boolean('webhook_verified')->default(false)->after('webhook_url');
            }
            if (!Schema::hasColumn('whatsapp_instances', 'last_message_received')) {
                $table->timestamp('last_message_received')->nullable()->after('last_seen');
            }
            if (!Schema::hasColumn('whatsapp_instances', 'total_messages_received')) {
                $table->integer('total_messages_received')->default(0)->after('last_message_received');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropColumn([
                'access_token',
                'connect_status', 
                'webhook_verified',
                'last_message_received',
                'total_messages_received'
            ]);
        });
    }
}
