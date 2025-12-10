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
        Schema::create('missed_automations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->enum('automation_type', ['followup', 'qualification', 'reminder', 'cart_recovery', 'welcome_sequence']);
            $table->timestamp('scheduled_at');
            $table->string('missed_reason', 255)->default('subscription_inactive');
            $table->json('target_data')->nullable(); // Customer info, product info, message content
            $table->decimal('potential_value', 10, 2)->nullable(); // Estimated lost revenue
            $table->timestamp('recovered_at')->nullable(); // If automation was later executed
            $table->timestamp('created_at')->useCurrent();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('set null');
            
            $table->index(['user_id', 'created_at'], 'idx_user_missed');
            $table->index('automation_type', 'idx_automation_type');
            $table->index(['scheduled_at', 'missed_reason'], 'idx_scheduled_missed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('missed_automations');
    }
};
