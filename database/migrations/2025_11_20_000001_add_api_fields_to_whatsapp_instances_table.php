<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiFieldsToWhatsappInstancesTable extends Migration
{
    public function up()
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->string('api_key')->nullable()->after('instance_name');
            $table->string('webhook_url')->nullable()->after('api_key');
            $table->string('webhook_secret')->nullable()->after('webhook_url');
        });
    }

    public function down()
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropColumn(['api_key', 'webhook_url', 'webhook_secret']);
        });
    }
}
