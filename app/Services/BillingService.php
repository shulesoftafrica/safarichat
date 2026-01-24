<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Business;
use App\Models\BillingAccount;

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
        // Use the configured billing API URL from services config
        return config('services.billing.api_url', 'http://localhost/shulesoft_newversion/api/billing');
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
     * Alias for getCachedStatus for backward compatibility
     */
    public static function getBillingStatus($customerId)
    {
        return self::getCachedStatus($customerId);
    }
    
    /**
     * Load complete customer billing status from API
     * This runs once at app boot and periodically for refresh
     */
    public static function loadCompleteStatus($customerId)
    {
        try {
            $response = Http::timeout(10)->withHeaders([
                'X-API-Key' => config('services.billing.api_key'),
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
     * Now uses BillingAccount as single source of truth
     */
    private static function getFallbackStatus($customerId)
    {
        // Get user and their billing account
        $user = User::find($customerId);
        
        if (!$user) {
            return self::getDefaultTrialStatus($customerId);
        }
        
        // Get or create billing account
        $billingAccount = $user->billingAccount ?? $user->getOrCreateBillingAccount();
        
        if (!$billingAccount) {
            return self::getDefaultTrialStatus($customerId);
        }
        
        $plan = $billingAccount->subscription_plan;
        $aiCredits = $billingAccount->ai_credits;
        
        // Build status from billing account data
        $status = [
            'customer_id' => $customerId,
            'loaded_at' => now()->toISOString(),
            'expires_at' => now()->addSeconds(self::FALLBACK_DURATION)->toISOString(),
            'is_fallback' => true,
            'subscription' => [
                'active' => $billingAccount->isActive(),
                'plan' => $plan,
                'trial' => $plan === 'trial',
                'expires' => $billingAccount->subscription_expires_at 
                    ? $billingAccount->subscription_expires_at->toISOString() 
                    : now()->addDays(30)->toISOString()
            ],
            'limits' => [
                'contacts' => [
                    'current' => 0, 
                    'max' => $billingAccount->max_contacts, 
                    'canAdd' => true
                ],
                'products' => [
                    'current' => 0, 
                    'max' => $billingAccount->max_products, 
                    'canAdd' => true
                ],
                'whatsapp_channels' => [
                    'current' => 0, 
                    'max' => $billingAccount->whatsapp_channels, 
                    'canAdd' => true
                ],
                'ai_credits' => [
                    'balance' => $aiCredits, 
                    'canUse' => $aiCredits > 0
                ]
            ],
            'permissions' => [
                'add_contact' => $billingAccount->isActive(),
                'add_product' => $billingAccount->isActive(),
                'send_message' => $billingAccount->isActive(),
                'use_ai' => $billingAccount->isActive() && $aiCredits > 0,
                'automations' => $billingAccount->isActive() && $plan !== 'trial',
                'customer_followups' => $billingAccount->customer_followups,
                'customer_categorization' => $billingAccount->customer_categorization,
                'booking_calendars' => $billingAccount->booking_calendars,
                'sales_reports' => $billingAccount->sales_reports
            ]
        ];
        
        // Cache fallback for shorter duration
        self::cacheStatus($customerId, $status, self::FALLBACK_DURATION);
        
        // Log fallback usage for revenue tracking
        Log::warning("Using fallback billing status for customer {$customerId}, plan: {$plan}, credits: {$aiCredits}");
        
        return $status;
    }
    
    /**
     * Get default trial status when no user found
     */
    private static function getDefaultTrialStatus($customerId)
    {
        $trialConfig = config('safarichat_billing.plans.trial');
        
        return [
            'customer_id' => $customerId,
            'loaded_at' => now()->toISOString(),
            'expires_at' => now()->addSeconds(self::FALLBACK_DURATION)->toISOString(),
            'is_fallback' => true,
            'subscription' => [
                'active' => false,
                'plan' => 'trial',
                'trial' => true,
                'expires' => now()->addDays(3)->toISOString()
            ],
            'limits' => [
                'contacts' => ['current' => 0, 'max' => 5, 'canAdd' => false],
                'products' => ['current' => 0, 'max' => 1, 'canAdd' => false],
                'whatsapp_channels' => ['current' => 0, 'max' => 1, 'canAdd' => false],
                'ai_credits' => ['balance' => 0, 'canUse' => false]
            ],
            'permissions' => [
                'add_contact' => false,
                'add_product' => false,
                'send_message' => false,
                'use_ai' => false,
                'automations' => false,
                'customer_followups' => false,
                'customer_categorization' => false,
                'booking_calendars' => false,
                'sales_reports' => false
            ]
        ];
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
            try {
                $expiresAt = \Carbon\Carbon::parse($status['expires_at']);
                if ($expiresAt->isPast()) {
                    return false;
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to parse expires_at in billing cache validation', [
                    'expires_at' => $status['expires_at'],
                    'error' => $e->getMessage(),
                ]);
                return false; // Treat parse error as invalid cache
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
     * Get plan configuration from config file
     */
    public static function getPlanLimits($plan)
    {
        $planConfig = config("safarichat_billing.plans.{$plan}");
        
        if (!$planConfig) {
            // Fallback to trial plan if plan not found
            $planConfig = config('safarichat_billing.plans.trial');
        }
        
        // Return only the limits array
        return $planConfig['limits'] ?? [];
    }
    
    /**
     * Get products catalog from billing system
     * Falls back to config file if API is unavailable
     * 
     * @param array $params Query parameters (product_code, currency, active_only)
     * @return array Product catalog data or error
     */
    public static function getProducts($params = [])
    {
        try {
            // Set default parameters
            $queryParams = array_merge([
            ], $params);
            
            $apiUrl = self::getBillingApiBase() . "/products/by-code/".self::PRODUCT_CODE;
            Log::info("Fetching products catalog", [
                'api_url' => $apiUrl,
                'query_params' => $queryParams
            ]);
            
            $response = Http::timeout(10)->withHeaders([
                'Authorization' => 'Bearer ' . config('services.billing.api_key'),
                'Accept' => 'application/json'
            ])->get($apiUrl, $queryParams);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['success']) && $data['success']) {
                    Log::info("Products catalog fetched successfully", ['product_code' => self::PRODUCT_CODE]);
                    return [
                        'success' => true,
                        'data' => $data['data'] ?? []
                    ];
                }
            }
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::warning("Billing API unavailable, using config fallback", [
                'error' => $e->getMessage(),
                'api_url' => self::getBillingApiBase(),
            ]);
            
            // FALLBACK: Return products from config file
            return self::getProductsFromConfig();
        }
    }
    
    /**
     * Get products from config file as fallback
     * @return array Product catalog from config
     */
    private static function getProductsFromConfig()
    {
        $config = config('safarichat_billing');
        $plans = $config['plans'] ?? [];
        
        $products = [];
        foreach ($plans as $planName => $planData) {
            $products[] = [
                'id' => $planName,
                'product_code' => $config['product_code'],
                'plan_name' => ucfirst($planName),
                'price' => $planData['price'] ?? 0,
                'currency' => $planData['currency'] ?? 'TZS',
                'billing_cycle' => $planData['billing_cycle'] ?? 'one-time',
                'duration_days' => $planData['duration_days'] ?? 30,
                'limits' => $planData['limits'] ?? [],
                'credits_rollover' => $planData['credits_rollover'] ?? false,
                'is_active' => true,
                'source' => 'config_fallback'
            ];
        }
        
        Log::info("Loaded products from config file", [
            'product_count' => count($products),
            'plans' => array_keys($plans)
        ]);
        
        return [
            'success' => true,
            'data' => $products,
            'source' => 'config'
        ];
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
                'X-API-Key' => config('services.billing.api_key'),
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

    
    /**
     * Get billing account for a user
     * @param User|int $user User instance or user ID
     * @return BillingAccount|null
     */
    public static function getBillingAccountForUser($user)
    {
        if (is_numeric($user)) {
            $user = User::find($user);
        }
        
        if (!$user) {
            return null;
        }
        
        return $user->billingAccount ?? $user->getOrCreateBillingAccount();
    }
    
    /**
     * Deduct AI credits from billing account
     * @param User|int $user
     * @param int $credits
     * @param string $reason
     * @return bool Success status
     */
    public static function deductCredits($user, int $credits, string $reason = null): bool
    {
        $billingAccount = self::getBillingAccountForUser($user);
        
        if (!$billingAccount) {
            Log::error("Cannot deduct credits: No billing account found for user", [
                'user_id' => is_numeric($user) ? $user : $user->id
            ]);
            return false;
        }
        
        return $billingAccount->deductCredits($credits, $reason);
    }
    
    /**
     * Add AI credits to billing account
     * @param User|int $user
     * @param int $credits
     * @param string $reason
     */
    public static function addCredits($user, int $credits, string $reason = null): void
    {
        $billingAccount = self::getBillingAccountForUser($user);
        
        if (!$billingAccount) {
            Log::error("Cannot add credits: No billing account found");
            return;
        }
        
        $billingAccount->addCredits($credits, $reason);
    }

    /**
     * Get remaining credits for user
     * @param User|int $user
     * @return int
     */
    public static function getRemainingCredits($user): int
    {
        $billingAccount = self::getBillingAccountForUser($user);
        
        if (!$billingAccount) {
            return 0;
        }
        
        return $billingAccount->ai_credits;
    }
    
    /**
     * Check if user has sufficient credits
     * @param User|int $user
     * @param int $credits
     * @return bool
     */
    public static function hasCredits($user, int $credits = 1): bool
    {
        $billingAccount = self::getBillingAccountForUser($user);
        
        if (!$billingAccount) {
            return false;
        }
        
        return $billingAccount->hasCredits($credits);
    }    
    /**
     * Check if user can add new contact based on subscription limit
     * @param User|int $user
     * @return array ['can_add' => bool, 'current' => int, 'max' => int, 'plan' => string, 'message' => string]
     */
    public static function canAddContact($user): array
    {
        $billingAccount = self::getBillingAccountForUser($user);
        
        if (!$billingAccount) {
            return [
                'can_add' => false,
                'current' => 0,
                'max' => 0,
                'plan' => 'none',
                'message' => 'No active subscription found. Please subscribe to add contacts.'
            ];
        }
        
        // Get current contact count for this user/business
        $userId = is_numeric($user) ? $user : $user->id;
        $currentCount = \App\Models\BusinessContact::where('user_id', $userId)->count();
        
        $maxContacts = $billingAccount->max_contacts;
        $plan = $billingAccount->subscription_plan;
        
        $canAdd = $currentCount < $maxContacts;
        
        return [
            'can_add' => $canAdd,
            'current' => $currentCount,
            'max' => $maxContacts,
            'plan' => $plan,
            'message' => $canAdd 
                ? "You can add more contacts ({$currentCount}/{$maxContacts} used)"
                : "Contact limit reached ({$currentCount}/{$maxContacts}). Upgrade your {$plan} plan to add more contacts."
        ];
    }
    
    /**
     * Check if user can add multiple contacts (for bulk import)
     * @param User|int $user
     * @param int $countToAdd
     * @return array ['can_add' => bool, 'current' => int, 'max' => int, 'available' => int, 'plan' => string, 'message' => string]
     */
    public static function canAddContacts($user, int $countToAdd): array
    {
        $billingAccount = self::getBillingAccountForUser($user);
        
        if (!$billingAccount) {
            return [
                'can_add' => false,
                'current' => 0,
                'max' => 0,
                'available' => 0,
                'plan' => 'none',
                'message' => 'No active subscription found. Please subscribe to add contacts.'
            ];
        }
        
        // Get current contact count for this user/business
        $userId = is_numeric($user) ? $user : $user->id;
        $currentCount = \App\Models\BusinessContact::where('user_id', $userId)->count();
        
        $maxContacts = $billingAccount->max_contacts;
        $plan = $billingAccount->subscription_plan;
        $available = max(0, $maxContacts - $currentCount);
        
        $canAdd = $currentCount + $countToAdd <= $maxContacts;
        
        return [
            'can_add' => $canAdd,
            'current' => $currentCount,
            'max' => $maxContacts,
            'available' => $available,
            'plan' => $plan,
            'message' => $canAdd 
                ? "You can add {$countToAdd} contacts ({$currentCount}/{$maxContacts} used, {$available} available)"
                : "Cannot add {$countToAdd} contacts. Only {$available} slots available ({$currentCount}/{$maxContacts} used). Upgrade your {$plan} plan to add more contacts."
        ];
    }}
