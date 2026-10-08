<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-stage appointment reminders: track the "24h before" and the near-time
 * ("1h before") reminders independently, for both the customer and the team,
 * instead of a single reminder_sent boolean that can only fire once.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'reminder_24h_sent_at')) {
                $table->timestamp('reminder_24h_sent_at')->nullable()->after('reminder_sent_at');
            }
            if (!Schema::hasColumn('appointments', 'reminder_1h_sent_at')) {
                $table->timestamp('reminder_1h_sent_at')->nullable()->after('reminder_24h_sent_at');
            }
            if (!Schema::hasColumn('appointments', 'team_notified_at')) {
                $table->timestamp('team_notified_at')->nullable()->after('reminder_1h_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            foreach (['reminder_24h_sent_at', 'reminder_1h_sent_at', 'team_notified_at'] as $col) {
                if (Schema::hasColumn('appointments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
