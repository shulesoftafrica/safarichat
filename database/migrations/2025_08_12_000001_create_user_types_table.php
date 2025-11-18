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
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default user types
        DB::table('user_types')->insert([
            ['name' => 'Small Businesses', 'description' => '1-10 employees', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Medium Businesses', 'description' => '11-50 employees', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Large Enterprises', 'description' => '50+ employees', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Individual Customers', 'description' => 'Personal use', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Retail & E-commerce', 'description' => 'Retail businesses', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hospitality & Tourism', 'description' => 'Hotels, restaurants, tours', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Healthcare', 'description' => 'Medical and health services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Education', 'description' => 'Schools and training', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Finance & Banking', 'description' => 'Financial services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Technology', 'description' => 'Tech companies and services', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_types');
    }
};
