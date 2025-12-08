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
        // Drop budget_payments table first (has foreign key to budgets)
        Schema::dropIfExists('budget_payments');
        
        // Drop budgets table
        Schema::dropIfExists('budgets');
        
        echo "Budget tables dropped successfully.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate budgets table
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('business_service_id')->nullable();
            $table->decimal('initial_price', 10, 2)->nullable();
            $table->decimal('actual_price', 10, 2)->nullable();
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->boolean('approved')->default(false);
            $table->integer('quantity')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->foreign('business_service_id')->references('id')->on('business_services')->onDelete('cascade');
        });
        
        // Recreate budget_payments table
        Schema::create('budget_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_id');
            $table->unsignedBigInteger('created_by');
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('method')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            
            $table->foreign('budget_id')->references('id')->on('budgets')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
        
        echo "Budget tables recreated.\n";
    }
};
