<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_attempts', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 30)->index();              // exactly as submitted
            $table->string('type', 30)->default('registration'); // registration | login | password_reset
            $table->string('delivery_channel', 20)->nullable(); // whatsapp | sms | failed
            $table->string('delivery_status', 20)->default('pending'); // pending | sent | failed | undeliverable
            $table->string('failure_reason')->nullable();       // human-readable error, if any
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->boolean('verified')->default(false);        // did the user eventually verify?
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['phone', 'type', 'created_at']);
            $table->index(['delivery_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_attempts');
    }
};
