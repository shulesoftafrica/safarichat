<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->timestamp('unified_api_registered_at')
                  ->nullable()
                  ->after('last_active_at')
                  ->comment('Set when instance is successfully registered with Unified Notification API after connection');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropColumn('unified_api_registered_at');
        });
    }
};
