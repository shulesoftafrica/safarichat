<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

/**
 * Local Credit Manager - Revenue Protected Credit Tracking
 * Handles AI credit reservations, deductions, and sync with safeguards
 */
class LocalCreditManager
{
    const RESERVATION_PREFIX = 'credit_reservation_';
    const PENDING_SYNC_PREFIX = 'pending_credits_';
    const RETRY_COUNT_PREFIX = 'credit_retry_';
    const MAX_RETRIES = 5;
    const HIGH_CREDIT_THRESHOLD = 100;
    
    /**
     * REVENUE PROTECTION: Credit reservation system
     */
    public static function reserveCredits($customerId, $amount, $description = 'AI operation')
    {
        $status = BillingService::getCachedStatus($customerId);
        
        // CRITICAL: Check if enough credits available
        $currentBalance = $status['limits']['ai_credits']['balance'] ?? $status['wallet']['ai_credits'] ?? 0;
        
        if ($currentBalance < $amount) {
            Log::warning("Credit reservation failed for customer {$customerId}: insufficient credits", [
                'requested' => $amount,
                'available' => $currentBalance
            ]);
            return ['success' => false, 'reason' => 'insufficient_credits', 'available' => $currentBalance];
        }
        
        // REVENUE PROTECTION: Atomic reservation with unique ID
        $reservationId = 'RSV_' . time() . '_' . uniqid();
        
        $reservation = [
            'id' => $reservationId,
            'customer_id' => $customerId,
            'reserved_amount' => $amount,
            'description' => $description,
            'created_at' => now()->toISOString(),
            'expires_at' => now()->addMinutes(10)->toISOString() // Reservations expire in 10 minutes
        ];
        
        // Store reservation
        Cache::put(self::RESERVATION_PREFIX . $reservationId, $reservation, 600); // 10 minutes
        
        // Temporarily reduce available credits in cache
        $status['limits']['ai_credits']['balance'] = $currentBalance - $amount;
        $status['limits']['ai_credits']['canUse'] = $status['limits']['ai_credits']['balance'] > 0;
        
        // Update cached status
        BillingService::clearCache($customerId);
        Cache::put('billing_status_' . $customerId, $status, BillingService::CACHE_DURATION);
        
        Log::info("Credits reserved for customer {$customerId}", [
            'reservation_id' => $reservationId,
            'amount' => $amount,
            'new_balance' => $currentBalance - $amount
        ]);
        
        return ['success' => true, 'reservation_id' => $reservationId, 'new_balance' => $currentBalance - $amount];
    }
    
    /**
     * REVENUE PROTECTION: Finalize credit deduction with actual amount
     */
    public static function finalizeCredits($customerId, $reservationId, $actualAmount)
    {
        $reservation = Cache::get(self::RESERVATION_PREFIX . $reservationId);
        
        if (!$reservation) {
            Log::warning("Attempted to finalize non-existent reservation {$reservationId}");
            return false;
        }
        
        $difference = $actualAmount - $reservation['reserved_amount'];
        
        if ($difference !== 0) {
            // Adjust credits based on actual vs reserved
            $status = BillingService::getCachedStatus($customerId);
            $currentBalance = $status['limits']['ai_credits']['balance'] ?? 0;
            
            // If actual amount is less than reserved, give credits back
            // If actual amount is more than reserved, deduct additional credits
            $status['limits']['ai_credits']['balance'] = $currentBalance - $difference;
            $status['limits']['ai_credits']['canUse'] = $status['limits']['ai_credits']['balance'] > 0;
            
            // Update cache
            BillingService::clearCache($customerId);
            Cache::put('billing_status_' . $customerId, $status, BillingService::CACHE_DURATION);
            
            Log::info("Credit adjustment made", [
                'customer_id' => $customerId,
                'reservation_id' => $reservationId,
                'reserved' => $reservation['reserved_amount'],
                'actual' => $actualAmount,
                'difference' => $difference,
                'new_balance' => $status['limits']['ai_credits']['balance']
            ]);
        }
        
        // REVENUE PROTECTION: Queue for server sync with actual amount
        self::queueCreditSync($customerId, $actualAmount, $reservation['description'], [
            'reserved' => $reservation['reserved_amount'],
            'actual' => $actualAmount,
            'reservation_id' => $reservationId
        ]);
        
        // Clear reservation
        Cache::forget(self::RESERVATION_PREFIX . $reservationId);
        
        return true;
    }
    
    /**
     * REVENUE PROTECTION: Release reservation on failure
     */
    public static function releaseReservation($customerId, $reservationId)
    {
        $reservation = Cache::get(self::RESERVATION_PREFIX . $reservationId);
        
        if (!$reservation) {
            return false;
        }
        
        // Restore reserved credits
        $status = BillingService::getCachedStatus($customerId);
        $currentBalance = $status['limits']['ai_credits']['balance'] ?? 0;
        
        $status['limits']['ai_credits']['balance'] = $currentBalance + $reservation['reserved_amount'];
        $status['limits']['ai_credits']['canUse'] = true;
        
        // Update cache
        BillingService::clearCache($customerId);
        Cache::put('billing_status_' . $customerId, $status, BillingService::CACHE_DURATION);
        
        // Clear reservation
        Cache::forget(self::RESERVATION_PREFIX . $reservationId);
        
        Log::info("Credit reservation released", [
            'customer_id' => $customerId,
            'reservation_id' => $reservationId,
            'amount_restored' => $reservation['reserved_amount'],
            'new_balance' => $status['limits']['ai_credits']['balance']
        ]);
        
        return true;
    }
    
    /**
     * Direct credit deduction (for non-AI operations)
     */
    public static function deductCredits($customerId, $amount, $description = 'Credit usage')
    {
        $status = BillingService::getCachedStatus($customerId);
        
        // Update local cache
        $currentBalance = $status['limits']['ai_credits']['balance'] ?? 0;
        $status['limits']['ai_credits']['balance'] = max(0, $currentBalance - $amount);
        $status['limits']['ai_credits']['canUse'] = $status['limits']['ai_credits']['balance'] > 0;
        
        // Queue for sync (don't wait)
        self::queueCreditSync($customerId, $amount, $description);
        
        // Update cache
        BillingService::clearCache($customerId);
        Cache::put('billing_status_' . $customerId, $status, BillingService::CACHE_DURATION);
        
        Log::info("Credits deducted for customer {$customerId}", [
            'amount' => $amount,
            'description' => $description,
            'new_balance' => $status['limits']['ai_credits']['balance']
        ]);
    }
    
    /**
     * Queue credit operation for batch sync
     */
    private static function queueCreditSync($customerId, $amount, $description, $metadata = [])
    {
        $pendingKey = self::PENDING_SYNC_PREFIX . $customerId;
        $pending = Cache::get($pendingKey, []);
        
        $pending[] = [
            'amount' => $amount,
            'description' => $description,
            'timestamp' => now()->toISOString(),
            'metadata' => $metadata
        ];
        
        // Keep pending deductions for 24 hours
        Cache::put($pendingKey, $pending, 86400);
    }
    
    /**
     * REVENUE PROTECTION: Batch sync with conflict resolution
     */
    public static function syncCredits($customerId)
    {
        $pendingKey = self::PENDING_SYNC_PREFIX . $customerId;
        $pendingDeductions = Cache::get($pendingKey, []);
        
        if (empty($pendingDeductions)) {
            return true;
        }
        
        $status = BillingService::getCachedStatus($customerId);
        $localBalance = $status['limits']['ai_credits']['balance'] ?? 0;
        
        try {
            $response = Http::timeout(30)->post(env('BILLING_API_URL', 'http://localhost/safarichat/api') . '/billing/sync-credits', [
                'customer_id' => $customerId,
                'deductions' => $pendingDeductions,
                'local_balance' => $localBalance
            ]);
            
            if ($response->successful()) {
                $result = $response->json();
                
                // REVENUE PROTECTION: Handle server-side corrections
                if (isset($result['balance_correction']) && $result['balance_correction'] != 0) {
                    $status['limits']['ai_credits']['balance'] = $result['corrected_balance'];
                    BillingService::clearCache($customerId);
                    Cache::put('billing_status_' . $customerId, $status, BillingService::CACHE_DURATION);
                    
                    Log::warning("Credit balance corrected for customer {$customerId}", [
                        'correction' => $result['balance_correction'],
                        'old_balance' => $localBalance,
                        'new_balance' => $result['corrected_balance']
                    ]);
                }
                
                // Clear pending queue only if successful
                Cache::forget($pendingKey);
                Cache::forget(self::RETRY_COUNT_PREFIX . $customerId);
                
                Log::info("Credit sync successful for customer {$customerId}", [
                    'synced_operations' => count($pendingDeductions)
                ]);
                
                return true;
            } else {
                throw new \Exception('HTTP error: ' . $response->status() . ' - ' . $response->body());
            }
            
        } catch (\Exception $e) {
            Log::error("Credit sync failed for customer {$customerId}: " . $e->getMessage());
            
            // REVENUE PROTECTION: Credits remain queued for retry
            self::incrementRetryCount($customerId);
            
            if (self::getRetryCount($customerId) >= self::MAX_RETRIES) {
                // EMERGENCY: Handle persistent sync failures
                self::handleSyncFailure($customerId);
            }
            
            return false;
        }
    }
    
    /**
     * Get pending credit deductions for a customer
     */
    public static function getPendingDeductions($customerId)
    {
        return Cache::get(self::PENDING_SYNC_PREFIX . $customerId, []);
    }
    
    /**
     * Clear pending deductions (use carefully)
     */
    public static function clearPendingDeductions($customerId)
    {
        Cache::forget(self::PENDING_SYNC_PREFIX . $customerId);
        Cache::forget(self::RETRY_COUNT_PREFIX . $customerId);
    }
    
    /**
     * Increment retry count for failed syncs
     */
    private static function incrementRetryCount($customerId)
    {
        $retryKey = self::RETRY_COUNT_PREFIX . $customerId;
        $count = Cache::get($retryKey, 0) + 1;
        Cache::put($retryKey, $count, 86400); // Store for 24 hours
        return $count;
    }
    
    /**
     * Get current retry count
     */
    public static function getRetryCount($customerId)
    {
        return Cache::get(self::RETRY_COUNT_PREFIX . $customerId, 0);
    }
    
    /**
     * REVENUE PROTECTION: Emergency fallback for persistent sync failures
     */
    private static function handleSyncFailure($customerId)
    {
        Log::critical("BILLING ALERT: Persistent credit sync failure for customer {$customerId}");
        
        // 1. Force refresh billing status
        BillingService::forceRefresh($customerId);
        
        // 2. Log for manual reconciliation
        self::logForManualReconciliation($customerId);
        
        // 3. Temporarily disable high-credit operations
        $status = BillingService::getCachedStatus($customerId);
        $status['emergency_mode'] = true;
        $status['emergency_reason'] = 'credit_sync_failure';
        $status['emergency_timestamp'] = now()->toISOString();
        
        BillingService::clearCache($customerId);
        Cache::put('billing_status_' . $customerId, $status, BillingService::CACHE_DURATION);
        
        // 4. Clear retry counter
        Cache::forget(self::RETRY_COUNT_PREFIX . $customerId);
        
        Log::info("Emergency mode activated for customer {$customerId} due to sync failures");
    }
    
    /**
     * Log failed sync for manual reconciliation
     */
    private static function logForManualReconciliation($customerId)
    {
        $pendingDeductions = self::getPendingDeductions($customerId);
        $status = BillingService::getCachedStatus($customerId);
        
        $reconciliationData = [
            'customer_id' => $customerId,
            'pending_deductions' => $pendingDeductions,
            'local_balance' => $status['limits']['ai_credits']['balance'] ?? 0,
            'retry_count' => self::getRetryCount($customerId),
            'timestamp' => now()->toISOString(),
            'plan' => $status['subscription']['plan'] ?? 'unknown'
        ];
        
        // Store in database for manual review
        DB::table('billing_reconciliation_log')->insert([
            'customer_id' => $customerId,
            'reconciliation_data' => json_encode($reconciliationData),
            'status' => 'pending_manual_review',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        Log::info("Credit sync failure logged for manual reconciliation", ['customer_id' => $customerId]);
    }
    
    /**
     * Clean up expired reservations (should run periodically)
     */
    public static function cleanupExpiredReservations()
    {
        // This would typically be called by a scheduled command
        Log::info("Cleaning up expired credit reservations");
        
        // Note: This is a simplified cleanup - in production you might want to scan all reservation keys
        // For now, reservations auto-expire from cache after 10 minutes
    }
}