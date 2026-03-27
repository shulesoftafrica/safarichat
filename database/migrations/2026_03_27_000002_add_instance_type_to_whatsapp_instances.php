<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->string('instance_type', 30)
                  ->default('sales')
                  ->after('status')
                  ->comment('sales | customer_success | both');
        });

        // Mark the system default instance as 'both' (handles CS push + inbound sales leads)
        // Change to 'customer_success' if this number never receives public leads
        DB::table('whatsapp_instances')
          ->where('is_system_default', true)
          ->update(['instance_type' => 'both']);
    }

    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropColumn('instance_type');
        });
    }
};
