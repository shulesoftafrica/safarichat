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
        Schema::create('corporate_strategy_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('country', 100);
            $table->integer('meeting_length'); // 30, 60, 90 minutes
            $table->datetime('proposed_date_time');
            $table->datetime('confirmed_date_time')->nullable();
            $table->text('meeting_agendas');
            $table->enum('payment_method', ['flutterwave', 'stripe', 'ucn']);
            $table->decimal('price_usd', 10, 2);
            $table->enum('payment_status', ['pending', 'processing', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_transaction_id')->nullable();
            $table->enum('session_status', ['pending', 'confirmed', 'completed', 'cancelled', 'rescheduled'])->default('pending');
            $table->text('session_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('meeting_link')->nullable(); // Zoom/Teams link
            $table->timestamps();
            
            $table->index(['payment_status', 'created_at']);
            $table->index(['session_status', 'confirmed_date_time']);
            $table->index('proposed_date_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_strategy_sessions');
    }
};
