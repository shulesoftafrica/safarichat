<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add identity columns the Lead model declares in $fillable (and, for phone_number,
 * exposes get/set mutators for) but that were missing from the leads table.
 *
 * Their absence throws on live: the WhatsApp send path looks up a lead with
 *   ...->orWhere('phone_number', $phone)
 * producing "column phone_number does not exist", which fails the reply send.
 * Direct create()/firstOrCreate() paths that set name/phone_number/email also break.
 * (safeCreate() strips them via filterPersistableAttributes, which is why the app
 * limped along, but query/insert paths that reference them directly do not.)
 *
 * Purely additive and idempotent (each column guarded by hasColumn).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'name')) {
                $table->string('name')->nullable();
            }
            if (!Schema::hasColumn('leads', 'phone_number')) {
                $table->string('phone_number')->nullable()->index();
            }
            if (!Schema::hasColumn('leads', 'email')) {
                $table->string('email')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('leads')) {
            return;
        }

        Schema::table('leads', function (Blueprint $table) {
            foreach (['name', 'phone_number', 'email'] as $col) {
                if (Schema::hasColumn('leads', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
