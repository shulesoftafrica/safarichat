<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove obsolete billing_account_id since billing is now accessed through business relationship
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['billing_account_id']);
            $table->dropColumn('billing_account_id');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropForeign(['billing_account_id']);
            $table->dropColumn('billing_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('billing_account_id')->nullable();
            $table->foreign('billing_account_id')->references('id')->on('billing_accounts')->onDelete('set null');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedBigInteger('billing_account_id')->nullable();
            $table->foreign('billing_account_id')->references('id')->on('billing_accounts')->onDelete('set null');
        });
    }
};
