<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales activity journal: every touch a sales person makes with a customer
 * (call, physical visit, WhatsApp, email, meeting, ...) is logged on the
 * customer's record, optionally spawning a follow-up appointment.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('sales_activities')) {
            return;
        }

        Schema::create('sales_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->nullable()->index();
            $table->unsignedBigInteger('business_contact_id')->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Who logged the activity');
            $table->string('type', 30)->default('other')->comment('call/visit/whatsapp/email/meeting/other');
            $table->text('notes')->nullable();
            $table->timestamp('activity_at')->nullable();
            $table->string('follow_up_text', 500)->nullable()->comment('Raw natural-language follow-up the rep typed');
            $table->unsignedBigInteger('appointment_id')->nullable()->comment('Follow-up appointment this activity created');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_activities');
    }
};
