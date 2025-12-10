<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\AiSalesAgent;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First add the uuid column
        Schema::table('ai_sales_agents', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->index('uuid');
        });

        // Generate UUIDs for existing records
        AiSalesAgent::whereNull('uuid')->chunk(100, function ($agents) {
            foreach ($agents as $agent) {
                $agent->uuid = (string) Str::uuid();
                $agent->save();
            }
        });

        // Make uuid column required
        Schema::table('ai_sales_agents', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_sales_agents', function (Blueprint $table) {
            $table->dropIndex(['uuid']);
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
