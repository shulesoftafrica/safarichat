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
            // Add missing columns that the SendWhatsAppMessage job expects
            
            if (!Schema::hasColumn('outgoing_messages', 'retry_count')) {
                $table->integer('retry_count')->default(0)->after('metadata');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'error_message')) {
                $table->text('error_message')->nullable()->after('retry_count');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'waapi_message_id')) {
                $table->string('waapi_message_id')->nullable()->after('error_message');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'waapi_response')) {
                $table->json('waapi_response')->nullable()->after('waapi_message_id');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('waapi_response');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'chat_id')) {
                $table->string('chat_id')->nullable()->after('phone_number');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'media_path')) {
                $table->string('media_path')->nullable()->after('message_body');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'media_url')) {
                $table->string('media_url')->nullable()->after('media_path');
            }
            
            if (!Schema::hasColumn('outgoing_messages', 'caption')) {
                $table->string('caption')->nullable()->after('media_url');
            }
            
            // Add indexes for performance
            $table->index(['status', 'queued_at']);
            $table->index(['phone_number', 'sent_at']);
            $table->index('waapi_message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            // Drop added columns
            $columnsToCheck = [
                'retry_count', 'error_message', 'waapi_message_id', 
                'waapi_response', 'sent_at', 'chat_id', 
                'media_path', 'media_url', 'caption'
            ];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('outgoing_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
            
            // Drop indexes
            $table->dropIndex(['status', 'queued_at']);
            $table->dropIndex(['phone_number', 'sent_at']);
            $table->dropIndex(['waapi_message_id']);
        });
    }
};
