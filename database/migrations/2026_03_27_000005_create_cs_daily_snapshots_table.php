<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cs_daily_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->onDelete('cascade');
            $table->date('snapshot_date');

            // Conversation counts
            $table->unsignedInteger('total_conversations')->default(0);
            $table->unsignedInteger('new_prospects')->default(0);
            $table->unsignedInteger('active_leads')->default(0);
            $table->unsignedInteger('converted')->default(0);
            $table->unsignedInteger('churned')->default(0);
            $table->unsignedInteger('stage_changes')->default(0);

            // Lead stage breakdown (maps to Lead::STATUS_* constants)
            $table->unsignedInteger('lead_new')->default(0);        // NEW + OUTREACHED
            $table->unsignedInteger('lead_interested')->default(0); // REPLIED
            $table->unsignedInteger('lead_engaged')->default(0);    // ENGAGED + QUALIFIED + PITCHED + ...
            $table->unsignedInteger('lead_converted')->default(0);  // CLOSED
            $table->unsignedInteger('lead_churned')->default(0);    // LOST or is_churned

            $table->timestamp('created_at')->useCurrent();

            // Only one snapshot per business per day
            $table->unique(['business_id', 'snapshot_date']);

            $table->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cs_daily_snapshots');
    }
};
