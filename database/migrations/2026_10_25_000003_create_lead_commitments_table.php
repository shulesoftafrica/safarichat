<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A promise a lead made that we expect a conversion event to fulfil ("I'll pay
 * tomorrow", "I'll fill the form by Friday"). The scheduler nudges the lead and
 * escalates to the team when the due time passes without a matching event.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lead_commitments')) {
            return;
        }

        Schema::create('lead_commitments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('lead_id')->index();
            $table->unsignedBigInteger('business_contact_id')->nullable();
            $table->string('expected_event_type', 50)->nullable(); // purchase|payment|form_submitted|...
            $table->text('promised_text')->nullable();             // the lead's own words
            $table->timestamp('due_by')->nullable();
            $table->string('status', 20)->default('pending');      // pending|fulfilled|missed|cancelled
            $table->unsignedBigInteger('fulfilled_event_id')->nullable();
            $table->string('created_by', 30)->nullable();          // 'ai' or a user id
            $table->timestamp('nudged_at')->nullable();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_commitments');
    }
};
