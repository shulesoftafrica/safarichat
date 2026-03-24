<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCitiesTableAndFixCountries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fix countries table - add proper country_code column if needed
        // Note: Keep existing structure intact, just add what we need
        Schema::table('countries', function (Blueprint $table) {
            // Add proper structured columns
            // name column currently has phone code (255,254, etc.)
            // dialling_code has country name  
            // We'll add iso_code for proper 2-letter codes
            if (!Schema::hasColumn('countries', 'iso_code')) {
                $table->string('iso_code', 2)->nullable()->after('country_code');
                $table->index('iso_code');
            }
            if (!Schema::hasColumn('countries', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('exchange_rate_source');
            }
        });

        // Create cities table
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->string('name', 100); // City name (e.g., "Dar es Salaam")
            $table->string('slug', 100)->nullable(); // URL-friendly name
            $table->boolean('is_major')->default(false); // Flag for major cities
            $table->integer('sort_order')->default(0); // For custom sorting
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('country_id');
            $table->index('slug');
            $table->index(['country_id', 'is_major']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cities');
        
        Schema::table('countries', function (Blueprint $table) {
            if (Schema::hasColumn('countries', 'iso_code')) {
                $table->dropColumn('iso_code');
            }
            if (Schema::hasColumn('countries', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
}
