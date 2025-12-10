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
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'tokens_used')) {
                $table->integer('tokens_used')->default(0)->after('ai_metadata');
                $table->index('tokens_used', 'idx_conversations_tokens');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            if (Schema::hasColumn('conversations', 'tokens_used')) {
                $table->dropIndex('idx_conversations_tokens');
                $table->dropColumn('tokens_used');
            }
        });
    }
};
