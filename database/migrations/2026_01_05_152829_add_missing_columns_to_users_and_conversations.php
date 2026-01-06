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
        // Add phone column to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
        });
        
        // Fix conversations table constraints
        Schema::table('conversations', function (Blueprint $table) {
            $table->bigInteger('contact_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
        
        Schema::table('conversations', function (Blueprint $table) {
            $table->bigInteger('contact_id')->nullable(false)->change();
        });
    }
};
