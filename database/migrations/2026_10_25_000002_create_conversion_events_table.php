<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every conversion signal received (via webhook or internal), whether or not it
 * matched a known lead/contact. This is the audit trail behind the WON flow.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('conversion_events')) {
            return;
        }

        Schema::create('conversion_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('event_config_id')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('business_contact_id')->nullable();
            $table->string('event_type', 50);
            $table->string('source', 30)->nullable();          // webhook|lineshop|manual|shulesoft
            $table->string('identifier')->nullable();          // the phone/email we matched on
            $table->string('identifier_type', 10)->nullable(); // phone|email
            $table->decimal('amount', 14, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('reference')->nullable();           // order no / receipt / application id
            $table->json('metadata')->nullable();              // full raw payload
            $table->string('idempotency_key')->nullable()->index();
            $table->timestamp('occurred_at')->nullable();
            $table->string('status', 20)->default('received'); // received|matched|processed|orphan|failed
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_events');
    }
};
