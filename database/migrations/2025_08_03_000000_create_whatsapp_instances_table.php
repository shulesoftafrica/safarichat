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
        Schema::create('whatsapp_instances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('instance_id')->unique();
            $table->string('instance_name');
            $table->string('phone_number');
            $table->string('webhook_url')->nullable();
            $table->enum('status', ['connecting', 'connected', 'disconnected', 'error'])->default('connecting');
            $table->json('metadata')->nullable(); // For storing additional WAAPI response data
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
            $table->index(['instance_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('whatsapp_instances');
    }
};
