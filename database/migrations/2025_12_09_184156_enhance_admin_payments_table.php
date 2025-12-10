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
        Schema::table('admin_payments', function (Blueprint $table) {
            $table->enum('payment_gateway', ['lipa_number', 'stripe', 'manual'])->default('lipa_number')->after('method');
            $table->string('gateway_reference', 255)->nullable()->after('payment_gateway');
            $table->integer('credit_amount')->default(0)->after('gateway_reference');
            $table->integer('subscription_months')->default(1)->after('credit_amount');
            $table->decimal('excess_amount', 10, 2)->default(0)->after('subscription_months');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_payments', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway', 'gateway_reference', 'credit_amount', 'subscription_months', 'excess_amount']);
        });
    }
};
