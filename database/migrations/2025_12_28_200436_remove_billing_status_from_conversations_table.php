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
            $table->dropColumn(['billing_status', 'credits_deducted']);
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
