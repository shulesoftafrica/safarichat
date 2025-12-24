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
            // Add status field for conversation processing
            if (!Schema::hasColumn('conversations', 'status')) {
                $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('is_active');
                $table->index('status');
            }
            
            // Add priority field for queue processing
            if (!Schema::hasColumn('conversations', 'priority')) {
                $table->integer('priority')->default(1)->after('status');
                $table->index('priority');
            }
            
            // Add processing fields for queue management
            if (!Schema::hasColumn('conversations', 'processing_started_at')) {
                $table->timestamp('processing_started_at')->nullable()->after('priority');
                $table->index('processing_started_at');
            }
            
            if (!Schema::hasColumn('conversations', 'processing_timeout_at')) {
                $table->timestamp('processing_timeout_at')->nullable()->after('processing_started_at');
                $table->index('processing_timeout_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $columnsToCheck = ['status', 'priority', 'processing_started_at', 'processing_timeout_at'];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('conversations', $column)) {
                    $table->dropIndex(['conversations_' . $column . '_index']);
                    $table->dropColumn($column);
                }
            }
        });
    }
};