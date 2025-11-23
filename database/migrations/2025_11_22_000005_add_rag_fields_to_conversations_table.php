<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRagFieldsToConversationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('conversations', function (Blueprint $table) {
            // Add RAG-specific fields
            $table->json('rag_sources')->nullable()->after('ai_metadata'); // Store RAG source documents
            $table->boolean('rag_enhanced')->default(false)->after('rag_sources'); // Whether RAG was used
            $table->text('customer_message')->nullable()->after('message_content'); // Customer's original message
            $table->text('ai_response')->nullable()->after('customer_message'); // AI's response
            $table->string('sentiment')->nullable()->after('sentiment_score'); // Sentiment analysis result
            $table->decimal('confidence_score', 5, 4)->nullable()->after('sentiment'); // AI confidence score
            $table->integer('tokens_used')->default(0)->after('confidence_score'); // OpenAI tokens used
            $table->string('state')->nullable()->after('conversation_state'); // Conversation state
            $table->text('summary')->nullable()->after('state'); // Brief summary
            $table->json('ai_actions')->nullable()->after('summary'); // Actions taken by AI
            $table->json('conversation_context')->nullable()->after('ai_actions'); // Enhanced context
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn([
                'rag_sources',
                'rag_enhanced', 
                'customer_message',
                'ai_response',
                'sentiment',
                'confidence_score',
                'tokens_used',
                'state',
                'summary',
                'ai_actions',
                'conversation_context'
            ]);
        });
    }
}