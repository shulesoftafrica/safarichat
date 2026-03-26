<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Deduplicate business_contacts rows that share the same (business_id, guest_phone).
 *
 * These duplicates were produced by the pre-fix code that looked up contacts
 * without a business_id scope, causing different sessions of the same phone number
 * to sometimes create new rows instead of updating the existing one.
 *
 * Run this BEFORE running the migration that adds the unique constraint:
 *   php artisan contacts:deduplicate-within-business
 *   php artisan contacts:deduplicate-within-business --dry-run   (preview only)
 */
class DeduplicateBusinessContactsCommand extends Command
{
    protected $signature = 'contacts:deduplicate-within-business
                            {--dry-run : Show what would be merged without making changes}
                            {--force  : Skip the confirmation prompt}';

    protected $description = 'Merge duplicate business_contacts rows that share (business_id, guest_phone). Run before adding the unique index.';

    /** Tables whose FK references business_contact_id must be re-pointed. */
    private const DEPENDENT_TABLES = [
        ['table' => 'leads',             'fk' => 'business_contact_id'],
        ['table' => 'incoming_messages', 'fk' => 'business_contact_id'],
        ['table' => 'outgoing_messages', 'fk' => 'business_contact_id'],
        ['table' => 'messages',          'fk' => 'business_contact_id'],
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('[DRY RUN] No changes will be written to the database.');
        }

        // Find all (business_id, guest_phone) pairs that have more than one row.
        $duplicateGroups = DB::table('business_contacts')
            ->select('business_id', 'guest_phone', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('guest_phone')
            ->where('guest_phone', '!=', '')
            ->groupBy('business_id', 'guest_phone')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicateGroups->isEmpty()) {
            $this->info('No duplicate (business_id, guest_phone) pairs found. Nothing to do.');
            return Command::SUCCESS;
        }

        $this->info("Found {$duplicateGroups->count()} duplicate group(s).");

        if (!$isDryRun && !$this->option('force')) {
            if (!$this->confirm("Proceed with merging duplicates? This will modify the database.")) {
                $this->info('Aborted.');
                return Command::SUCCESS;
            }
        }

        $mergedCount    = 0;
        $deletedCount   = 0;
        $errorCount     = 0;

        foreach ($duplicateGroups as $group) {
            try {
                $this->processGroup($group->business_id, $group->guest_phone, $group->cnt, $isDryRun, $mergedCount, $deletedCount);
            } catch (\Throwable $e) {
                $this->error("Error processing group (business_id={$group->business_id}, phone={$group->guest_phone}): {$e->getMessage()}");
                Log::error('DeduplicateBusinessContacts: group error', [
                    'business_id' => $group->business_id,
                    'guest_phone' => $group->guest_phone,
                    'error'       => $e->getMessage(),
                ]);
                $errorCount++;
            }
        }

        $this->newLine();
        $this->info("Done.");
        $this->table(['Metric', 'Count'], [
            ['Groups processed', $duplicateGroups->count()],
            ['Contacts re-pointed (rows updated across dependent tables)', $mergedCount],
            ['Duplicate contact rows deleted', $deletedCount],
            ['Errors', $errorCount],
        ]);

        if ($isDryRun) {
            $this->warn('[DRY RUN] No changes were written.');
        }

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function processGroup(
        int    $businessId,
        string $phone,
        int    $duplicateCount,
        bool   $isDryRun,
        int    &$mergedCount,
        int    &$deletedCount
    ): void {
        // Fetch all duplicates for this group, oldest first.
        $contacts = DB::table('business_contacts')
            ->where('business_id', $businessId)
            ->where('guest_phone', $phone)
            ->orderBy('id', 'asc')
            ->get();

        // The oldest record is the canonical one we keep.
        $canonical   = $contacts->shift(); // removes from collection
        $duplicates  = $contacts;          // remainder are to be re-pointed and deleted

        $this->line(sprintf(
            "  business_id=%d phone=%s — keeping id=%d, merging %d duplicate(s)",
            $businessId,
            $phone,
            $canonical->id,
            $duplicates->count()
        ));

        foreach ($duplicates as $dup) {
            if ($isDryRun) {
                // Just report what would happen
                foreach (self::DEPENDENT_TABLES as $dep) {
                    $rowCount = DB::table($dep['table'])->where($dep['fk'], $dup->id)->count();
                    if ($rowCount > 0) {
                        $this->line("    [DRY] Would re-point {$rowCount} row(s) in {$dep['table']} from id={$dup->id} → id={$canonical->id}");
                    }
                }
                $this->line("    [DRY] Would delete business_contact id={$dup->id}");
                continue;
            }

            DB::transaction(function () use ($canonical, $dup, &$mergedCount, &$deletedCount) {
                // Re-point all FK references from the duplicate to the canonical row.
                foreach (self::DEPENDENT_TABLES as $dep) {
                    $updated = DB::table($dep['table'])
                        ->where($dep['fk'], $dup->id)
                        ->update([$dep['fk'] => $canonical->id]);
                    if ($updated > 0) {
                        $this->line("    Re-pointed {$updated} row(s) in {$dep['table']}");
                        $mergedCount += $updated;
                    }
                }

                // Merge any non-null fields from the duplicate that the canonical record lacks.
                $updates = [];
                foreach (['guest_name', 'guest_email', 'crm_id', 'preferred_language', 'preferred_tone'] as $field) {
                    if (empty($canonical->{$field}) && !empty($dup->{$field})) {
                        $updates[$field] = $dup->{$field};
                    }
                }
                if (!empty($updates)) {
                    DB::table('business_contacts')->where('id', $canonical->id)->update($updates);
                    $this->line("    Merged fields into canonical: " . implode(', ', array_keys($updates)));
                }

                // Delete the duplicate row.
                DB::table('business_contacts')->where('id', $dup->id)->delete();
                $deletedCount++;

                Log::info('DeduplicateBusinessContacts: merged duplicate', [
                    'canonical_id' => $canonical->id,
                    'deleted_id'   => $dup->id,
                    'business_id'  => $canonical->business_id,
                    'phone'        => $canonical->guest_phone,
                ]);
            });
        }
    }
}
