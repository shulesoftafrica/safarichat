<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores a business's link to its LineShop (Stock Manager Advance) shop, so
 * SafariChat can sync the catalog + live stock instead of the owner re-entering
 * thousands of products by hand. One business <-> one LineShop shop (client_id).
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('lineshop_connections')) {
            return;
        }

        Schema::create('lineshop_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->unique();
            $table->string('base_url');                       // e.g. https://wecarepharmacy.lineshop.africa
            $table->unsignedInteger('client_id');             // LineShop sma_settings.client_id
            $table->string('api_key');                        // LineShop sma_settings.api_key
            $table->string('shop_name')->nullable();
            $table->string('status')->default('pending');     // pending | connected | error
            $table->string('webhook_token', 64)->nullable();  // token LineShop uses to push stock changes
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('product_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lineshop_connections');
    }
};
