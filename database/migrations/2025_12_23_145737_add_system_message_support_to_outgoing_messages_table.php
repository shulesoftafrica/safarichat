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
            // Only add is_system_message since message_type already exists
            if (!Schema::hasColumn('outgoing_messages', 'is_system_message')) {
                $table->boolean('is_system_message')->default(false)->after('message_type');
            }
            
            // Add indexes for system message queries
            $table->index(['is_system_message', 'message_type'], 'idx_outgoing_messages_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            $table->dropIndex('idx_outgoing_messages_system');
            if (Schema::hasColumn('outgoing_messages', 'is_system_message')) {
                $table->dropColumn('is_system_message');
            }
        });
    }
};
