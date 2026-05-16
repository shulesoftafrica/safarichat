<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            // Stores a list of phone numbers that should be completely ignored
            // for this instance (e.g. personal contacts, test numbers).
            // Format: [{"phone": "255714825469", "label": "John (friend)"}, ...]
            // Phones are stored as digits-only (no +) for reliable comparison.
            $table->json('ignored_contacts')->nullable()->after('instance_type');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_instances', function (Blueprint $table) {
            $table->dropColumn('ignored_contacts');
        });
    }
};
