<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountriesCitiesTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Countries table
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Country name (e.g., "Tanzania")
            $table->string('code', 2)->unique(); // ISO 2-letter code (e.g., "TZ")
            $table->string('phone_code', 5); // Phone code (e.g., "+255")
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('code');
            $table->index('phone_code');
        });

        // Cities table
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->onDelete('cascade');
            $table->string('name', 100); // City name (e.g., "Dar es Salaam")
            $table->string('slug', 100)->nullable(); // URL-friendly name (e.g., "dar-es-salaam")
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
        Schema::dropIfExists('countries');
    }
}
