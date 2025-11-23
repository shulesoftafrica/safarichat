<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            $table->enum('attachment_type', [
                'brochure',
                'manual',
                'profile',
                'case_study',
                'certificate',
                'contract_template',
                'technical_spec',
                'other'
            ]);
            
            $table->string('file_path'); // storage path
            $table->string('original_filename');
            $table->string('mime_type');
            $table->integer('file_size'); // bytes
            
            $table->string('title')->nullable(); // User-friendly name
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true); // Shareable with leads?
            $table->integer('display_order')->default(0);
            
            // RAG Processing Status
            $table->boolean('is_processed')->default(false);
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])
                  ->default('pending');
            $table->integer('vector_count')->default(0); // Number of vectors generated
            $table->text('processing_error')->nullable(); // Error details if failed
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id', 'attachment_type']);
            $table->index('processing_status');
            $table->index('is_processed');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_attachments');
    }
}