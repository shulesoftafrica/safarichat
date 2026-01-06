<?php

namespace App\Console\Commands;

use App\Services\AdminCrmIntegrationService;
use App\Services\DataMappingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTasksFromAdmin extends Command
{
    protected $signature = 'admin:import-tasks 
                            {--limit=200 : Number of records to import per batch}
                            {--offset=0 : Starting offset for import}
                            {--client-id= : Import tasks for specific client ID}
                            {--user-id= : Import tasks created by specific user ID}
                            {--date-from= : Import tasks from this date (Y-m-d format)}
                            {--date-to= : Import tasks until this date (Y-m-d format)}
                            {--dry-run : Preview import without saving}
                            {--force : Force import even if validations fail}';

    protected $description = 'Import task history from Admin CRM system as conversations/interactions';

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
        $this->info('Starting Admin CRM Task Import...');

        // Test connection first
        if (!$this->integrationService->testConnection()) {
            $this->error('Cannot connect to Admin CRM database');
            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $clientId = $this->option('client-id');
        $userId = $this->option('user-id');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $filters = [];
        if ($clientId) {
            $filters['client_id'] = (int) $clientId;
            $this->info("Filtering by client ID: {$clientId}");
        }
        if ($userId) {
            $filters['user_id'] = (int) $userId;
            $this->info("Filtering by user ID: {$userId}");
        }
        if ($dateFrom) {
            $filters['date_from'] = $dateFrom;
            $this->info("Filtering from date: {$dateFrom}");
        }
        if ($dateTo) {
            $filters['date_to'] = $dateTo;
            $this->info("Filtering to date: {$dateTo}");
        }

        $this->info("Import parameters: Limit={$limit}, Offset={$offset}, Dry Run=" . ($dryRun ? 'Yes' : 'No'));

        try {
            DB::beginTransaction();

            $result = $this->integrationService->importTasks($limit, $offset, $filters, $dryRun);

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
                $this->info('Task import completed successfully!');
                return Command::SUCCESS;
            } else {
                $this->error('Task import failed: ' . ($result['message'] ?? 'Unknown error'));
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

        // Show task type distribution
        if (!empty($result['task_type_distribution'])) {
            $this->info("\n--- Task Type Distribution ---");
            foreach ($result['task_type_distribution'] as $type => $count) {
                $this->line("{$type}: {$count}");
            }
        }

        // Show date range
        if (!empty($result['date_range'])) {
            $this->info("\n--- Date Range ---");
            $this->line("Earliest task: {$result['date_range']['earliest']}");
            $this->line("Latest task: {$result['date_range']['latest']}");
        }

        if (!empty($result['sample_data'])) {
            $this->info("\n--- Sample Record (first record to be imported) ---");
            foreach ($result['sample_data'] as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value, JSON_PRETTY_PRINT);
                }
                if (strlen($value) > 100) {
                    $value = substr($value, 0, 100) . '...';
                }
                $this->line("{$key}: {$value}");
            }
        }

        if (!empty($result['orphaned_tasks'])) {
            $this->warn("\n--- Orphaned Tasks (no matching client found) ---");
            $this->warn("Count: {$result['orphaned_tasks']}");
            $this->warn("These tasks will be skipped unless clients are imported first.");
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
        $this->info("Skipped (no matching client): {$result['skipped']}");
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
            $this->info("Total tasks in Admin CRM: {$stats['total_tasks']}");
            $this->info("Total conversations in SafariChat: {$stats['total_safarichat_conversations']}");
            $this->info("Tasks imported from Admin: {$stats['imported_tasks']}");
        }

        // Show recommendations
        $this->info("\n=== RECOMMENDATIONS ===");
        if ($result['skipped'] > 0) {
            $this->warn("• {$result['skipped']} tasks were skipped due to missing clients");
            $this->warn("• Run 'php artisan admin:import-clients' first to import missing clients");
        }
        
        if ($result['imported'] > 0) {
            $this->info("• {$result['imported']} task conversations have been imported");
            $this->info("• Check SafariChat conversations to see the imported interaction history");
        }
    }
}