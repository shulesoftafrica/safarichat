<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cs_message_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type', 100)->comment(
                'welcome | first_product | daily_summary | trial_reminder | ' .
                'trial_warning | trial_expired | subscription_success | ' .
                'upgrade_nudge | credit_low | whatsapp_disconnected_alert | ' .
                'inactivity_day3 | inactivity_day10 | reengagement | unknown_inbound'
            );
            $table->timestamp('sent_at')->useCurrent();
            $table->boolean('delivered')->default(false);
            $table->jsonb('metadata')->default('{}');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Composite index for deduplication queries
            $table->index(['user_id', 'type', 'sent_at']);
            $table->index(['business_id', 'type', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cs_message_log');
    }
};
