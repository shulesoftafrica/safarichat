<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Add campaign-related fields
            $table->boolean('is_active_campaign')->default(false)->after('status');
            $table->string('campaign_hook_text', 255)->nullable()->after('is_active_campaign');
            $table->string('campaign_pain_point', 255)->nullable()->after('campaign_hook_text');
            $table->string('campaign_attachment_path', 512)->nullable()->after('campaign_pain_point');
        });

        // Create a unique partial index to enforce only one active campaign at a time
        // This ensures data integrity at the database level
        DB::statement('
            CREATE UNIQUE INDEX one_active_campaign_check 
            ON products (is_active_campaign) 
            WHERE is_active_campaign = TRUE
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop the unique index first
        DB::statement('DROP INDEX IF EXISTS one_active_campaign_check');
        
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_active_campaign',
                'campaign_hook_text',
                'campaign_pain_point',
                'campaign_attachment_path'
            ]);
        });
    }
};
