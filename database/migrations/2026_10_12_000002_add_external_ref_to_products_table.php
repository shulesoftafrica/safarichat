<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Track products that were synced from an external source (e.g. LineShop) so the
 * catalog sync is idempotent (upsert keyed by business + source + external ref)
 * and synced products can be distinguished from manually-added ones.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'external_source')) {
                $table->string('external_source', 30)->nullable()->index(); // 'lineshop'
            }
            if (!Schema::hasColumn('products', 'external_ref')) {
                $table->string('external_ref', 100)->nullable();            // LineShop product code/id
            }
            if (!Schema::hasColumn('products', 'external_synced_at')) {
                $table->timestamp('external_synced_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['external_source', 'external_ref', 'external_synced_at'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
