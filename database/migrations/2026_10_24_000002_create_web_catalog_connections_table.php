<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remembers a web-store / marketplace / product-feed URL a business imported
 * from, so the scheduled job can auto-refresh it (and a full large shop can be
 * crawled on the cron, where there is no web-request timeout). A business may
 * have several web sources.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('web_catalog_connections')) {
            return;
        }

        Schema::create('web_catalog_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id')->index();
            $table->string('url', 500);
            $table->string('status')->default('connected'); // connected | error
            $table->timestamp('last_synced_at')->nullable();
            $table->unsignedInteger('product_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'url']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_catalog_connections');
    }
};
