<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('campaign_analytics', function (Blueprint $table) {
            $table->integer('total_failed')->default(0)->after('total_replied');
            $table->decimal('avg_confidence_score', 5, 2)->default(0)->after('credits_spent');
        });
    }

    public function down()
    {
        Schema::table('campaign_analytics', function (Blueprint $table) {
            $table->dropColumn(['total_failed', 'avg_confidence_score']);
        });
    }
};
