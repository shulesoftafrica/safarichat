<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            // Add notification API specific fields (batch_id already exists)
            $table->json('metadata')->nullable()->after('waapi_response');
            $table->string('priority', 20)->default('normal')->after('metadata');
            $table->string('provider', 50)->default('unified_api')->after('priority');
            $table->string('external_id')->nullable()->after('provider'); // For API response tracking
            
            // Add indexes for performance
            $table->index('priority');
            $table->index('provider');
            $table->index('external_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropIndex(['provider']);
            $table->dropIndex(['external_id']);
            
            $table->dropColumn(['metadata', 'priority', 'provider', 'external_id']);
        });
    }
};
