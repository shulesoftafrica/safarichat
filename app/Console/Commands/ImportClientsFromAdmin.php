<?php

namespace App\Console\Commands;

use App\Services\AdminCrmIntegrationService;
use App\Services\DataMappingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportClientsFromAdmin extends Command
{
    protected $signature = 'admin:import-clients 
                            {--limit=50 : Number of records to import per batch}
                            {--offset=0 : Starting offset for import}
                            {--status= : Filter by client status (0-6)}
                            {--with-tasks : Also import task history for clients}
                            {--dry-run : Preview import without saving}
                            {--force : Force import even if validations fail}';

    protected $description = 'Import clients from Admin CRM system as business contacts';

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
        $this->info('Starting Admin CRM Client Import...');

        // Test connection first
        if (!$this->integrationService->testConnection()) {
            $this->error('Cannot connect to Admin CRM database');
            return Command::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $offset = (int) $this->option('offset');
        $status = $this->option('status');
        $withTasks = $this->option('with-tasks');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $filters = [];
        if ($status !== null) {
            $filters['status'] = (int) $status;
            $this->info("Filtering by status: {$status}");
        }

        $this->info("Import parameters: Limit={$limit}, Offset={$offset}, Dry Run=" . ($dryRun ? 'Yes' : 'No'));
        
        if ($withTasks) {
            $this->info("Will also import task history for each client");
        }

        try {
            DB::beginTransaction();

            $result = $this->integrationService->importClients($limit, $offset, $filters, $dryRun);

            if ($dryRun) {
                $this->displayDryRunResults($result);
                DB::rollBack();
                return Command::SUCCESS;
            }

            if ($result['success']) {
                $this->displaySuccessResults($result);

                // Import tasks if requested
                if ($withTasks && $result['imported'] > 0) {
                    $this->info("\nImporting task history for clients...");
                    $taskResult = $this->importTasksForClients($result['imported_clients']);
                    $this->displayTaskImportResults($taskResult);
                }
                
                if (!$force && $result['errors'] > 0) {
                    $this->warn('Some errors occurred. Use --force to proceed with partial import.');
                    if (!$this->confirm('Continue with import despite errors?')) {
                        DB::rollBack();
                        return Command::FAILURE;
                    }
                }

                DB::commit();
                $this->info('Client import completed successfully!');
                return Command::SUCCESS;
            } else {
                $this->error('Client import failed: ' . ($result['message'] ?? 'Unknown error'));
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

    protected function importTasksForClients(array $clientIds): array
    {
        $totalTasks = 0;
        $importedTasks = 0;
        $taskErrors = 0;

        foreach ($clientIds as $clientId) {
            try {
                $taskResult = $this->integrationService->importTasks(100, 0, ['client_id' => $clientId]);
                
                if ($taskResult['success']) {
                    $totalTasks += $taskResult['processed'];
                    $importedTasks += $taskResult['imported'];
                    $taskErrors += $taskResult['errors'];
                }
            } catch (\Exception $e) {
                $this->warn("Error importing tasks for client {$clientId}: " . $e->getMessage());
                $taskErrors++;
            }
        }

        return [
            'processed' => $totalTasks,
            'imported' => $importedTasks,
            'errors' => $taskErrors
        ];
    }

    protected function displayDryRunResults(array $result): void
    {
        $this->info('=== DRY RUN RESULTS ===');
        $this->info("Records processed: {$result['processed']}");
        $this->info("Would be imported: {$result['imported']}");
        $this->info("Would be skipped: {$result['skipped']}");
        $this->info("Validation errors: {$result['errors']}");

        // Show status distribution
        if (!empty($result['status_distribution'])) {
            $this->info("\n--- Status Distribution ---");
            foreach ($result['status_distribution'] as $status => $count) {
                $statusName = $this->getStatusName($status);
                $this->line("{$statusName}: {$count}");
            }
        }

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
            $this->info("Total clients in Admin CRM: {$stats['total_clients']}");
            $this->info("Total business contacts in SafariChat: {$stats['safarichat_imported']['contacts']}");
            $this->info("Clients imported from Admin: {$stats['admin_totals']['clients']}");
        }
    }

    protected function displayTaskImportResults(array $result): void
    {
        $this->info('=== TASK IMPORT RESULTS ===');
        $this->info("Total tasks processed: {$result['processed']}");
        $this->info("Tasks imported: {$result['imported']}");
        $this->info("Task import errors: {$result['errors']}");
    }

    protected function getStatusName(int $status): string
    {
        $statusNames = [
            0 => 'Lead',
            1 => 'Prospect', 
            2 => 'Customer',
            4 => 'Churned',
            5 => 'Qualified Lead',
            6 => 'Low Usage Client'
        ];

        return $statusNames[$status] ?? "Unknown ({$status})";
    }
}