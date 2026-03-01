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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->nullable()->constrained()->onDelete('cascade');
            
            // Campaign details
            $table->string('campaign_name');
            $table->enum('campaign_type', ['broadcast', 'targeted', 'drip'])->default('targeted');
            $table->text('original_message');
            $table->json('recipient_criteria')->nullable();
            
            // Counters
            $table->integer('total_recipients')->default(0);
            $table->integer('queued_count')->default(0);
            $table->integer('analyzing_count')->default(0);
            $table->integer('refined_count')->default(0);
            $table->integer('scheduled_count')->default(0);
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->integer('human_review_count')->default(0);
            
            // Status
            $table->enum('status', [
                'staging',
                'processing',
                'scheduled',
                'sending',
                'completed',
                'paused',
                'cancelled'
            ])->default('staging');
            
            // Metadata
            $table->boolean('has_attachments')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaigns');
    }
};
