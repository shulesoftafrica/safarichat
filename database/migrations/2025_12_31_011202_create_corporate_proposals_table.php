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
        Schema::create('corporate_proposals', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('country', 100);
            $table->string('official_email');
            $table->enum('adoption_timeline', ['very_soon', 'within_month', 'within_3months', 'within_6months']);
            $table->text('custom_message')->nullable();
            $table->enum('status', ['pending', 'contacted', 'in_progress', 'completed', 'declined'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index('official_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_proposals');
    }
};
