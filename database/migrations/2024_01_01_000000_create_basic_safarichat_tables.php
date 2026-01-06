<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('business_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('guest_name');
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->unsignedBigInteger('business_id');
            $table->text('message_content');
            $table->enum('sender_type', ['staff', 'client', 'system'])->default('staff');
            $table->unsignedBigInteger('staff_user_id')->nullable();
            $table->timestamp('timestamp');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('business_contacts');
    }
};