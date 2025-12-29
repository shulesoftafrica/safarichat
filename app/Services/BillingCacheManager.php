<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Billing Cache Manager - Optimized Cache Control System
 * Handles cache refresh triggers and background updates
 */
class BillingCacheManager
{
    const CACHE_PREFIX = 'billing_status_';
    const EVENT_PREFIX = 'billing_event_';
    
    /**
     * Check if cache should be refreshed
     */
    public static function shouldRefresh($customerId)
    {
        $status = self::getCache($customerId);
        
        // Time-based refresh
        if (!self::isCacheValid($status)) {
            Log::info("Cache refresh needed for customer {$customerId}: time-based expiry");
            return true;
        }
        
        // Event-based refresh triggers
        $refreshTriggers = [
            'payment_received',
            'subscription_expired',
            'plan_changed',
            'credits_low',
            'limit_reached',
            'manual_refresh'
        ];
        
        foreach ($refreshTriggers as $trigger) {
            if (self::hasEvent($customerId, $trigger)) {
                Log::info("Cache refresh needed for customer {$customerId}: event trigger {$trigger}");
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Refresh customer status from API
     */
    public static function refreshStatus($customerId)
    {
        try {
            Log::info("Refreshing billing status for customer {$customerId}");
            
            $newStatus = BillingService::loadCompleteStatus($customerId);
            
            if ($newStatus) {
                self::setCache($customerId, $newStatus);
                self::clearEvents($customerId); // Clear refresh triggers
                
                Log::info("Billing status refreshed successfully for customer {$customerId}");
                return $newStatus;
            }
            
            throw new \Exception('Failed to load status from API');
            
        } catch (\Exception $e) {
            Log::warning("Billing status refresh failed for customer {$customerId}, using cached data: " . $e->getMessage());
            return self::getCache($customerId); // Fallback to cache
        }
    }
    
    /**
     * Background refresh (non-blocking)
     */
    public static function backgroundRefresh($customerId)
    {
        // Use Laravel's queue system if available, otherwise async
        dispatch(function () use ($customerId) {
            try {
                self::refreshStatus($customerId);
            } catch (\Exception $e) {
                Log::error("Background billing refresh failed for customer {$customerId}: " . $e->getMessage());
            }
        })->onQueue('billing');
    }
    
    /**
     * Force refresh (emergency use)
     */
    public static function forceRefresh($customerId)
    {
        self::clearCache($customerId);
        self::clearEvents($customerId);
        
        Log::warning("Force refresh triggered for customer {$customerId}");
        
        return self::refreshStatus($customerId);
    }
    
    /**
     * Get cached billing status
     */
    public static function getCache($customerId)
    {
        $cacheKey = self::CACHE_PREFIX . $customerId;
        return Cache::get($cacheKey);
    }
    
    /**
     * Set cached billing status
     */
    public static function setCache($customerId, $status, $duration = null)
    {
        $cacheKey = self::CACHE_PREFIX . $customerId;
        $cacheDuration = $duration ?? BillingService::CACHE_DURATION;
        
        Cache::put($cacheKey, $status, $cacheDuration);
    }
    
    /**
     * Update cached status (partial update)
     */
    public static function updateCache($customerId, $updates)
    {
        $status = self::getCache($customerId);
        
        if ($status) {
            // Merge updates into existing status
            $status = array_merge_recursive($status, $updates);
            self::setCache($customerId, $status);
            
            Log::debug("Cache updated for customer {$customerId}", ['updates' => array_keys($updates)]);
        }
    }
    
    /**
     * Clear cached billing status
     */
    public static function clearCache($customerId)
    {
        $cacheKey = self::CACHE_PREFIX . $customerId;
        Cache::forget($cacheKey);
        
        Log::debug("Cache cleared for customer {$customerId}");
    }
    
    /**
     * Check if cache is valid (not expired)
     */
    private static function isCacheValid($status)
    {
        if (!$status || !is_array($status)) {
            return false;
        }
        
        if (!isset($status['expires_at'])) {
            return false;
        }
        
        $expiresAt = Carbon::parse($status['expires_at']);
        return $expiresAt->isFuture();
    }
    
    /**
     * Trigger cache refresh event
     */
    public static function triggerEvent($customerId, $event, $data = [])
    {
        $eventKey = self::EVENT_PREFIX . $customerId . '_' . $event;
        
        $eventData = [
            'event' => $event,
            'data' => $data,
            'timestamp' => now()->toISOString()
        ];
        
        Cache::put($eventKey, $eventData, 3600); // Store for 1 hour
        
        Log::info("Billing event triggered for customer {$customerId}: {$event}", $data);
    }
    
    /**
     * Check if specific event exists
     */
    private static function hasEvent($customerId, $event)
    {
        $eventKey = self::EVENT_PREFIX . $customerId . '_' . $event;
        return Cache::has($eventKey);
    }
    
    /**
     * Clear all events for customer
     */
    private static function clearEvents($customerId)
    {
        $events = [
            'payment_received',
            'subscription_expired',
            'plan_changed',
            'credits_low',
            'limit_reached',
            'manual_refresh'
        ];
        
        foreach ($events as $event) {
            $eventKey = self::EVENT_PREFIX . $customerId . '_' . $event;
            Cache::forget($eventKey);
        }
    }
    
    /**
     * Get cache statistics
     */
    public static function getCacheStats($customerId)
    {
        $status = self::getCache($customerId);
        
        if (!$status) {
            return [
                'cached' => false,
                'status' => 'not_cached'
            ];
        }
        
        $expiresAt = Carbon::parse($status['expires_at']);
        $loadedAt = Carbon::parse($status['loaded_at']);
        
        return [
            'cached' => true,
            'loaded_at' => $loadedAt->toISOString(),
            'expires_at' => $expiresAt->toISOString(),
            'age_minutes' => $loadedAt->diffInMinutes(now()),
            'expires_in_minutes' => now()->diffInMinutes($expiresAt),
            'is_fallback' => $status['is_fallback'] ?? false,
            'plan' => $status['subscription']['plan'] ?? 'unknown',
            'status' => $expiresAt->isFuture() ? 'valid' : 'expired'
        ];
    }
    
    /**
     * Periodic cleanup of expired cache entries
     */
    public static function cleanup()
    {
        // This would be called by a scheduled command
        // Laravel's cache system handles TTL automatically, but we can log cleanup
        Log::info("Billing cache cleanup completed");
    }
    
    /**
     * Warm up cache for multiple customers (batch operation)
     */
    public static function warmupCache($customerIds)
    {
        foreach ($customerIds as $customerId) {
            if (!self::getCache($customerId)) {
                try {
                    BillingService::loadCompleteStatus($customerId);
                } catch (\Exception $e) {
                    Log::warning("Cache warmup failed for customer {$customerId}: " . $e->getMessage());
                }
            }
        }
        
        Log::info("Cache warmup completed for " . count($customerIds) . " customers");
    }
}