<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Internal appointments (e.g. "call the customer back") remind only the sales
 * team + owner — never the customer. Customer-facing meetings keep is_internal
 * = false and still remind the customer.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'is_internal')) {
                $table->boolean('is_internal')->default(false)->after('appointment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'is_internal')) {
                $table->dropColumn('is_internal');
            }
        });
    }
};
