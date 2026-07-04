<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('outgoing_messages', 'is_ai_generated')) {
                $table->boolean('is_ai_generated')->default(false)->after('is_personalized');
            }
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            if (Schema::hasColumn('outgoing_messages', 'is_ai_generated')) {
                $table->dropColumn('is_ai_generated');
            }
        });
    }
};