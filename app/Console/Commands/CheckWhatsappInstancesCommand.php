<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\WhatsappInstance;
use App\Services\UnifiedNotificationService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class CheckWhatsappInstancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:check-instances';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check WhatsApp instance connection status every 15 minutes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting WhatsApp instance connection check...');
        
        try {
            $unifiedService = app(UnifiedNotificationService::class);
            
            // Get all active WhatsApp instances (excluding system_default and non-numeric IDs)
            $instances = WhatsappInstance::whereNotNull('instance_id')
                ->where('is_system_default', false)
                ->get()
                ->filter(function ($instance) {
                    // Only check instances with numeric instance_id
                    return is_numeric($instance->instance_id);
                });
            
            if ($instances->isEmpty()) {
                $this->info('No WhatsApp instances found to check.');
                return 0;
            }
            
            $this->info("Found {$instances->count()} instances to check.");
            
            $checkedCount = 0;
            $connectedCount = 0;
            $disconnectedCount = 0;
            $errorCount = 0;
            
            foreach ($instances as $instance) {
                try {
                    $this->line("Checking instance {$instance->id} (Session: {$instance->instance_id})...");
                    
                    // Get session status from WaSender API
                    $statusResult = $unifiedService->getSessionStatus($instance->instance_id);
                    
                    if (isset($statusResult['success']) && $statusResult['success']) {
                        $apiStatus = $statusResult['data']['status'] ?? $statusResult['status'] ?? 'unknown';
                        $previousStatus = $instance->connect_status;
                        
                        // Map API status to database-allowed values
                        // Allowed values: disconnected, connecting, ready, error
                        $mappedStatus = $this->mapApiStatusToDbStatus($apiStatus);
                        
                        // Determine connection status
                        $isConnected = in_array($mappedStatus, ['ready', 'connecting']);
                        
                        // Update instance in database
                        $updateData = [
                            'connect_status' => $mappedStatus,
                            'last_active_at' => now(),
                        ];
                        
                        if ($isConnected) {
                            $updateData['status'] = 'connected';
                            $connectedCount++;
                            $this->info("  ✓ Instance {$instance->id} is CONNECTED");
                            
                            // If it was previously disconnected, log the reconnection
                            if (in_array(strtolower($previousStatus), ['disconnected', 'error'])) {
                                Log::info('WhatsApp instance reconnected', [
                                    'instance_id' => $instance->id,
                                    'user_id' => $instance->user_id,
                                    'previous_status' => $previousStatus,
                                    'new_status' => $mappedStatus,
                                    'api_status' => $apiStatus
                                ]);
                            }
                        } else {
                            $updateData['status'] = 'disconnected';
                            $updateData['disconnected_at'] = now();
                            $disconnectedCount++;
                            $this->warn("  ✗ Instance {$instance->id} is DISCONNECTED");
                            
                            // Log disconnection if status changed
                            if (!in_array(strtolower($previousStatus), ['disconnected', 'error'])) {
                                Log::warning('WhatsApp instance disconnected', [
                                    'instance_id' => $instance->id,
                                    'user_id' => $instance->user_id,
                                    'previous_status' => $previousStatus,
                                    'new_status' => $apiStatus
                                ]);
                                
                                // You can trigger notification to user here if needed
                                $this->notifyUserAboutDisconnection($instance);
                            }
                        }
                        
                        $instance->update($updateData);
                        $checkedCount++;
                        
                        // Clear the user's cache so the UI updates immediately
                        Cache::forget('whatsapp_disconnected_' . $instance->user_id);
                        
                    } else {
                        $errorMessage = $statusResult['error'] ?? $statusResult['message'] ?? 'Unknown error';
                        $this->error("  ✗ Failed to check instance {$instance->id}: {$errorMessage}");
                        
                        // Mark instance as disconnected/error when API fails
                        $previousStatus = $instance->connect_status;
                        $errorStatus = (stripos($errorMessage, 'not found') !== false) ? 'disconnected' : 'error';
                        
                        $instance->update([
                            'connect_status' => $errorStatus,
                            'status' => 'disconnected',
                            'disconnected_at' => now(),
                        ]);
                        
                        // Clear cache to update UI
                        Cache::forget('whatsapp_disconnected_' . $instance->user_id);
                        
                        Log::error('Failed to check WhatsApp instance status', [
                            'instance_id' => $instance->id,
                            'user_id' => $instance->user_id,
                            'error' => $errorMessage,
                            'previous_status' => $previousStatus,
                            'new_status' => $errorStatus
                        ]);
                        
                        // Notify user if status changed to disconnected
                        if (!in_array(strtolower($previousStatus), ['disconnected', 'error'])) {
                            $this->notifyUserAboutDisconnection($instance);
                        }
                        
                        $errorCount++;
                        $disconnectedCount++;
                    }
                    
                } catch (Exception $e) {
                    $this->error("  ✗ Error checking instance {$instance->id}: " . $e->getMessage());
                    
                    // Mark instance as error when exception occurs
                    $previousStatus = $instance->connect_status;
                    
                    $instance->update([
                        'connect_status' => 'error',
                        'status' => 'disconnected',
                        'disconnected_at' => now(),
                    ]);
                    
                    // Clear cache to update UI
                    Cache::forget('whatsapp_disconnected_' . $instance->user_id);
                    
                    Log::error('Exception while checking WhatsApp instance', [
                        'instance_id' => $instance->id,
                        'user_id' => $instance->user_id,
                        'error' => $e->getMessage(),
                        'previous_status' => $previousStatus,
                        'trace' => $e->getTraceAsString()
                    ]);
                    
                    // Notify user if status changed
                    if (!in_array(strtolower($previousStatus), ['disconnected', 'error'])) {
                        $this->notifyUserAboutDisconnection($instance);
                    }
                    
                    $errorCount++;
                    $disconnectedCount++;
                }
            }
            
            // Summary
            $this->newLine();
            $this->info("=== Check Summary ===");
            $this->info("Total instances: {$instances->count()}");
            $this->info("Successfully checked: {$checkedCount}");
            $this->info("Connected: {$connectedCount}");
            $this->warn("Disconnected: {$disconnectedCount}");
            
            if ($errorCount > 0) {
                $this->error("Errors: {$errorCount}");
            }
            
            Log::info('WhatsApp instance check completed', [
                'total' => $instances->count(),
                'checked' => $checkedCount,
                'connected' => $connectedCount,
                'disconnected' => $disconnectedCount,
                'errors' => $errorCount
            ]);
            
            return 0;
            
        } catch (Exception $e) {
            $this->error('Fatal error during WhatsApp instance check: ' . $e->getMessage());
            
            Log::error('Fatal error in WhatsApp instance check command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
    
    /**
     * Map API status to database-allowed values
     * 
     * Database allows: disconnected, connecting, ready, error
     * 
     * @param string $apiStatus
     * @return string
     */
    private function mapApiStatusToDbStatus(string $apiStatus): string
    {
        // Normalize to lowercase for comparison
        $status = strtolower($apiStatus);
        
        // Map API statuses to database-allowed values
        return match($status) {
            'connected', 'ready', 'open' => 'ready',
            'connecting', 'initializing', 'starting' => 'connecting',
            'disconnected', 'closed', 'logged_out', 'offline' => 'disconnected',
            'failed', 'error', 'timeout' => 'error',
            default => 'disconnected' // Safe default
        };
    }
    
    /**
     * Notify user about WhatsApp disconnection
     * 
     * @param WhatsappInstance $instance
     * @return void
     */
    private function notifyUserAboutDisconnection($instance)
    {
        try {
            // You can implement notification logic here
            // For example, send an email, SMS, or in-app notification to the user
            // This is a placeholder for future implementation
            
            Log::info('User notification triggered for disconnected WhatsApp instance', [
                'instance_id' => $instance->id,
                'user_id' => $instance->user_id,
                'phone_number' => $instance->phone_number
            ]);
            
            // Example: Send notification via notification service
            // $notificationService = app(\App\Services\AccountNotificationService::class);
            // $notificationService->notifyWhatsAppDisconnected($instance->user, $instance);
            
        } catch (Exception $e) {
            Log::error('Failed to notify user about WhatsApp disconnection', [
                'instance_id' => $instance->id,
                'user_id' => $instance->user_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
