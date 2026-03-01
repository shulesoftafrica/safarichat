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
        Schema::table('nurture_library', function (Blueprint $table) {
            // Add product relationship
            $table->unsignedBigInteger('product_id')->nullable()->after('business_id');
            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');
            
            // Flag for business-level vs product-level messages
            $table->boolean('is_business_level')->default(false)->after('product_id');
            
            // Index for fast product-specific lookups
            $table->index(['product_id', 'success_rate'], 'idx_product_nurture');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nurture_library', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_product_nurture');
            
            // Drop foreign key and column
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'is_business_level']);
        });
    }
};
