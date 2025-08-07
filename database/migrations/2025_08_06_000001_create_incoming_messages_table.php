<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIncomingMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('incoming_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Owner of the WhatsApp instance
            $table->string('instance_id'); // WhatsApp instance ID
            $table->string('message_id')->unique(); // WAAPI message ID
            $table->unsignedBigInteger('events_guest_id')->nullable(); // Associated guest
            $table->string('chat_id'); // WhatsApp chat ID (phone@c.us)
            $table->string('phone_number'); // Clean phone number
            $table->string('sender_name')->nullable(); // Contact name from WhatsApp
            $table->text('message_body'); // Message content
            $table->enum('message_type', ['text', 'image', 'video', 'audio', 'document', 'location', 'contact', 'sticker', 'other'])->default('text');
            $table->json('media_data')->nullable(); // Media information (URL, caption, etc.)
            $table->boolean('from_me')->default(false); // Is message from bot/instance owner
            $table->boolean('is_group')->default(false); // Is from group chat
            $table->timestamp('message_timestamp'); // When message was sent
            $table->enum('status', ['received', 'processed', 'replied', 'ignored'])->default('received');
            $table->text('auto_reply')->nullable(); // Auto reply sent (if any)
            $table->json('metadata')->nullable(); // Additional WAAPI data
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'instance_id']);
            $table->index(['chat_id', 'message_timestamp']);
            $table->index(['phone_number', 'created_at']);
            $table->index('status');
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('events_guest_id')->references('id')->on('events_guests')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('incoming_messages');
    }
}
