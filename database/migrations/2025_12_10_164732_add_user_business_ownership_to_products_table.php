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
        Schema::table('products', function (Blueprint $table) {
            // Add user and business ownership - nullable first
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->unsignedBigInteger('business_id')->nullable()->after('user_id');
        });

        // Update existing products to have a user_id (assign to first user if any)
        $firstUserId = \App\Models\User::first()?->id;
        if ($firstUserId) {
            \Illuminate\Support\Facades\DB::table('products')
                ->whereNull('user_id')
                ->update(['user_id' => $firstUserId]);
        }

        Schema::table('products', function (Blueprint $table) {
            // Now make user_id not nullable and add constraints
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            
            // Add foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            
            // Add indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['business_id', 'status']);
            $table->index(['user_id', 'business_id']);
            
            // Make SKU unique per user instead of globally unique
            // First check if the unique constraint exists before trying to drop it
            $table->dropUnique(['sku']);
            $table->unique(['user_id', 'sku'], 'products_user_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop the new unique constraint first
            $table->dropUnique('products_user_sku_unique');
            
            // Restore the original unique constraint
            $table->unique('sku');
            
            // Drop indexes
            $table->dropIndex(['user_id', 'business_id']);
            $table->dropIndex(['business_id', 'status']);
            $table->dropIndex(['user_id', 'status']);
            
            // Drop foreign key columns
            $table->dropForeign(['business_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['business_id', 'user_id']);
        });
    }
};
