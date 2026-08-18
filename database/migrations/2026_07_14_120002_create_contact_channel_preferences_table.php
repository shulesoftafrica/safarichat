<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contact_channel_preferences')) {
            return;
        }

        Schema::create('contact_channel_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_contact_id');
            $table->unsignedBigInteger('ai_sales_agent_id')->nullable();
            $table->unsignedBigInteger('channel_id')->nullable();
            $table->string('channel_key', 30);
            $table->boolean('is_preferred')->default(false);
            $table->boolean('is_allowed')->default(true);
            $table->unsignedTinyInteger('priority_rank')->default(5);
            $table->timestamp('opt_out_at')->nullable();
            $table->boolean('formal_only')->default(false);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['business_contact_id', 'channel_key'], 'contact_channel_pref_unique');
            $table->index(['business_contact_id', 'is_allowed', 'priority_rank'], 'contact_channel_pref_lookup_idx');

            $table->foreign('business_contact_id')
                ->references('id')
                ->on('business_contacts')
                ->onDelete('cascade');

            $table->foreign('ai_sales_agent_id')
                ->references('id')
                ->on('ai_sales_agents')
                ->onDelete('set null');

            $table->foreign('channel_id')
                ->references('id')
                ->on('channels')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('contact_channel_preferences')) {
            return;
        }

        Schema::dropIfExists('contact_channel_preferences');
    }
};
