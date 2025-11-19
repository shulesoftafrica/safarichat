<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConversationsTable extends Migration
{
    public function up()
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('product_id')->nullable(); // Product context for conversation
            $table->enum('message_type', ['CUSTOMER', 'AI_AGENT', 'HUMAN_AGENT'])->default('AI_AGENT');
            $table->text('message_content');
            $table->string('conversation_state', 50)->default('INTRO'); // INTRO, PITCH, DEMO, NEGOTIATION, CLOSING, etc.
            $table->json('ai_metadata')->nullable(); // OpenAI response metadata
            $table->timestamp('followup_attempt_at')->nullable(); // When to send follow-up
            $table->json('context_data')->nullable(); // Additional context for AI
            $table->boolean('is_active')->default(true);
            $table->decimal('sentiment_score', 3, 2)->nullable(); // -1.00 to 1.00
            $table->string('language_detected', 10)->nullable(); // Auto-detected language
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
            
            $table->index(['lead_id', 'created_at']);
            $table->index('followup_attempt_at');
            $table->index('conversation_state');
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversations');
    }
}