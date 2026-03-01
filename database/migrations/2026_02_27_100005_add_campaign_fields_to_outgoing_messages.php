<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            // Campaign tracking
            $table->foreignId('campaign_id')->nullable()->after('id')->constrained()->onDelete('set null');
            $table->foreignId('message_queue_id')->nullable()->after('campaign_id')->constrained('message_queue')->onDelete('set null');
            
            // Personalization metadata
            $table->text('original_message')->nullable()->after('message');
            $table->boolean('is_personalized')->default(false)->after('original_message');
            $table->json('personalization_metadata')->nullable()->after('is_personalized');
            
            // Indexes
            $table->index('campaign_id');
            $table->index('message_queue_id');
            $table->index('is_personalized');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
            $table->dropForeign(['message_queue_id']);
            $table->dropIndex(['campaign_id']);
            $table->dropIndex(['message_queue_id']);
            $table->dropIndex(['is_personalized']);
            
            $table->dropColumn([
                'campaign_id',
                'message_queue_id',
                'original_message',
                'is_personalized',
                'personalization_metadata'
            ]);
        });
    }
};
