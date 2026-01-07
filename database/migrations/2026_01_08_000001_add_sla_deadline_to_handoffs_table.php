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
        Schema::table('handoffs', function (Blueprint $table) {
            // Add sla_deadline column if it doesn't exist
            if (!Schema::hasColumn('handoffs', 'sla_deadline')) {
                $table->timestamp('sla_deadline')->nullable()->after('estimated_resolution_time');
                $table->index('sla_deadline');
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
        Schema::table('handoffs', function (Blueprint $table) {
            if (Schema::hasColumn('handoffs', 'sla_deadline')) {
                $table->dropIndex(['sla_deadline']);
                $table->dropColumn('sla_deadline');
            }
        });
    }
};