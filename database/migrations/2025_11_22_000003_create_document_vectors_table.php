<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentVectorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('document_vectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_attachment_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // Denormalized for faster queries
            
            $table->integer('chunk_index'); // Sequential chunk number within document
            $table->text('content_text'); // Original text chunk
            $table->string('content_summary', 500)->nullable(); // AI-generated summary
            $table->integer('page_number')->nullable(); // PDF page number (if applicable)
            $table->string('section_title')->nullable(); // Extracted section heading
            
            $table->json('embedding_vector'); // OpenAI text-embedding-3-small vector
            $table->json('metadata')->nullable(); // Additional context (word_count, keywords, etc)
            
            $table->timestamps();
            
            // Indexes for fast retrieval
            $table->index(['product_id']);
            $table->index(['product_attachment_id']);
            $table->index(['product_id', 'chunk_index']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_vectors');
    }
}