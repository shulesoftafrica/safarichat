<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('contact_id')->nullable();
            
            // Contact info
            $table->string('phone_number', 20);
            $table->string('contact_name')->nullable();
            
            // Messages
            $table->text('original_message');
            $table->text('refined_message')->nullable();
            $table->text('attachment_context')->nullable();
            
            // Status
            $table->enum('status', [
                'staged',
                'analyzing',
                'refined',
                'scheduled',
                'sent',
                'failed',
                'human_review',
                'opted_out',
                'cancelled'
            ])->default('staged');
            
            $table->integer('priority')->default(5);
            
            // AI Analysis Results
            $table->string('detected_language', 10)->nullable(); // en, sw, mixed
            $table->string('detected_tone', 20)->nullable(); // formal, casual, urgent, friendly
            $table->string('relationship_stage', 20)->nullable(); // new, engaged, converting, customer, inactive
            $table->timestamp('last_interaction_at')->nullable();
            
            // Scheduling
            $table->timestamp('optimal_send_time')->nullable();
            $table->timestamp('scheduled_send_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            
            // AI Metadata
            $table->decimal('ai_confidence_score', 5, 2)->nullable();
            $table->string('sentiment_filter_result', 20)->nullable(); // positive, neutral, negative, opt_out_detected
            $table->text('human_review_reason')->nullable();
            $table->json('context_summary')->nullable();
            $table->json('ai_metadata')->nullable();
            
            // Delivery Tracking
            $table->integer('retry_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('external_message_id')->nullable();
            $table->string('provider', 20)->default('wasender'); // wasender, meta
            $table->integer('credits_used')->default(5); // 2 AI + 3 WaSender
            
            $table->timestamps();
            
            // Indexes
            $table->index(['campaign_id', 'status']);
            $table->index(['scheduled_send_at', 'status']);
            $table->index('contact_id');
            $table->index('optimal_send_time');
            
            // Foreign key for contact
            $table->foreign('contact_id')
                  ->references('id')
                  ->on('business_contacts')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_queue');
    }
};
