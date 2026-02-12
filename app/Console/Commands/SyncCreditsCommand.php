<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LocalCreditManager;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SyncCreditsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'billing:sync-credits {--customer-id= : Sync credits for specific customer}';

    /**
     * The console command description.
     */
    protected $description = 'Sync pending credit deductions with billing system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting credit synchronization...');
        
        $customerId = $this->option('customer-id');
        
        if ($customerId) {
            $this->syncCustomerCredits($customerId);
        } else {
            $this->syncAllCustomerCredits();
        }
        
        $this->info('✅ Credit synchronization completed');
    }
    
    /**
     * Sync credits for specific customer
     */
    private function syncCustomerCredits($customerId)
    {
        $this->info("Syncing credits for customer {$customerId}...");
        
        try {
            $success = LocalCreditManager::syncCredits($customerId);
            
            if ($success) {
                $this->info("✅ Credits synced successfully for customer {$customerId}");
            } else {
                $this->warn("⚠️  Credit sync failed for customer {$customerId}");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Credit sync error for customer {$customerId}: " . $e->getMessage());
            Log::error("Credit sync command failed for customer {$customerId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Sync credits for all customers with pending deductions
     */
    private function syncAllCustomerCredits()
    {
        $this->info('Finding customers with pending credit deductions...');
        
        // Get all users who have billing accounts (new billing system)
        $customers = User::whereHas('billingAccount')
                        ->get();
        
        $syncCount = 0;
        $errorCount = 0;
        
        $progressBar = $this->output->createProgressBar($customers->count());
        $progressBar->start();
        
        foreach ($customers as $customer) {
            try {
                $pendingDeductions = LocalCreditManager::getPendingDeductions($customer->id);
                
                if (!empty($pendingDeductions)) {
                    $success = LocalCreditManager::syncCredits($customer->id);
                    
                    if ($success) {
                        $syncCount++;
                        Log::info("Credits synced for customer {$customer->id}", [
                            'operations_synced' => count($pendingDeductions)
                        ]);
                    } else {
                        $errorCount++;
                        Log::warning("Credit sync failed for customer {$customer->id}");
                    }
                }
                
            } catch (\Exception $e) {
                $errorCount++;
                Log::error("Credit sync error for customer {$customer->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->table(['Metric', 'Count'], [
            ['Customers Processed', $customers->count()],
            ['Successful Syncs', $syncCount],
            ['Failed Syncs', $errorCount],
        ]);
        
        if ($errorCount > 0) {
            $this->warn("⚠️  {$errorCount} customers had sync failures. Check logs for details.");
        }
    }
}
