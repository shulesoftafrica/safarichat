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
        // First, add new columns
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            // QR Code fields
            $table->boolean('qr_code_generated')->default(false)->after('status');
            $table->timestamp('qr_code_generated_at')->nullable()->after('qr_code_generated');
            $table->text('qr_code')->nullable()->after('qr_code_generated_at');
            
            // Connection tracking
            $table->timestamp('connected_at')->nullable()->after('qr_code');
            $table->timestamp('disconnected_at')->nullable()->after('connected_at');
            $table->timestamp('last_active_at')->nullable()->after('disconnected_at');
            
            // Platform and device info
            $table->string('platform', 50)->default('wasender')->after('last_active_at');
            $table->json('device_info')->nullable()->after('platform');
            
            // API integration
            $table->string('api_key')->nullable()->after('device_info');
        });
        
        // For PostgreSQL, we need to alter the enum type differently
        // First check if we're using PostgreSQL
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL: Drop and recreate the status column with new enum values
            DB::statement("ALTER TABLE whatsapp_instances ALTER COLUMN status DROP DEFAULT");
            DB::statement("ALTER TABLE whatsapp_instances ALTER COLUMN status TYPE VARCHAR(20)");
            DB::statement("ALTER TABLE whatsapp_instances ALTER COLUMN status SET DEFAULT 'pending'");
        } else {
            // MySQL: Use MODIFY syntax
            DB::statement("ALTER TABLE whatsapp_instances MODIFY COLUMN status ENUM('pending', 'connecting', 'connected', 'active', 'disconnected', 'error', 'suspended') DEFAULT 'pending'");
        }
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
                'qr_code_generated',
                'qr_code_generated_at',
                'qr_code',
                'connected_at',
                'disconnected_at',
                'last_active_at',
                'platform',
                'device_info',
                'api_key'
            ]);
        });
        
        // Revert status column changes
        $driver = DB::connection()->getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL: Keep as VARCHAR since we can't easily revert to enum
            DB::statement("ALTER TABLE whatsapp_instances ALTER COLUMN status SET DEFAULT 'connecting'");
        } else {
            // MySQL: Revert to original enum
            DB::statement("ALTER TABLE whatsapp_instances MODIFY COLUMN status ENUM('connecting', 'connected', 'disconnected', 'error') DEFAULT 'connecting'");
        }
    }
};
