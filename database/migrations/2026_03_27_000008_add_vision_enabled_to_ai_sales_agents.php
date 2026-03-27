<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add vision_enabled flag to ai_sales_agents.
 * When true AND the business has an active premium plan, incoming image
 * messages will be processed by the GPT-4o vision API instead of the
 * standard text-only RAG path.
 * Defaults to false so ZERO existing behaviour is changed on deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_sales_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_sales_agents', 'vision_enabled')) {
                $table->boolean('vision_enabled')
                      ->default(false)
                      ->after('accepted_terms')
                      ->comment('Process incoming images via GPT-4o vision (premium plan only)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_sales_agents', function (Blueprint $table) {
            if (Schema::hasColumn('ai_sales_agents', 'vision_enabled')) {
                $table->dropColumn('vision_enabled');
            }
        });
    }
};
