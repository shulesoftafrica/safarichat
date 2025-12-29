<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations - Consolidate Events functionality into Business table
     */
    public function up(): void
    {
        // Step 1: Add missing fields to businesses table from events
        Schema::table('businesses', function (Blueprint $table) {
            // Campaign/Event specific fields
            if (!Schema::hasColumn('businesses', 'campaign_name')) {
                $table->string('campaign_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('businesses', 'campaign_start_date')) {
                $table->date('campaign_start_date')->nullable()->after('campaign_name');
            }
            if (!Schema::hasColumn('businesses', 'campaign_end_date')) {
                $table->date('campaign_end_date')->nullable()->after('campaign_start_date');
            }
            
            // WhatsApp Integration fields (migrate from events)
            if (!Schema::hasColumn('businesses', 'whatsapp_api_url')) {
                $table->string('whatsapp_api_url')->nullable()->after('website');
            }
            if (!Schema::hasColumn('businesses', 'whatsapp_token')) {
                $table->text('whatsapp_token')->nullable()->after('whatsapp_api_url');
            }
            
            // Business type (replaces event_type_id)  
            if (!Schema::hasColumn('businesses', 'business_category')) {
                $table->string('business_category')->nullable()->after('business_type_id');
            }
            
            // Campaign/Business unique identifier
            if (!Schema::hasColumn('businesses', 'campaign_uid')) {
                $table->string('campaign_uid')->nullable()->unique()->after('campaign_end_date');
            }
            
            // Location enhancement (district support)
            if (!Schema::hasColumn('businesses', 'district_id')) {
                $table->unsignedBigInteger('district_id')->nullable()->after('ward_id');
                $table->index('district_id');
            }
        });

        // Step 2: Migrate data from events to businesses
        echo "Migrating events data to businesses table...\n";
        
        DB::statement("
            UPDATE businesses 
            SET 
                campaign_name = e.name,
                campaign_start_date = DATE(e.created_at),
                campaign_end_date = DATE(e.date),
                whatsapp_api_url = e.whatsapp_api_url,
                whatsapp_token = e.whatsapp_token,
                campaign_uid = e.uid,
                district_id = e.district_id,
                business_category = et.name
            FROM events e
            JOIN users_events ue ON ue.event_id = e.id
            LEFT JOIN events_types et ON et.id = e.event_type_id
            WHERE businesses.user_id = ue.user_id
            AND businesses.campaign_name IS NULL
        ");

        // Step 3: Create mapping table for legacy event references
        Schema::create('event_business_mapping', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('business_id');
            $table->timestamps();
            
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['event_id', 'business_id']);
        });

        // Step 4: Populate mapping table
        DB::statement("
            INSERT INTO event_business_mapping (event_id, business_id, created_at, updated_at)
            SELECT DISTINCT e.id, b.id, NOW(), NOW()
            FROM events e
            JOIN users_events ue ON ue.event_id = e.id
            JOIN businesses b ON b.user_id = ue.user_id
        ");

        echo "Migration completed. Events data consolidated into businesses table.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop mapping table
        Schema::dropIfExists('event_business_mapping');
        
        // Remove added columns from businesses
        Schema::table('businesses', function (Blueprint $table) {
            $columns = [
                'campaign_name', 'campaign_start_date', 'campaign_end_date',
                'whatsapp_api_url', 'whatsapp_token', 'business_category',
                'campaign_uid', 'district_id'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('businesses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};