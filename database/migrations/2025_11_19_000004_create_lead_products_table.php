<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadProductsTable extends Migration
{
    public function up()
    {
        Schema::create('lead_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('product_id');
            $table->enum('status', [
                'INTERESTED', 'PITCHED', 'DEMO_REQUESTED', 'DEMO_COMPLETED', 
                'PROPOSAL_SENT', 'NEGOTIATING', 'CLOSED', 'LOST'
            ])->default('INTERESTED');
            $table->boolean('is_primary_product')->default(false);
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->decimal('discount_applied', 5, 2)->default(0); // Percentage discount
            $table->text('sales_notes')->nullable();
            $table->date('demo_scheduled_date')->nullable();
            $table->date('proposal_sent_date')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->json('negotiation_history')->nullable(); // Track price negotiations
            $table->integer('follow_up_count')->default(0);
            $table->timestamp('next_followup_at')->nullable();
            $table->timestamp('followup_scheduled_by_customer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            
            $table->unique(['lead_id', 'product_id']);
            $table->index(['lead_id', 'status']);
            $table->index('is_primary_product');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lead_products');
    }
}