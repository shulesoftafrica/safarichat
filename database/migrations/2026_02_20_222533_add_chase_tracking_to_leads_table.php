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
        Schema::table('leads', function (Blueprint $table) {
            // Add columns for tracking chase campaign and lead replies
            if (!Schema::hasColumn('leads', 'last_reply_at')) {
                $table->timestamp('last_reply_at')->nullable()->after('last_contact_at');
            }
            if (!Schema::hasColumn('leads', 'last_chase_at')) {
                $table->timestamp('last_chase_at')->nullable()->after('last_reply_at');
            }
            if (!Schema::hasColumn('leads', 'chase_count')) {
                $table->integer('chase_count')->default(0)->after('last_chase_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['last_reply_at', 'last_chase_at', 'chase_count']);
        });
    }
};
