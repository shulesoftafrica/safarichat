<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Store the product a campaign promotes.
 *
 * Without this, campaign message personalization has no idea which product was
 * selected and defaults to a generic brand ("SafariChat"), producing messages
 * that name the wrong product. The AI personalizer now reads this to keep the
 * message on the selected product.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaigns') || Schema::hasColumn('campaigns', 'product_id')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->after('business_id')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('campaigns') && Schema::hasColumn('campaigns', 'product_id')) {
            Schema::table('campaigns', function (Blueprint $table) {
                $table->dropColumn('product_id');
            });
        }
    }
};
