<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageAttachmentToProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('products', 'image_path')) {
                $table->string('image_path')->nullable()->after('ai_generated_description');
            }
            if (!Schema::hasColumn('products', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('image_path');
            }
            if (!Schema::hasColumn('products', 'image_original_name')) {
                $table->string('image_original_name')->nullable()->after('attachment_path');
            }
            if (!Schema::hasColumn('products', 'attachment_original_name')) {
                $table->string('attachment_original_name')->nullable()->after('image_original_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'attachment_path', 'image_original_name', 'attachment_original_name']);
        });
    }
}
