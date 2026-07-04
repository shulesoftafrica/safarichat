<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            if (Schema::hasColumn('outgoing_messages', 'media_path')) {
                $table->text('media_path')->nullable()->change();
            }

            if (Schema::hasColumn('outgoing_messages', 'media_url')) {
                $table->text('media_url')->nullable()->change();
            }

            if (Schema::hasColumn('outgoing_messages', 'caption')) {
                $table->text('caption')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_messages', function (Blueprint $table) {
            if (Schema::hasColumn('outgoing_messages', 'media_path')) {
                $table->string('media_path', 255)->nullable()->change();
            }

            if (Schema::hasColumn('outgoing_messages', 'media_url')) {
                $table->string('media_url', 255)->nullable()->change();
            }

            if (Schema::hasColumn('outgoing_messages', 'caption')) {
                $table->string('caption', 255)->nullable()->change();
            }
        });
    }
};