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
        Schema::create('campaign_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->unique()->constrained()->onDelete('cascade');
            
            // Delivery metrics
            $table->integer('total_sent')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('total_read')->default(0);
            $table->integer('total_replied')->default(0);
            
            // Response metrics
            $table->integer('avg_response_time')->default(0); // In minutes
            $table->integer('positive_sentiment_count')->default(0);
            $table->integer('neutral_sentiment_count')->default(0);
            $table->integer('negative_sentiment_count')->default(0);
            $table->integer('opt_out_count')->default(0);
            
            // Business metrics
            $table->integer('conversion_count')->default(0);
            $table->decimal('revenue_generated', 10, 2)->default(0);
            $table->integer('credits_spent')->default(0);
            $table->decimal('roi', 10, 2)->default(0); // Return on investment percentage
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_analytics');
    }
};
