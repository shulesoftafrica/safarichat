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
        Schema::create('campaign_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            
            // File information
            $table->string('file_name');
            $table->string('file_path', 500);
            $table->string('file_url', 500)->nullable();
            $table->string('file_type', 50);
            $table->bigInteger('file_size'); // Size in bytes
            
            $table->timestamps();
            
            // Index
            $table->index('campaign_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_attachments');
    }
};
