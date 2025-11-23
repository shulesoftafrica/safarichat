<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductServiceDistinction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Product Type Classification
            $table->enum('product_type', ['tangible', 'service'])
                  ->default('tangible')
                  ->after('sku');
            
            // Service-Specific Fields
            $table->string('service_delivery_type')->nullable()
                  ->after('product_type'); // 'onsite', 'remote', 'hybrid'
            
            $table->integer('service_duration_days')->nullable()
                  ->after('service_delivery_type');
            
            $table->json('service_deliverables')->nullable()
                  ->after('service_duration_days');
            
            $table->boolean('requires_consultation')->default(false)
                  ->after('service_deliverables');
            
            $table->string('pricing_type')->nullable()
                  ->after('pricing_model'); // 'hourly', 'daily', 'project', 'subscription', 'one-time'
            
            $table->decimal('hourly_rate', 10, 2)->nullable()
                  ->after('pricing_type');
            
            $table->json('service_tiers')->nullable()
                  ->after('hourly_rate');
            
            $table->text('prerequisites')->nullable()
                  ->after('service_tiers');
            
            // Index for filtering
            $table->index('product_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_type',
                'service_delivery_type',
                'service_duration_days',
                'service_deliverables',
                'requires_consultation',
                'pricing_type',
                'hourly_rate',
                'service_tiers',
                'prerequisites'
            ]);
        });
    }
}