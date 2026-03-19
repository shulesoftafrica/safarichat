<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sanitizes all phone numbers in the database to ensure they only contain
     * valid characters (digits and optional leading +)
     */
    public function up(): void
    {
        // Tables and columns that contain phone numbers
        $phoneColumns = [
            'leads' => 'phone_number',
            'users' => 'phone',
            'businesses' => 'phone',
            'business_contacts' => 'phone',
            'guests' => 'guest_phone',
            'ai_sales_agents' => 'fallback_number',
            'whatsapp_instances' => 'phone_number',
            'appointments' => 'customer_phone',
        ];

        foreach ($phoneColumns as $table => $column) {
            // Skip if table doesn't exist
            if (!Schema::hasTable($table)) {
                if (method_exists($this, 'command') && $this->command) {
                    $this->command->warn("Table {$table} does not exist, skipping...");
                }
                continue;
            }

            // Skip if column doesn't exist
            if (!Schema::hasColumn($table, $column)) {
                if (method_exists($this, 'command') && $this->command) {
                    $this->command->warn("Column {$column} does not exist in table {$table}, skipping...");
                }
                continue;
            }

            if (method_exists($this, 'command') && $this->command) {
                $this->command->info("Sanitizing phone numbers in {$table}.{$column}...");
            }

            // Get all records with non-null phone numbers
            $records = DB::table($table)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->get(['id', $column]);

            $updated = 0;
            $invalid = 0;

            foreach ($records as $record) {
                $original = $record->{$column};
                $sanitized = $this->sanitizePhoneNumber($original);

                // Update if sanitized version is different
                if ($sanitized !== $original) {
                    // Validate sanitized phone
                    if ($this->isValidPhoneNumber($sanitized)) {
                        DB::table($table)
                            ->where('id', $record->id)
                            ->update([$column => $sanitized]);
                        $updated++;
                    } else {
                        // Log invalid phone numbers for manual review
                        if (method_exists($this, 'command') && $this->command) {
                            $this->command->warn("Invalid phone in {$table}#{$record->id}: {$original} -> {$sanitized}");
                        }
                        $invalid++;
                        
                        // Set to null if completely invalid (optional - comment out to keep original)
                        // DB::table($table)->where('id', $record->id)->update([$column => null]);
                    }
                }
            }

            if (method_exists($this, 'command') && $this->command) {
                $this->command->info("✓ {$table}.{$column}: {$updated} updated, {$invalid} invalid");
            }
        }

        if (method_exists($this, 'command') && $this->command) {
            $this->command->info('Phone number sanitization complete!');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is irreversible as we're cleaning data
        // Original unsanitized data cannot be restored
        if (method_exists($this, 'command') && $this->command) {
            $this->command->warn('This migration cannot be reversed. Phone numbers have been permanently sanitized.');
        }
    }

    /**
     * Sanitize phone number for database storage
     * Removes all non-numeric characters except leading +
     */
    private function sanitizePhoneNumber(?string $number): ?string
    {
        if (empty($number)) {
            return null;
        }

        // Remove all characters except digits and plus
        $sanitized = preg_replace("/[^0-9+]/", '', $number);

        // Ensure only one plus at the beginning
        if (strpos($sanitized, '+') !== false) {
            $sanitized = '+' . preg_replace("/[^0-9]/", '', $sanitized);
        }

        return $sanitized ?: null;
    }

    /**
     * Validate phone number format
     * Returns true if valid, false otherwise
     */
    private function isValidPhoneNumber(?string $number): bool
    {
        if (empty($number)) {
            return false;
        }

        // Extract digits only
        $digits = preg_replace("/[^0-9]/", '', $number);

        // International standard: 7-15 digits
        if (strlen($digits) < 7 || strlen($digits) > 15) {
            return false;
        }

        return true;
    }
};
