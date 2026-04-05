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
        Schema::table('outgoing_messages', function (Blueprint $table) {
            // Add message lifecycle timestamps
            if (!Schema::hasColumn('outgoing_messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('scheduled_at');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_at');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('delivered_at');
            }
            
            // Add indexes for performance
            if (!Schema::hasColumn('outgoing_messages', 'sent_at')) {
                $table->index('sent_at');
            }
            if (!Schema::hasColumn('outgoing_messages', 'delivered_at')) {
                $table->index('delivered_at');
            }
            if (!Schema::hasColumn('outgoing_messages', 'read_at')) {
                $table->index('read_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            if (Schema::hasColumn('outgoing_messages', 'sent_at')) {
                $table->dropIndex(['sent_at']);
                $table->dropColumn('sent_at');
            }
            
            if (Schema::hasColumn('outgoing_messages', 'delivered_at')) {
                $table->dropIndex(['delivered_at']);
                $table->dropColumn('delivered_at');
            }
            
            if (Schema::hasColumn('outgoing_messages', 'read_at')) {
                $table->dropIndex(['read_at']);
                $table->dropColumn('read_at');
            }
        });
    }
};
