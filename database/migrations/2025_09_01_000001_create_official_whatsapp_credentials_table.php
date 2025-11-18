<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfficialWhatsappCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('official_whatsapp_credentials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('waba_id')->nullable(); // WhatsApp Business Account ID
            $table->string('phone_number_id')->nullable(); // Phone Number ID
            $table->text('access_token')->nullable(); // Long-lived token (encrypted)
            $table->timestamp('token_expiration')->nullable();
            $table->string('api_provider')->default('360dialog'); // BSP provider
            $table->enum('status', ['pending', 'connected', 'disconnected', 'suspended', 'verification_pending'])->default('pending');
            $table->string('phone_number')->nullable(); // The actual phone number
            $table->string('display_phone_number')->nullable(); // Formatted display number
            $table->string('verified_name')->nullable(); // Business verified name
            $table->string('quality_rating')->nullable(); // GREEN, YELLOW, RED
            $table->json('webhook_verification_token')->nullable(); // For webhook verification
            $table->json('meta_app_config')->nullable(); // Store app_id, config_id, etc.
            $table->text('temporary_code')->nullable(); // Store temporary code during onboarding
            $table->timestamp('onboarding_started_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->json('error_logs')->nullable(); // Store any error information
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index('waba_id');
            $table->index('phone_number_id');
            $table->unique(['user_id', 'phone_number']); // One official WhatsApp per user per number
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('official_whatsapp_credentials');
    }
}
