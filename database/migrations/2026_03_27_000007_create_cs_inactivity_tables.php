<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cs_inactivity_episodes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->date('started_at');                        // first day of zero activity
            $table->date('ended_at')->nullable();              // NULL if still inactive
            $table->string('tier_reached', 20)->nullable();    // 'at_risk' | 'churned' | 'abandoned'
            $table->timestamp('day3_alert_sent_at')->nullable();
            $table->timestamp('day10_alert_sent_at')->nullable();
            $table->timestamp('recovery_message_sent_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->onDelete('cascade');

            // Index for the common query pattern
            $table->index(['business_id', 'ended_at']);
        });

        Schema::create('cs_escalations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('episode_id')->nullable();
            $table->string('reason', 100)->comment('paid_churned_10d | no_reply_winback');
            $table->string('status', 30)->default('needs_human_followup');
            $table->unsignedBigInteger('assigned_to')->nullable();  // CS team member user_id
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->onDelete('cascade');

            $table->foreign('episode_id')
                  ->references('id')
                  ->on('cs_inactivity_episodes')
                  ->onDelete('set null');

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cs_escalations');
        Schema::dropIfExists('cs_inactivity_episodes');
    }
};
