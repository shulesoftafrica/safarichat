<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('cs_welcome_sent_at')->nullable()
                  ->after('locale')
                  ->comment('When the welcome CS message was sent; NULL = not yet sent');
            $table->timestamp('cs_first_product_message_sent_at')->nullable()
                  ->after('cs_welcome_sent_at')
                  ->comment('When the first-product CS guide was sent; NULL = not yet sent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'cs_welcome_sent_at',
                'cs_first_product_message_sent_at',
            ]);
        });
    }
};
