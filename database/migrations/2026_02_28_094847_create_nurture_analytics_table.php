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
        Schema::create('nurture_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('nurture_library_id');
            $table->unsignedBigInteger('campaign_id')->nullable();
            $table->unsignedBigInteger('message_queue_id');
            $table->unsignedBigInteger('contact_id');
            
            // Before nurture
            $table->integer('days_since_last_contact')->nullable()->comment('How long had they been ghosting?');
            $table->integer('unanswered_messages_count')->nullable()->comment('How many messages ignored?');
            
            // After nurture
            $table->boolean('did_reply')->default(false);
            $table->integer('reply_time_minutes')->nullable()->comment('How fast did they respond?');
            $table->string('reply_sentiment', 20)->nullable()->comment('positive, neutral, negative');
            $table->boolean('did_convert')->default(false)->comment('Did they eventually become customer?');
            $table->decimal('conversion_value', 10, 2)->nullable()->comment('Deal size if converted');
            
            // Timestamps
            $table->timestamp('sent_at');
            $table->timestamp('replied_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['nurture_library_id', 'did_reply'], 'idx_library');
            $table->index(['did_reply', 'did_convert'], 'idx_performance');
            
            // Foreign Keys
            $table->foreign('nurture_library_id')->references('id')->on('nurture_library')->onDelete('cascade');
            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('set null');
            $table->foreign('message_queue_id')->references('id')->on('message_queue')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('business_contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nurture_analytics');
    }
};
