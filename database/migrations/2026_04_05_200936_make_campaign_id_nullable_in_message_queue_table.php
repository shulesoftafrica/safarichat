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
        Schema::table('message_queue', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['campaign_id']);
            
            // Make campaign_id nullable
            $table->foreignId('campaign_id')->nullable()->change();
            
            // Re-add the foreign key constraint with nullable support
            $table->foreign('campaign_id')
                  ->references('id')
                  ->on('campaigns')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_queue', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['campaign_id']);
            
            // Make campaign_id NOT NULL again
            $table->foreignId('campaign_id')->nullable(false)->change();
            
            // Re-add the foreign key constraint
            $table->foreign('campaign_id')
                  ->references('id')
                  ->on('campaigns')
                  ->onDelete('cascade');
        });
    }
};
