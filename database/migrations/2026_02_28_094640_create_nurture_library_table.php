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
        Schema::create('nurture_library', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('business_id');
            
            // Content
            $table->string('title')->comment('E.g., "75% Faster Registration with SMS Auto-Confirm"');
            $table->enum('content_type', ['case_study', 'tip', 'insight', 'video', 'article', 'testimonial']);
            $table->text('content_body')->comment('The actual value message (2-4 sentences)');
            $table->string('content_url', 500)->nullable()->comment('Optional link to video/article/demo');
            
            // Targeting Rules
            $table->string('target_industry', 100)->nullable()->comment('E.g., "Education", "Retail", "Healthcare"');
            $table->string('target_job_title', 100)->nullable()->comment('E.g., "School Director", "Principal", "Administrator"');
            $table->string('target_pain_point', 255)->nullable()->comment('E.g., "Student registration", "Fee collection", "Parent communication"');
            $table->string('target_lead_status', 50)->nullable()->comment('E.g., "cold", "warm", "hot", "customer"');
            $table->string('seasonal_relevance', 100)->nullable()->comment('E.g., "January-February" (school intake season)');
            
            // Metadata
            $table->string('language', 10)->default('en')->comment('en, sw, mixed');
            $table->string('tone', 20)->default('friendly')->comment('formal, casual, friendly, urgent');
            $table->integer('usage_count')->default(0)->comment('Track how many times used');
            $table->decimal('success_rate', 5, 2)->default(0.00)->comment('Reply rate after sending this nugget');
            
            $table->timestamps();
            
            // Indexes
            $table->index(['target_industry', 'target_job_title', 'language'], 'idx_targeting');
            $table->index(['user_id', 'business_id'], 'idx_user');
            $table->index('content_type', 'idx_content_type');
            
            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nurture_library');
    }
};
