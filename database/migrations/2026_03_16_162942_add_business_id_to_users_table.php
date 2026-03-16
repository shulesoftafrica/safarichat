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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_business_id')->nullable()->after('id');
            $table->foreign('parent_business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->string('role')->default('member')->after('parent_business_id'); // owner, member
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['parent_business_id']);
            $table->dropColumn(['parent_business_id', 'role']);
        });
    }
};
