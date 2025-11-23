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
            $table->boolean('contacted_for_sales')->default(false)->after('guest_pledge');
            $table->timestamp('contacted_at')->nullable()->after('contacted_for_sales');
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
            $table->dropColumn(['contacted_for_sales', 'contacted_at']);
        });
    }
};