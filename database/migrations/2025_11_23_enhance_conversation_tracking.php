<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add fields to leads table for better tracking
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'ai_sales_agent_id')) {
                $table->unsignedBigInteger('ai_sales_agent_id')->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('leads', 'last_contact_at')) {
                $table->timestamp('last_contact_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('leads', 'follow_up_sent_at')) {
                $table->timestamp('follow_up_sent_at')->nullable()->after('last_contact_at');
            }
            if (!Schema::hasColumn('leads', 'event_id')) {
                $table->unsignedBigInteger('event_id')->nullable()->after('user_id');
            }
        });

        // Add fields to conversations table for better context
        Schema::table('conversations', function (Blueprint $table) {
            if (!Schema::hasColumn('conversations', 'ai_sales_agent_id')) {
                $table->unsignedBigInteger('ai_sales_agent_id')->nullable()->after('lead_id');
            }
            if (!Schema::hasColumn('conversations', 'message_type')) {
                $table->enum('message_type', ['inbound', 'outbound'])->default('inbound')->after('message');
            }
            if (!Schema::hasColumn('conversations', 'sender_type')) {
                $table->enum('sender_type', ['customer', 'ai_agent', 'user_manual', 'ai_agent_followup'])->default('customer')->after('message_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['ai_sales_agent_id', 'last_contact_at', 'follow_up_sent_at', 'event_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['ai_sales_agent_id', 'message_type', 'sender_type']);
        });
    }
};