<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAiFieldsToProductsTable extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Check if columns don't already exist before adding them
            if (!Schema::hasColumn('products', 'ai_description')) {
                $table->text('ai_description')->nullable()->after('description');
            }
            if (!Schema::hasColumn('products', 'conversion_rate')) {
                $table->decimal('conversion_rate', 5, 2)->default(0.00)->after('max_discount');
            }
            if (!Schema::hasColumn('products', 'min_negotiable_price')) {
                $table->decimal('min_negotiable_price', 10, 2)->nullable()->after('wholesale_price');
            }
            if (!Schema::hasColumn('products', 'low_stock_threshold')) {
                $table->integer('low_stock_threshold')->default(10)->after('quantity');
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'ai_description',
                'conversion_rate', 
                'min_negotiable_price',
                'low_stock_threshold'
            ]);
        });
    }
}