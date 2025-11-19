<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHandoffsTable extends Migration
{
    public function up()
    {
        Schema::create('handoffs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->string('reason_code', 100); // COMPLEX_QUESTION, COMPLAINT, LARGE_ORDER, etc.
            $table->text('ai_summary');
            $table->unsignedBigInteger('human_agent_id')->nullable();
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'resolved', 'escalated'])->default('pending');
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->integer('customer_satisfaction')->nullable(); // 1-5 rating
            $table->json('context_data')->nullable(); // Additional context for human agent
            $table->enum('priority_level', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->integer('estimated_resolution_time')->nullable(); // minutes
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('human_agent_id')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['status', 'created_at']);
            $table->index('priority_level');
            $table->index('lead_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('handoffs');
    }
}