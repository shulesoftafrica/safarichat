<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'subscription_status')) {
                $table->enum('subscription_status', ['trial', 'active', 'inactive', 'expired'])->default('trial')->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            }
            if (!Schema::hasColumn('users', 'country_code')) {
                $table->string('country_code', 3)->default('TZ')->after('trial_ends_at');
            }
            if (!Schema::hasColumn('users', 'available_credits')) {
                $table->integer('available_credits')->default(0)->after('country_code');
            }
            if (!Schema::hasColumn('users', 'whatsapp_number')) {
                $table->string('whatsapp_number', 20)->nullable()->after('available_credits');
            }
            if (!Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('whatsapp_number');
            }
            
            if (!Schema::hasIndex('users', 'idx_users_subscription_status')) {
                $table->index('subscription_status', 'idx_users_subscription_status');
            }
            if (!Schema::hasIndex('users', 'idx_users_trial_ends')) {
                $table->index('trial_ends_at', 'idx_users_trial_ends');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasIndex('users', 'idx_users_subscription_status')) {
                $table->dropIndex('idx_users_subscription_status');
            }
            if (Schema::hasIndex('users', 'idx_users_trial_ends')) {
                $table->dropIndex('idx_users_trial_ends');
            }
            $table->dropColumn(['subscription_status', 'trial_ends_at', 'available_credits', 'whatsapp_number', 'last_activity_at']);
            // Note: Not dropping country_code as it may be used elsewhere
        });
    }
};
