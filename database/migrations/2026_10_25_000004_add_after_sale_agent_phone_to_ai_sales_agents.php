<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The human (or number) that WON contacts are handed to for after-sale support.
 * Once a contact is CONVERTED/CLOSED, the AI stops selling and hands off here.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('ai_sales_agents', 'after_sale_agent_phone')) {
            return;
        }

        Schema::table('ai_sales_agents', function (Blueprint $table) {
            $table->string('after_sale_agent_phone', 30)->nullable()->after('personality_description');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('ai_sales_agents', 'after_sale_agent_phone')) {
            return;
        }

        Schema::table('ai_sales_agents', function (Blueprint $table) {
            $table->dropColumn('after_sale_agent_phone');
        });
    }
};
