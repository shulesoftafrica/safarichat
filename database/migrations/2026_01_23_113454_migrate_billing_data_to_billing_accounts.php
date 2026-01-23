<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add billing_account_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('billing_account_id')->nullable()->after('id');
            $table->foreign('billing_account_id')->references('id')->on('billing_accounts')->onDelete('set null');
        });

        // Add billing_account_id to businesses table
        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedBigInteger('billing_account_id')->nullable()->after('id');
            $table->foreign('billing_account_id')->references('id')->on('billing_accounts')->onDelete('set null');
        });

        // Migrate existing billing data from businesses table
        $businesses = DB::table('businesses')->get();
        foreach ($businesses as $business) {
            $planConfig = config("safarichat_billing.plans.{$business->subscription_plan}", 
                                config('safarichat_billing.plans.trial'));
            
            $billingAccountId = DB::table('billing_accounts')->insertGetId([
                'owner_type' => 'App\\Models\\Business',
                'owner_id' => $business->id,
                'subscription_plan' => $business->subscription_plan ?? 'trial',
                'ai_credits' => $business->ai_credits ?? 0,
                'ai_credits_used' => 0,
                'available_credits' => $business->ai_credits ?? 0,
                'subscription_started_at' => $business->created_at ?? now(),
                'subscription_expires_at' => isset($business->subscription_expires_at) 
                    ? $business->subscription_expires_at 
                    : now()->addDays($planConfig['duration_days'] ?? 30),
                'status' => 'active',
                'credits_rollover' => $planConfig['credits_rollover'] ?? false,
                
                // Sync limits from config
                'max_contacts' => $planConfig['limits']['max_contacts'] ?? 10,
                'max_products' => $planConfig['limits']['max_products'] ?? 1,
                'whatsapp_channels' => $planConfig['limits']['whatsapp_channels'] ?? 1,
                'customer_followups' => $planConfig['limits']['customer_followups'] ?? false,
                'customer_categorization' => $planConfig['limits']['customer_categorization'] ?? false,
                'booking_calendars' => $planConfig['limits']['booking_calendars'] ?? false,
                'sales_reports' => $planConfig['limits']['sales_reports'] ?? false,
                'unlimited_messages' => $planConfig['limits']['unlimited_messages'] ?? false,
                
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link business to billing account
            DB::table('businesses')->where('id', $business->id)->update([
                'billing_account_id' => $billingAccountId
            ]);
        }

        // Migrate users who have their own billing data
        $users = DB::table('users')
            ->whereNotNull('subscription_plan')
            ->orWhereNotNull('ai_credits')
            ->get();

        foreach ($users as $user) {
            // Check if user's business already has billing account
            $business = DB::table('businesses')->where('user_id', $user->id)->first();
            if ($business && isset($business->billing_account_id)) {
                DB::table('users')->where('id', $user->id)->update([
                    'billing_account_id' => $business->billing_account_id
                ]);
                continue;
            }

            $planConfig = config("safarichat_billing.plans.{$user->subscription_plan}", 
                                config('safarichat_billing.plans.trial'));
            
            $billingAccountId = DB::table('billing_accounts')->insertGetId([
                'owner_type' => 'App\\Models\\User',
                'owner_id' => $user->id,
                'subscription_plan' => $user->subscription_plan ?? 'trial',
                'ai_credits' => max($user->ai_credits ?? 0, $user->available_credits ?? 0),
                'ai_credits_used' => 0,
                'available_credits' => $user->available_credits ?? 0,
                'subscription_started_at' => $user->created_at ?? now(),
                'status' => 'active',
                'credits_rollover' => $planConfig['credits_rollover'] ?? false,
                
                // Sync limits from config
                'max_contacts' => $planConfig['limits']['max_contacts'] ?? 10,
                'max_products' => $planConfig['limits']['max_products'] ?? 1,
                'whatsapp_channels' => $planConfig['limits']['whatsapp_channels'] ?? 1,
                'customer_followups' => $planConfig['limits']['customer_followups'] ?? false,
                'customer_categorization' => $planConfig['limits']['customer_categorization'] ?? false,
                'booking_calendars' => $planConfig['limits']['booking_calendars'] ?? false,
                'sales_reports' => $planConfig['limits']['sales_reports'] ?? false,
                'unlimited_messages' => $planConfig['limits']['unlimited_messages'] ?? false,
                
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $user->id)->update([
                'billing_account_id' => $billingAccountId
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['billing_account_id']);
            $table->dropColumn('billing_account_id');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['billing_account_id']);
            $table->dropColumn('billing_account_id');
        });
    }
};
