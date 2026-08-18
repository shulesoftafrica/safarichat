<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('channel_product_policies')) {
            return;
        }

        Schema::create('channel_product_policies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->string('channel_key', 30);
            $table->boolean('is_allowed')->default(true);
            $table->unsignedTinyInteger('priority_rank')->nullable();
            $table->unsignedSmallInteger('cooldown_minutes')->nullable();
            $table->json('rules')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'product_id', 'channel_key'], 'channel_product_policy_unique');
            $table->index(['business_id', 'product_id', 'is_allowed'], 'channel_product_policy_lookup_idx');

            $table->foreign('business_id')
                ->references('id')
                ->on('businesses')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');

            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('channel_product_policies')) {
            return;
        }

        Schema::dropIfExists('channel_product_policies');
    }
};
