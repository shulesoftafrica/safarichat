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
        Schema::table('leads', function (Blueprint $table) {
            // Add sentiment tracking columns
            if (!Schema::hasColumn('leads', 'negative_sentiment_count')) {
                $table->integer('negative_sentiment_count')->default(0)->after('interaction_count');
                $table->index('negative_sentiment_count');
            }
            
            if (!Schema::hasColumn('leads', 'positive_sentiment_count')) {
                $table->integer('positive_sentiment_count')->default(0)->after('negative_sentiment_count');
                $table->index('positive_sentiment_count');
            }
            
            // Add last activity tracking
            if (!Schema::hasColumn('leads', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('last_interaction_at');
                $table->index('last_activity_at');
            }
            
            // Add sentiment score for overall tracking
            if (!Schema::hasColumn('leads', 'overall_sentiment_score')) {
                $table->decimal('overall_sentiment_score', 3, 2)->default(0)->after('positive_sentiment_count');
                $table->index('overall_sentiment_score');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $columnsToCheck = [
                'negative_sentiment_count', 
                'positive_sentiment_count', 
                'last_activity_at', 
                'overall_sentiment_score'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('leads', $column)) {
                    $table->dropIndex(['leads_' . $column . '_index']);
                    $table->dropColumn($column);
                }
            }
        });
    }
};
