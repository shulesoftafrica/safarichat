<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lead Offer Progress — the angle-rotation ledger.
 *
 * One row per (lead, product/offer). This is the source of truth for
 * "which module have we already pitched to this prospect, how many times,
 * and what happened". The NextBestOfferService reads it to pick the next
 * UNTRIED offer and to stop repeating a pitch the lead ignored.
 *
 * Kept separate from lead_products on purpose: lead_products already carries
 * quote/negotiation/deal semantics used across the app, whereas this table is
 * purely the outreach rotation state. They can coexist without interfering.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_offer_progress')) {
            return;
        }

        Schema::create('lead_offer_progress', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('lead_id');
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('product_id');

            // Rotation lifecycle for THIS offer against THIS lead.
            // pending   — selected/known but not yet pitched
            // pitched   — sent at least once, awaiting a reply
            // engaged   — the lead replied while this offer was in flight
            // rejected  — the lead explicitly declined this offer
            // exhausted — pitched max touches with no engagement → rotate away
            // converted — this offer drove a close
            $table->string('status', 20)->default('pending');

            $table->unsignedInteger('touch_count')->default(0);
            $table->timestamp('first_pitched_at')->nullable();
            $table->timestamp('last_pitched_at')->nullable();
            $table->timestamp('engaged_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->string('last_channel', 30)->nullable();
            $table->string('last_outcome', 60)->nullable();

            // Free-form audit trail (per-touch channel/reason snapshots).
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['lead_id', 'product_id'], 'lead_offer_progress_unique');
            $table->index(['lead_id', 'status'], 'lead_offer_progress_lead_status_idx');
            $table->index(['business_id'], 'lead_offer_progress_business_idx');

            $table->foreign('lead_id')
                ->references('id')
                ->on('leads')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_offer_progress');
    }
};
