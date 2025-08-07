<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutgoingMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outgoing_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('instance_id');
            $table->unsignedBigInteger('events_guest_id')->nullable();
            $table->string('chat_id'); // WhatsApp chat ID (phone@c.us)
            $table->string('phone_number'); // Clean phone number
            $table->text('message_body')->nullable();
            $table->enum('message_type', ['text', 'image', 'video', 'audio', 'document', 'location', 'contact'])->default('text');
            $table->string('media_path')->nullable(); // Local file path for media
            $table->string('media_url')->nullable(); // URL if media hosted online
            $table->string('caption')->nullable(); // Media caption
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->string('waapi_message_id')->nullable(); // ID returned by WAAPI
            $table->json('waapi_response')->nullable(); // Full WAAPI response
            $table->timestamp('scheduled_at')->nullable(); // For scheduled messages
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'instance_id']);
            $table->index(['status', 'scheduled_at']);
            $table->index(['phone_number', 'sent_at']);
            
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
        Schema::dropIfExists('outgoing_messages');
    }
}
