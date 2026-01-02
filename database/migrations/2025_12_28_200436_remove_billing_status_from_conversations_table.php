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
            // Check if columns exist before dropping them
            if (Schema::hasColumn('conversations', 'billing_status')) {
                $table->dropColumn('billing_status');
            }
            if (Schema::hasColumn('conversations', 'credits_deducted')) {
                $table->dropColumn('credits_deducted');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->integer('credits_deducted')->nullable()->after('output_tokens');
            $table->enum('billing_status', ['pending', 'processed', 'failed'])->default('pending')->after('credits_deducted');
            $table->index(['billing_status', 'created_at']);
        });
    }
};
