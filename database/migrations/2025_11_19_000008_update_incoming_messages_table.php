<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIncomingMessagesTable extends Migration
{
    public function up()
    {
        Schema::table('incoming_messages', function (Blueprint $table) {
            // Add fields for webhook processing tracking
            $table->string('processing_method')->default('webhook')->after('status'); // 'webhook', 'cron_fallback'
            $table->timestamp('failed_instant_at')->nullable()->after('processing_method');
            $table->integer('processing_attempts')->default(0)->after('failed_instant_at');
            $table->string('failure_reason')->nullable()->after('processing_attempts');
            $table->json('webhook_response')->nullable()->after('failure_reason');
            
            $table->index(['status', 'processing_method']);
            $table->index('failed_instant_at');
        });
    }

    public function down()
    {
        Schema::table('incoming_messages', function (Blueprint $table) {
            $table->dropIndex(['status', 'processing_method']);
            $table->dropIndex(['failed_instant_at']);
            
            $table->dropColumn([
                'processing_method', 'failed_instant_at', 'processing_attempts', 
                'failure_reason', 'webhook_response'
            ]);
        });
    }
}