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
        Schema::create('notification_queue', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('notification_type', ['whatsapp', 'email', 'sms', 'dashboard']);
            $table->enum('category', ['expiry_warning', 'payment_success', 'missed_opportunity', 'daily_summary', 'final_warning']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->string('recipient', 255); // Phone/email depending on type
            $table->string('subject', 255)->nullable();
            $table->text('message');
            $table->json('template_data')->nullable();
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->text('failure_reason')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->index(['status', 'scheduled_for'], 'idx_notification_queue');
            $table->index(['user_id', 'category'], 'idx_user_notifications');
            $table->index(['status', 'scheduled_for', 'retry_count'], 'idx_pending_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_queue');
    }
};
