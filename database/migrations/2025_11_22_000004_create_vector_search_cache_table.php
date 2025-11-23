<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVectorSearchCacheTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vector_search_cache', function (Blueprint $table) {
            $table->id();
            $table->string('query_hash', 64); // SHA256 hash of search query
            $table->string('query_text', 1000);
            $table->json('product_ids')->nullable(); // Filter by specific products
            $table->json('search_results'); // Cached results with scores
            $table->timestamp('expiry_time');
            $table->integer('hit_count')->default(1);
            $table->timestamps();
            
            // Indexes for efficient lookups
            $table->index('query_hash');
            $table->index('expiry_time');
            $table->index(['query_hash', 'expiry_time']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vector_search_cache');
    }
}