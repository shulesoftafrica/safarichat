<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOutreachVariantsTable extends Migration
{
    public function up()
    {
        Schema::create('outreach_variants', function (Blueprint $table) {
            $table->id();
            $table->string('variant_key')->unique(); // INTRO_V1, INTRO_V2, etc.
            $table->text('message_template');
            $table->string('category', 50); // intro, follow_up, win_back
            $table->boolean('is_active')->default(true);
            $table->integer('success_rate')->default(0); // Percentage
            $table->timestamps();
            
            $table->index('category');
        });
    }

    public function down()
    {
        Schema::dropIfExists('outreach_variants');
    }
}