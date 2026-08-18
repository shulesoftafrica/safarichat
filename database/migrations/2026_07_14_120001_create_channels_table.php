<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('channels')) {
            return;
        }

        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('channel_key', 30);
            $table->string('display_name', 100);
            $table->string('provider', 50)->default('unified_api');
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('priority_rank')->default(5);
            $table->json('capabilities')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'channel_key']);
            $table->index(['business_id', 'is_active']);

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('channels')) {
            return;
        }

        Schema::dropIfExists('channels');
    }
};
