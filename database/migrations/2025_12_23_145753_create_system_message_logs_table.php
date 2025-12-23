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
        Schema::create('system_message_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whatsapp_instance_id');
            $table->string('phone_number', 20);
            $table->string('message_type', 50);
            $table->text('message_content');
            $table->enum('status', ['queued', 'sent', 'failed', 'delivered', 'read'])->default('queued');
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Foreign keys and indexes
            $table->foreign('whatsapp_instance_id')->references('id')->on('whatsapp_instances')->onDelete('cascade');
            $table->index('phone_number', 'idx_system_message_logs_phone');
            $table->index('message_type', 'idx_system_message_logs_type');
            $table->index('status', 'idx_system_message_logs_status');
            $table->index('sent_at', 'idx_system_message_logs_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_message_logs');
    }
};
