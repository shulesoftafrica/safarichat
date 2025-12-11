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
        Schema::table('businesses', function (Blueprint $table) {
            // Add company credibility kit fields
            $table->text('mission')->nullable()->after('name');
            $table->string('credibility_statistics', 500)->nullable()->after('mission');
            
            // Check if website column doesn't exist before adding
            if (!Schema::hasColumn('businesses', 'website')) {
                $table->string('website', 255)->nullable()->after('credibility_statistics');
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
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'mission',
                'credibility_statistics',
                'website'
            ]);
        });
    }
};
