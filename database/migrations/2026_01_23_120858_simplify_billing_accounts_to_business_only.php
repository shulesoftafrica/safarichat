<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Simplify billing_accounts from polymorphic to business-only relationship
     */
    public function up(): void
    {
        // Step 1: Add business_id column
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable()->after('id');
        });

        // Step 2: Migrate data - set business_id based on owner_type
        DB::statement("
            UPDATE billing_accounts 
            SET business_id = owner_id 
            WHERE owner_type = 'App\\Models\\Business'
        ");

        // For User-owned accounts, find their business
        $userAccounts = DB::table('billing_accounts')
            ->where('owner_type', 'App\\Models\\User')
            ->get();

        foreach ($userAccounts as $account) {
            $business = DB::table('businesses')
                ->where('user_id', $account->owner_id)
                ->first();
            
            if ($business) {
                DB::table('billing_accounts')
                    ->where('id', $account->id)
                    ->update(['business_id' => $business->id]);
            } else {
                // Create a business for this user if none exists
                $user = DB::table('users')->where('id', $account->owner_id)->first();
                if ($user) {
                    $businessId = DB::table('businesses')->insertGetId([
                        'user_id' => $user->id,
                        'name' => $user->business_name ?? $user->name . "'s Business",
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    DB::table('billing_accounts')
                        ->where('id', $account->id)
                        ->update(['business_id' => $businessId]);
                }
            }
        }

        // Step 3: Make business_id NOT NULL and add foreign key
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->unsignedBigInteger('business_id')->nullable(false)->change();
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
        });

        // Step 4: Drop polymorphic columns and index
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->dropIndex('billing_owner_index');
            $table->dropColumn(['owner_type', 'owner_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore polymorphic relationship
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->string('owner_type', 50)->after('id');
            $table->unsignedBigInteger('owner_id')->after('owner_type');
            $table->index(['owner_type', 'owner_id'], 'billing_owner_index');
        });

        // Restore data
        DB::statement("
            UPDATE billing_accounts 
            SET owner_type = 'App\\Models\\Business',
                owner_id = business_id
        ");

        // Drop business_id
        Schema::table('billing_accounts', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropColumn('business_id');
        });
    }
};
