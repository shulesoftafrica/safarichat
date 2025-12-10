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
        Schema::table('admin_packages', function (Blueprint $table) {
            $table->integer('max_contacts')->default(0)->after('price');
            $table->integer('max_products')->default(0)->after('max_contacts');
            $table->enum('package_type', ['winga', 'pro', 'enterprise', 'corporate'])->default('winga')->after('max_products');
            $table->enum('billing_interval', ['monthly', 'yearly', 'custom'])->default('monthly')->after('package_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_packages', function (Blueprint $table) {
            $table->dropColumn(['max_contacts', 'max_products', 'package_type', 'billing_interval']);
        });
    }
};
