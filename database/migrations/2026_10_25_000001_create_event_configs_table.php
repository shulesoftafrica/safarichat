<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner-configured conversion events (the "Events & Automations" tab). Each row
 * is one event a third-party system can POST to via its unique webhook URL
 * (/api/events/hook/{token}); the config also IS the rule — what status to set
 * and what actions to run when the event is received.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('event_configs')) {
            return;
        }

        Schema::create('event_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->unsignedBigInteger('ai_sales_agent_id')->nullable();
            $table->string('name');
            $table->string('event_type', 50);                 // purchase|payment|form_submitted|signup|registration|custom
            $table->string('token', 64)->unique();            // the secret embedded in the webhook URL
            $table->boolean('is_enabled')->default(false);
            $table->string('set_status', 30)->nullable();     // Lead status to apply (e.g. CONVERTED/CLOSED)
            $table->json('actions')->nullable();              // ["congratulate","stop_selling","handoff_after_sale","notify_owner","record_revenue"]
            $table->text('message_template')->nullable();     // optional congratulations copy
            $table->string('match_by', 10)->default('phone'); // phone|email
            $table->json('field_map')->nullable();            // optional: {"phone":"msisdn","reference":"order_no",...}
            $table->timestamp('last_received_at')->nullable();
            $table->unsignedInteger('received_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_configs');
    }
};
