<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add offer/rotation columns that the Product model already declares in
 * $fillable/$casts but that were missing from the products table.
 *
 * These power the Next-Best-Offer engine's ranking signals:
 *   - target_industry   → persona/industry match
 *   - upsell_products    → the "what to pitch next" rotation chain
 *   - key_features       → richer AI pitch content
 *   - common_objections  → pre-loaded rebuttals for inbound replies
 *
 * Purely additive and idempotent (each column guarded by hasColumn).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'target_industry')) {
                $table->string('target_industry')->nullable()->after('category');
            }
            if (!Schema::hasColumn('products', 'key_features')) {
                $table->json('key_features')->nullable();
            }
            if (!Schema::hasColumn('products', 'common_objections')) {
                $table->json('common_objections')->nullable();
            }
            if (!Schema::hasColumn('products', 'upsell_products')) {
                $table->json('upsell_products')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            foreach (['target_industry', 'key_features', 'common_objections', 'upsell_products'] as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
