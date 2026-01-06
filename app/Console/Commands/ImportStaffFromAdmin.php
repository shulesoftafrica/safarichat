<?php

namespace App\Console\Commands;

use App\Services\AdminCrmIntegrationService;
use App\Services\DataMappingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportStaffFromAdmin extends Command
{
    protected $signature = 'admin:import-staff 
                            {--limit=100 : Number of records to import per batch}
                            {--offset=0 : Starting offset for import}
                            {--dry-run : Preview import without saving}
                            {--force : Force import even if validations fail}';

    protected $description = 'Import staff users from Admin CRM system';

    protected AdminCrmIntegrationService $integrationService;
    protected DataMappingService $mappingService;

    public function __construct(
        AdminCrmIntegrationService $integrationService,
        DataMappingService $mappingService
    ) {
        parent::__construct();
        $this->integrationService = $integrationService;
        $this->mappingService = $mappingService;
    }

    public function handle(): int
    {
        $this->info('Starting Admin CRM Staff Import...');

        // Test connection first
        if (!$this->integrationService->testConnection()) {
            $this->error('Cannot connect to Admin CRM database');
            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Import parameters: Limit={$limit}, Offset={$offset}, Dry Run=" . ($dryRun ? 'Yes' : 'No'));

        try {
            DB::beginTransaction();

            $result = $this->integrationService->importStaff($limit, $offset, $dryRun);

            if ($dryRun) {
                $this->displayDryRunResults($result);
                DB::rollBack();
                return Command::SUCCESS;
            }

            if ($result['success']) {
                $this->displaySuccessResults($result);
                
                if (!$force && $result['errors'] > 0) {
                    $this->warn('Some errors occurred. Use --force to proceed with partial import.');
                    if (!$this->confirm('Continue with import despite errors?')) {
                        DB::rollBack();
                        return Command::FAILURE;
                    }
                }

                DB::commit();
                $this->info('Staff import completed successfully!');
                return Command::SUCCESS;
            } else {
                $this->error('Staff import failed: ' . ($result['message'] ?? 'Unknown error'));
                DB::rollBack();
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Import failed with exception: ' . $e->getMessage());
            $this->line('Stack trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    protected function displayDryRunResults(array $result): void
    {
        $this->info('=== DRY RUN RESULTS ===');
        $this->info("Records processed: {$result['processed']}");
        $this->info("Would be imported: {$result['imported']}");
        $this->info("Would be skipped: {$result['skipped']}");
        $this->info("Validation errors: {$result['errors']}");

        if (!empty($result['sample_data'])) {
            $this->info("\n--- Sample Record (first record to be imported) ---");
            foreach ($result['sample_data'] as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                $this->line("{$key}: {$value}");
            }
        }

        if (!empty($result['validation_errors'])) {
            $this->warn("\n--- Validation Errors ---");
            foreach (array_slice($result['validation_errors'], 0, 10) as $error) {
                $this->line("• {$error}");
            }
            if (count($result['validation_errors']) > 10) {
                $this->line("... and " . (count($result['validation_errors']) - 10) . " more errors");
            }
        }
    }

    protected function displaySuccessResults(array $result): void
    {
        $this->info('=== IMPORT RESULTS ===');
        $this->info("Records processed: {$result['processed']}");
        $this->info("Successfully imported: {$result['imported']}");
        $this->info("Skipped (already exist): {$result['skipped']}");
        $this->info("Errors: {$result['errors']}");

        if (!empty($result['error_details'])) {
            $this->warn("\n--- Error Details ---");
            foreach (array_slice($result['error_details'], 0, 5) as $error) {
                $this->line("• {$error}");
            }
            if (count($result['error_details']) > 5) {
                $this->line("... and " . (count($result['error_details']) - 5) . " more errors");
            }
        }

        // Display import statistics
        if (method_exists($this->integrationService, 'getImportStats')) {
            $stats = $this->integrationService->getImportStats();
            $this->info("\n=== IMPORT STATISTICS ===");
            $this->info("Total staff in Admin CRM: {$stats['total_staff']}");
            $this->info("Total staff in SafariChat: {$stats['total_safarichat_users']}");
            $this->info("Staff imported from Admin: {$stats['imported_staff']}");
        }
    }
}