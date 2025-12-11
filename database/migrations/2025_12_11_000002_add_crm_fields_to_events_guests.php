<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events_guests', function (Blueprint $table) {
            // Add CRM integration fields
            $table->string('crm_id')->nullable()->after('user_id')->index();
            $table->json('crm_data')->nullable()->after('crm_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events_guests', function (Blueprint $table) {
            $table->dropIndex(['crm_id']);
            $table->dropColumn(['crm_id', 'crm_data']);
        });
    }
};
