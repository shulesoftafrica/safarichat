<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Add a unique constraint on (business_id, guest_phone) to business_contacts.
 *
 * This enforces per-business contact isolation at the database level:
 * - The same phone number CAN appear in different businesses (correct).
 * - The same phone number CANNOT appear twice in the SAME business (prevented).
 *
 * IMPORTANT: Run the deduplication command before this migration:
 *   php artisan contacts:deduplicate-within-business
 *
 * The migration guards against remaining duplicates by aborting if any exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Safety check: abort if there are still duplicate (business_id, guest_phone) pairs.
        $duplicates = DB::table('business_contacts')
            ->select('business_id', 'guest_phone', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('guest_phone')
            ->where('guest_phone', '!=', '')
            ->whereNotNull('business_id')
            ->groupBy('business_id', 'guest_phone')
            ->having('cnt', '>', 1)
            ->count();

        if ($duplicates > 0) {
            throw new \RuntimeException(
                "Cannot add unique constraint: {$duplicates} duplicate (business_id, guest_phone) group(s) exist. " .
                "Run: php artisan contacts:deduplicate-within-business"
            );
        }

        Schema::table('business_contacts', function (Blueprint $table) {
            // Index only rows where guest_phone is not null and not empty,
            // so that contacts with no phone (manual entries) are not blocked.
            // PostgreSQL does not support partial unique indexes via Blueprint — we use raw SQL below.
        });

        // Raw partial unique index: only enforced when guest_phone is not null/empty.
        // This is safe for PostgreSQL (which SafariChat uses).
        DB::statement("
            CREATE UNIQUE INDEX business_contacts_business_phone_unique
            ON business_contacts (business_id, guest_phone)
            WHERE business_id IS NOT NULL
              AND guest_phone IS NOT NULL
              AND guest_phone != ''
        ");

        Log::info('Migration: added unique index business_contacts_business_phone_unique');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS business_contacts_business_phone_unique');
    }
};
