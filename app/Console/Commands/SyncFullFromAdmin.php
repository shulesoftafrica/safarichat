<?php

namespace App\Console\Commands;

use App\Services\AdminCrmIntegrationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncFullFromAdmin extends Command
{
    protected $signature = 'admin:sync-full 
                            {--batch-size=50 : Number of records to process in each batch}
                            {--dry-run : Preview sync without making changes}
                            {--staff-only : Import staff users only}
                            {--clients-only : Import clients only}
                            {--tasks-only : Import tasks only}
                            {--force : Force sync even if validations fail}';

    protected $description = 'Full synchronization from Admin CRM system (staff, clients, and tasks)';

    protected AdminCrmIntegrationService $integrationService;

    public function __construct(AdminCrmIntegrationService $integrationService)
    {
        parent::__construct();
        $this->integrationService = $integrationService;
    }

    public function handle(): int
    {
        $this->info('Starting Full Admin CRM Synchronization...');

        // Test connection first
        if (!$this->integrationService->testConnection()) {
            $this->error('Cannot connect to Admin CRM database');
            return Command::FAILURE;
        }

        $batchSize = (int) $this->option('batch-size');
        $dryRun = $this->option('dry-run');
        $staffOnly = $this->option('staff-only');
        $clientsOnly = $this->option('clients-only');
        $tasksOnly = $this->option('tasks-only');
        $force = $this->option('force');

        $this->info("Sync parameters: Batch Size={$batchSize}, Dry Run=" . ($dryRun ? 'Yes' : 'No'));

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No actual changes will be made');
        }

        try {
            // Don't use a single large transaction - let each section handle its own transactions
            // to prevent cascade failures when one section has errors
            
            $overallResult = $this->integrationService->syncFull([
                'batch_size' => $batchSize,
                'dry_run' => $dryRun,
                'staff_only' => $staffOnly,
                'clients_only' => $clientsOnly,
                'tasks_only' => $tasksOnly,
                'force' => $force
            ]);

            if ($dryRun) {
                $this->displayDryRunSummary($overallResult);
                return Command::SUCCESS;
            }

            if ($overallResult['success']) {
                $this->displaySuccessSummary($overallResult);
                
                if (!$force && $overallResult['total_errors'] > 0) {
                    $this->warn('Some errors occurred during sync. Use --force to proceed despite errors.');
                    if (!$this->confirm('Continue with sync despite errors?')) {
                        return Command::FAILURE;
                    }
                }

                $this->info('Full synchronization completed successfully!');
                
                // Display post-sync statistics
                $this->displayPostSyncStats();
                
                return Command::SUCCESS;
            } else {
                $this->error('Full sync failed: ' . ($overallResult['message'] ?? 'Unknown error'));
                
                // Display detailed error information
                $this->displayErrorDetails($overallResult);
                
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('Critical sync error: ' . $e->getMessage());
            Log::error('Admin CRM sync critical error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    protected function displayDryRunSummary(array $result): void
    {
        $this->info('=== DRY RUN SUMMARY ===');

        if (isset($result['staff'])) {
            $this->info("\n--- STAFF IMPORT ---");
            $this->info("Would process: {$result['staff']['processed']}");
            $this->info("Would import: {$result['staff']['imported']}");
            $this->info("Would skip: {$result['staff']['skipped']}");
            $this->info("Validation errors: {$result['staff']['errors']}");
        }

        if (isset($result['clients'])) {
            $this->info("\n--- CLIENT IMPORT ---");
            $this->info("Would process: {$result['clients']['processed']}");
            $this->info("Would import: {$result['clients']['imported']}");
            $this->info("Would skip: {$result['clients']['skipped']}");
            $this->info("Validation errors: {$result['clients']['errors']}");
        }

        if (isset($result['tasks'])) {
            $this->info("\n--- TASK IMPORT ---");
            $this->info("Would process: {$result['tasks']['processed']}");
            $this->info("Would import: {$result['tasks']['imported']}");
            $this->info("Would skip: {$result['tasks']['skipped']}");
            $this->info("Validation errors: {$result['tasks']['errors']}");
        }

        $this->info("\n=== OVERALL SUMMARY ===");
        $this->info("Total records would be processed: {$result['total_processed']}");
        $this->info("Total records would be imported: {$result['total_imported']}");
        $this->info("Total records would be skipped: {$result['total_skipped']}");
        $this->info("Total validation errors: {$result['total_errors']}");

        $this->info("\n=== EXECUTION TIME ESTIMATE ===");
        $this->info("Estimated sync time: {$result['estimated_time']} minutes");
        $this->info("Recommended batch size: {$result['recommended_batch_size']}");
        
        // Display error details
        $allErrors = [];
        foreach (['staff', 'clients', 'tasks'] as $type) {
            if (isset($result[$type]['error_details'])) {
                $allErrors = array_merge($allErrors, $result[$type]['error_details']);
            }
        }
        
        if (!empty($allErrors)) {
            $this->warn("\n=== ERROR DETAILS ===");
            foreach (array_slice($allErrors, 0, 10) as $error) {
                $this->line("• {$error}");
            }
            if (count($allErrors) > 10) {
                $this->line("... and " . (count($allErrors) - 10) . " more errors");
            }
        }
        
        // Display sample data
        foreach (['staff', 'clients', 'tasks'] as $type) {
            if (isset($result[$type]['sample_data']) && !empty($result[$type]['sample_data'])) {
                $this->info("\n--- SAMPLE " . strtoupper($type) . " DATA ---");
                $sample = $result[$type]['sample_data'];
                foreach (array_slice($sample, 0, 8) as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value, JSON_PRETTY_PRINT);
                    }
                    if (strlen($value) > 100) {
                        $value = substr($value, 0, 100) . '...';
                    }
                    $this->line("{$key}: {$value}");
                }
                break; // Only show first available sample
            }
        }
    }
    
    /**
     * Display detailed error information
     */
    protected function displayErrorDetails(array $result): void
    {
        // Display summary first
        if (isset($result['staff'])) {
            $this->line("\n--- STAFF ERRORS: {$result['staff']['errors']} ---");
        }
        if (isset($result['clients'])) {
            $this->line("--- CLIENT ERRORS: {$result['clients']['errors']} ---");
        }
        if (isset($result['tasks'])) {
            $this->line("--- TASK ERRORS: {$result['tasks']['errors']} ---");
        }
        
        // Display all error details
        if (isset($result['all_error_details']) && !empty($result['all_error_details'])) {
            $this->error("\n=== DETAILED ERROR LOG ===");
            foreach ($result['all_error_details'] as $index => $error) {
                $this->line(($index + 1) . ". {$error}");
            }
        }
        
        // Also log to Laravel log for debugging
        Log::error('Sync command failed with detailed errors', [
            'total_errors' => $result['total_errors'] ?? 0,
            'error_details' => $result['all_error_details'] ?? [],
            'result_summary' => [
                'processed' => $result['total_processed'] ?? 0,
                'imported' => $result['total_imported'] ?? 0,
                'skipped' => $result['total_skipped'] ?? 0
            ]
        ]);
    }

    protected function displaySuccessSummary(array $result): void
    {
        $this->info('=== SYNC SUMMARY ===');

        if (isset($result['staff'])) {
            $this->info("\n--- STAFF IMPORT RESULTS ---");
            $this->info("Processed: {$result['staff']['processed']}");
            $this->info("Imported: {$result['staff']['imported']}");
            $this->info("Skipped: {$result['staff']['skipped']}");
            $this->info("Errors: {$result['staff']['errors']}");
            if (isset($result['staff']['time_taken'])) {
                $this->info("Time taken: {$result['staff']['time_taken']} seconds");
            }
        }

        if (isset($result['clients'])) {
            $this->info("\n--- CLIENT IMPORT RESULTS ---");
            $this->info("Processed: {$result['clients']['processed']}");
            $this->info("Imported: {$result['clients']['imported']}");
            $this->info("Skipped: {$result['clients']['skipped']}");
            $this->info("Errors: {$result['clients']['errors']}");
            if (isset($result['clients']['time_taken'])) {
                $this->info("Time taken: {$result['clients']['time_taken']} seconds");
            }
        }

        if (isset($result['tasks'])) {
            $this->info("\n--- TASK IMPORT RESULTS ---");
            $this->info("Processed: {$result['tasks']['processed']}");
            $this->info("Imported: {$result['tasks']['imported']}");
            $this->info("Skipped: {$result['tasks']['skipped']}");
            $this->info("Errors: {$result['tasks']['errors']}");
            if (isset($result['tasks']['time_taken'])) {
                $this->info("Time taken: {$result['tasks']['time_taken']} seconds");
            }
        }

        $this->info("\n=== OVERALL RESULTS ===");
        $this->info("Total processed: {$result['total_processed']}");
        $this->info("Total imported: {$result['total_imported']}");
        $this->info("Total skipped: {$result['total_skipped']}");
        $this->info("Total errors: {$result['total_errors']}");
        $this->info("Total sync time: {$result['total_time']} seconds");

        if ($result['total_errors'] > 0) {
            $this->warn("\n--- ERROR BREAKDOWN ---");
            if (!empty($result['error_summary'])) {
                foreach ($result['error_summary'] as $type => $count) {
                    $this->line("{$type}: {$count}");
                }
            }
        }
    }

    protected function displayPostSyncStats(): void
    {
        $this->info("\n=== POST-SYNC STATISTICS ===");
        
        try {
            $stats = $this->integrationService->getImportStats();
            
            $this->info("SafariChat Database Summary:");
            $this->info("• Total Users: " . ($stats['safarichat_imported']['staff'] ?? 0));
            $this->info("• Business Contacts: " . ($stats['safarichat_imported']['contacts'] ?? 0));
            $this->info("• Conversations: " . ($stats['safarichat_imported']['conversations'] ?? 0));
            
            $this->info("\nAdmin CRM Source Data:");
            $this->info("• Admin Users: " . ($stats['admin_totals']['users'] ?? 0));
            $this->info("• Admin Clients: " . ($stats['admin_totals']['clients'] ?? 0));
            $this->info("• Admin Tasks: " . ($stats['admin_totals']['tasks'] ?? 0));
            $this->info("• Total Business Contacts: {$stats['safarichat_imported']['contacts']}");
            $this->info("• Contacts imported from Admin: {$stats['admin_totals']['clients']}");
            $this->info("• Total Conversations: {$stats['safarichat_imported']['conversations']}");
            $this->info("• Conversations from Admin tasks: {$stats['admin_totals']['tasks']}");
            
            $this->info("\nAdmin CRM Database Summary:");
            $this->info("• Total Staff: {$stats['admin_totals']['users']}");
            $this->info("• Total Clients: {$stats['admin_totals']['clients']}");
            $this->info("• Total Tasks: {$stats['admin_totals']['tasks']}");
            
            // Data integrity check
            if (method_exists($this->integrationService, 'verifyImport')) {
                $this->info("\nRunning data integrity verification...");
                $verification = $this->integrationService->verifyImport();
                
                if (isset($verification['success']) && $verification['success']) {
                    $this->info("✅ Data integrity check passed");
                    $this->info("• All staff mappings verified: {$verification['staff_mappings']}");
                    $this->info("• All client mappings verified: {$verification['client_mappings']}");
                    $this->info("• All task relationships verified: {$verification['task_relationships']}");
                } else {
                    $this->warn("⚠️  Data integrity issues found:");
                    foreach ($verification['issues'] as $issue) {
                        $this->warn("• {$issue}");
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->warn("Could not retrieve post-sync statistics: " . $e->getMessage());
        }

        $this->info("\n=== NEXT STEPS ===");
        $this->info("1. Review imported data in SafariChat admin panel");
        $this->info("2. Verify client contact information and lead stages");
        $this->info("3. Check conversation history for completeness");
        $this->info("4. Set up regular sync schedule if needed:");
        $this->info("   • Daily: php artisan admin:sync-full --batch-size=100");
        $this->info("   • Weekly full sync with validation: php artisan admin:sync-full --force");
    }
}