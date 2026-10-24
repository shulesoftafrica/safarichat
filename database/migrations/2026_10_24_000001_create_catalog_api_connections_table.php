<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A generic "catalog API" connection: lets a business point SafariChat at their
 * own stock-management software's product API (base URL + auth) so the catalog
 * syncs in, instead of re-entering products. One business <-> one API source.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('catalog_api_connections')) {
            return;
        }

        Schema::create('catalog_api_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->unique();
            $table->string('name')->nullable();                 // friendly label
            $table->string('base_url');
            $table->string('catalog_path')->default('/products'); // appended to base_url
            $table->string('auth_type')->default('bearer');      // bearer | header | none
            $table->string('auth_header')->default('Authorization');
            $table->text('auth_value')->nullable();              // token / key
            $table->string('page_param')->default('page');
            $table->string('status')->default('pending');        // pending | connected | error
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('product_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_api_connections');
    }
};
