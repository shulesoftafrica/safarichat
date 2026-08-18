<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contact_channel_metrics')) {
            return;
        }

        Schema::create('contact_channel_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_contact_id');
            $table->string('channel_key', 30);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('delivered_count')->default(0);
            $table->unsignedInteger('replied_count')->default(0);
            $table->unsignedInteger('converted_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->decimal('response_rate', 5, 2)->nullable();
            $table->decimal('conversion_rate', 5, 2)->nullable();
            $table->unsignedInteger('avg_response_minutes')->nullable();
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('last_success_at')->nullable();
            $table->timestamp('last_failure_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['business_contact_id', 'channel_key'], 'contact_channel_metrics_unique');
            $table->index(['channel_key', 'response_rate'], 'contact_channel_metrics_perf_idx');

            $table->foreign('business_contact_id')
                ->references('id')
                ->on('business_contacts')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('contact_channel_metrics')) {
            return;
        }

        Schema::dropIfExists('contact_channel_metrics');
    }
};
