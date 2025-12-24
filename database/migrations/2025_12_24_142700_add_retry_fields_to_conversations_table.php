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
            // Add retry_count for failed conversation processing
            if (!Schema::hasColumn('conversations', 'retry_count')) {
                $table->integer('retry_count')->default(0)->after('status');
                $table->index('retry_count');
            }
            
            // Add completed_at timestamp
            if (!Schema::hasColumn('conversations', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('processing_timeout_at');
                $table->index('completed_at');
            }
            
            // Add last_ai_response for tracking latest response
            if (!Schema::hasColumn('conversations', 'last_ai_response')) {
                $table->text('last_ai_response')->nullable()->after('completed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $columnsToCheck = ['retry_count', 'completed_at', 'last_ai_response'];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('conversations', $column)) {
                    if (in_array($column, ['retry_count', 'completed_at'])) {
                        $table->dropIndex(['conversations_' . $column . '_index']);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};