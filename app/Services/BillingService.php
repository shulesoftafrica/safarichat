<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Business;

/**
 * Central Billing Service - SafariChat Revenue Protection System
 * Handles all billing operations with comprehensive revenue safeguards
 */
class BillingService
{
    const CACHE_PREFIX = 'billing_status_';
    const CACHE_DURATION = 7200; // 2 hours in seconds
    const FALLBACK_DURATION = 1800; // 30 minutes for emergency fallbacks
    const PRODUCT_CODE = 'safarichat'; // Default product code
    
    private static function getBillingApiBase()
    {
        // For local development, use localhost URL
        if (config('app.env') === 'local' || str_contains(request()->getHost() ?? '', 'localhost')) {
            return 'http://localhost/safarichat/api/billing';
        }
        
        // For production, use the configured app URL
        return config('app.url') . '/api/billing';
    }
    
    /**
     * Get cached billing status for customer
     * REVENUE PROTECTION: Always validates cache before returning
     */
    public static function getCachedStatus($customerId)
    {
        $cacheKey = self::CACHE_PREFIX . $customerId;
        $status = Cache::get($cacheKey);
        
        // REVENUE PROTECTION: Validate cache integrity
        if (!$status || !self::isCacheValid($status)) {
            Log::warning("Billing cache invalid for customer {$customerId}, using fallback");
            return self::getFallbackStatus($customerId);
        }
        
        return $status;
    }
    
    /**
     * Load complete customer billing status from API
     * This runs once at app boot and periodically for refresh
     */
    public static function loadCompleteStatus($customerId)
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'X-API-Key' => 'Dp77IDXdqtBuB2zLvYovj2QmAK',
                'Accept' => 'application/json'
            ])->get(self::getBillingApiBase() . "/customers/{$customerId}/complete-status");
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data['success']) {
                    $status = self::enrichStatusData($data['data'], $customerId);
                    self::cacheStatus($customerId, $status);
                    
                    Log::info("Billing status loaded for customer {$customerId}");
                    return $status;
                }
            }
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error("Failed to load billing status for {$customerId}: " . $e->getMessage());
            
            // REVENUE PROTECTION: Return conservative fallback
            return self::getFallbackStatus($customerId);
        }
    }
    
    /**
     * REVENUE PROTECTION: Conservative fallback when billing API fails
     */
    private static function getFallbackStatus($customerId)
    {
        // Get user's current plan from database
        $user = User::find($customerId);
        $business = $user ? $user->business : null;
        
        $plan = 'trial'; // Default to most restrictive
        if ($business && $business->subscription_plan) {
            $plan = $business->subscription_plan;
        }
        
        // Conservative limits - protect revenue while allowing minimal usage
        $fallbackLimits = [
            'trial' => [
                'contacts' => ['current' => 0, 'max' => 5, 'canAdd' => true],
                'products' => ['current' => 0, 'max' => 1, 'canAdd' => true],
                'whatsapp_channels' => ['current' => 0, 'max' => 1, 'canAdd' => true],
                'ai_credits' => ['balance' => 50, 'canUse' => true] // Very limited
            ],
            'starter' => [
                'contacts' => ['current' => 0, 'max' => 20, 'canAdd' => true], // Reduced from 50
                'products' => ['current' => 0, 'max' => 2, 'canAdd' => true], // Reduced from 5
                'whatsapp_channels' => ['current' => 0, 'max' => 1, 'canAdd' => true],
                'ai_credits' => ['balance' => 1000, 'canUse' => true] // Heavily reduced
            ],
            'pro' => [
                'contacts' => ['current' => 0, 'max' => 50, 'canAdd' => true], // Reduced from 150
                'products' => ['current' => 0, 'max' => 10, 'canAdd' => true], // Reduced from 50
                'whatsapp_channels' => ['current' => 0, 'max' => 2, 'canAdd' => true], // Reduced from 3
                'ai_credits' => ['balance' => 5000, 'canUse' => true] // Heavily reduced
            ],
            'premium' => [
                'contacts' => ['current' => 0, 'max' => 100, 'canAdd' => true], // Reduced from 400
                'products' => ['current' => 0, 'max' => 25, 'canAdd' => true], // Reduced from 200
                'whatsapp_channels' => ['current' => 0, 'max' => 3, 'canAdd' => true], // Reduced from 7
                'ai_credits' => ['balance' => 10000, 'canUse' => true] // Heavily reduced
            ]
        ];
        
        $status = [
            'customer_id' => $customerId,
            'loaded_at' => now()->toISOString(),
            'expires_at' => now()->addSeconds(self::FALLBACK_DURATION)->toISOString(),
            'is_fallback' => true,
            'subscription' => [
                'active' => true,
                'plan' => $plan,
                'trial' => $plan === 'trial',
                'expires' => now()->addDays(30)->toISOString() // Conservative assumption
            ],
            'limits' => $fallbackLimits[$plan] ?? $fallbackLimits['trial'],
            'permissions' => [
                'add_contact' => true,
                'add_product' => true,
                'send_message' => true,
                'use_ai' => true,
                'automations' => $plan !== 'trial',
                'customer_followups' => in_array($plan, ['pro', 'premium']),
                'customer_categorization' => in_array($plan, ['pro', 'premium']),
                'booking_calendars' => $plan === 'premium',
                'sales_reports' => in_array($plan, ['pro', 'premium'])
            ]
        ];
        
        // Cache fallback for shorter duration
        self::cacheStatus($customerId, $status, self::FALLBACK_DURATION);
        
        // Log fallback usage for revenue tracking
        Log::warning("Using fallback billing status for customer {$customerId}, plan: {$plan}");
        
        return $status;
    }
    
    /**
     * Enrich status data with additional computed fields
     */
    private static function enrichStatusData($data, $customerId)
    {
        $data['loaded_at'] = now()->toISOString();
        $data['expires_at'] = now()->addSeconds(self::CACHE_DURATION)->toISOString();
        $data['is_fallback'] = false;
        
        // Add computed permissions
        $plan = $data['subscription']['plan'];
        $active = $data['subscription']['status'] === 'active';
        
        $data['permissions'] = [
            'add_contact' => $active && $data['limits']['contacts']['current'] < $data['limits']['contacts']['max'],
            'add_product' => $active && $data['limits']['products']['current'] < $data['limits']['products']['max'],
            'send_message' => $active,
            'use_ai' => $active && $data['wallet']['ai_credits'] > 0,
            'automations' => $active && $plan !== 'trial',
            'customer_followups' => $active && in_array($plan, ['pro', 'premium']),
            'customer_categorization' => $active && in_array($plan, ['pro', 'premium']),
            'booking_calendars' => $active && $plan === 'premium',
            'sales_reports' => $active && in_array($plan, ['pro', 'premium'])
        ];
        
        return $data;
    }
    
    /**
     * Cache billing status with expiration
     */
    private static function cacheStatus($customerId, $status, $duration = null)
    {
        $cacheKey = self::CACHE_PREFIX . $customerId;
        $cacheDuration = $duration ?? self::CACHE_DURATION;
        
        Cache::put($cacheKey, $status, $cacheDuration);
    }
    
    /**
     * REVENUE PROTECTION: Validate cache integrity
     */
    private static function isCacheValid($status)
    {
        if (!$status || !is_array($status)) {
            return false;
        }
        
        // Check if cache has expired
        if (isset($status['expires_at'])) {
            $expiresAt = \Carbon\Carbon::parse($status['expires_at']);
            if ($expiresAt->isPast()) {
                return false;
            }
        }
        
        // Check required fields
        $requiredFields = ['customer_id', 'subscription', 'limits', 'permissions'];
        foreach ($requiredFields as $field) {
            if (!isset($status[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Force refresh billing status (emergency use)
     */
    public static function forceRefresh($customerId)
    {
        $cacheKey = self::CACHE_PREFIX . $customerId;
        Cache::forget($cacheKey);
        
        Log::info("Force refreshing billing status for customer {$customerId}");
        return self::loadCompleteStatus($customerId);
    }
    
    /**
     * Clear billing cache (for testing or manual intervention)
     */
    public static function clearCache($customerId)
    {
        $cacheKey = self::CACHE_PREFIX . $customerId;
        Cache::forget($cacheKey);
    }
    
    /**
     * Get plan configuration from cached data
     */
    public static function getPlanLimits($plan)
    {
        $planLimits = [
            'trial' => [
                'max_contacts' => 10,
                'max_products' => 1,
                'max_outgoing_messages' => 50,
                'whatsapp_channels' => 1,
                'ai_credits' => 0,
                'customer_followups' => false,
                'customer_categorization' => false,
                'booking_calendars' => false,
                'sales_reports' => false
            ],
            'starter' => [
                'max_contacts' => 50,
                'max_products' => 5,
                'whatsapp_channels' => 1,
                'ai_credits' => 69000,
                'customer_followups' => false,
                'customer_categorization' => false,
                'booking_calendars' => false,
                'sales_reports' => false
            ],
            'pro' => [
                'max_contacts' => 150,
                'max_products' => 50,
                'whatsapp_channels' => 3,
                'ai_credits' => 149000,
                'customer_followups' => true,
                'customer_categorization' => true,
                'booking_calendars' => false,
                'sales_reports' => true
            ],
            'premium' => [
                'max_contacts' => 400,
                'max_products' => 200,
                'whatsapp_channels' => 7,
                'ai_credits' => 299000,
                'customer_followups' => true,
                'customer_categorization' => true,
                'booking_calendars' => true,
                'sales_reports' => true
            ]
        ];
        
        return $planLimits[$plan] ?? $planLimits['trial'];
    }
    
    /**
     * Get products catalog from billing system
     * 
     * @param array $params Query parameters (product_code, currency, active_only)
     * @return array Product catalog data or error
     */
    public static function getProducts($params = [])
    {
        try {
            // Set default parameters
            $queryParams = array_merge([
                'product_code' => self::PRODUCT_CODE,
                'currency' => 'TZS',
                'active_only' => true
            ], $params);
            
            $response = Http::timeout(10)->withHeaders([
                'X-API-Key' => 'Dp77IDXdqtBuB2zLvYovj2QmAK',
                'Accept' => 'application/json'
            ])->get(self::getBillingApiBase() . "/products", $queryParams);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success']) {
                    Log::info("Products catalog fetched successfully", ['product_code' => $queryParams['product_code']]);
                    return [
                        'success' => true,
                        'data' => $data['data'] ?? []
                    ];
                }
            }
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error("Failed to fetch products catalog: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Get specific product details from billing system
     * 
     * @param string|null $productCode Product code (defaults to PRODUCT_CODE constant)
     * @param string $currency Currency code (defaults to TZS)
     * @return array Product details or error
     */
    public static function getProductDetails($productCode = null, $currency = 'TZS')
    {
        try {
            $productCode = $productCode ?? self::PRODUCT_CODE;
            
            $response = Http::timeout(10)->withHeaders([
                'X-API-Key' => 'Dp77IDXdqtBuB2zLvYovj2QmAK',
                'Accept' => 'application/json'
            ])->get(self::getBillingApiBase() . "/products", [
                'product_code' => $productCode,
                'currency' => $currency
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success']) {
                    Log::info("Product details fetched successfully", ['product_code' => $productCode]);
                    return [
                        'success' => true,
                        'data' => $data['data'] ?? null
                    ];
                }
            }
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error("Failed to fetch product details for {$productCode}: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Get SafariChat product configuration with all plans
     * Convenience method specifically for SafariChat product
     * 
     * @return array SafariChat product details
     */
    public static function getSafariChatProduct()
    {
        return self::getProductDetails('safarichat');
    }
}