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
    const PRODUCT_CODE = 4; // Default product code
    
    private static function getBillingApiBase()
    {
        // Use the configured billing API URL from Shulesoft config (includes /api/v1)
        // .env should set: SHULESOFT_API_URL=https://shulesoftapi.shulesoft.africa/api/v1
        return rtrim(config('services.shulesoft_billing.api_url', 'https://api.safaribank.africa/api/v1'), '/');
    }
    
    /**
     * Get active access token from OAuth service
     * Uses dynamic token management with automatic refresh
     * Falls back to static token if OAuth is unavailable
     * 
     * @return string Access token
     */
    private static function getAccessToken()
    {
        try {
            $token = ShulesoftAuthService::getAccessToken();
            
            // If OAuth returns null (disabled due to server issues), use static token
            if ($token === null) {
                Log::warning('OAuth unavailable, using static token fallback');
                return config('services.billing.access_token', '');
            }
            
            return $token;
        } catch (\Exception $e) {
            Log::error('Failed to get access token: ' . $e->getMessage());
            // Fallback to static token if OAuth fails
            return config('services.billing.access_token', '');
        }
    }
    
    /**
     * Make API request with automatic token refresh on 401 errors
     * Handles OAuth authentication transparently
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $endpoint API endpoint
     * @param array $data Request data/params
     * @param int $attempt Current attempt number (for retry logic)
     * @return \Illuminate\Http\Client\Response
     * @throws \Exception If request fails after retry
     */
    private static function makeAuthenticatedRequest($method, $endpoint, $data = [], $attempt = 1)
    {
        $token = self::getAccessToken();
        
        $http = Http::timeout(config('services.shulesoft_billing.timeout', 30))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]);
        
        // Make request based on method
        $response = match(strtoupper($method)) {
            'GET' => $http->get($endpoint, $data),
            'POST' => $http->post($endpoint, $data),
            'PUT' => $http->put($endpoint, $data),
            'DELETE' => $http->delete($endpoint, $data),
            default => throw new \Exception("Unsupported HTTP method: {$method}")
        };
        
        // Check for 401 Unauthorized - token expired
        if ($response->status() === 401 && $attempt === 1) {
            Log::info('Received 401 Unauthorized, attempting token refresh...');
            
            try {
                // Force token refresh
                ShulesoftAuthService::refreshAccessToken();
                
                // Retry the request once with new token
                return self::makeAuthenticatedRequest($method, $endpoint, $data, 2);
                
            } catch (\Exception $e) {
                Log::error('Token refresh failed: ' . $e->getMessage());
                // Return the 401 response - caller will handle fallback
                return $response;
            }
        }
        
        return $response;
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
            $response = self::makeAuthenticatedRequest(
                'GET',
                self::getBillingApiBase() . "/customers/{$customerId}/complete-status"
            );
            
            if ($response->successful()) {
                $data = $response->json();
                
                if ($data === null) {
                    throw new \Exception('Billing API returned invalid JSON response');
                }
                
                if (isset($data['success']) && $data['success']) {
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
                'sales_reports' => $billingAccount->sales_reports,
                'image_vision' => $billingAccount->isActive() && $plan === 'premium',
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
            'sales_reports' => $active && in_array($plan, ['pro', 'premium']),
            'image_vision' => $active && $plan === 'premium',
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
    
            $apiUrl = self::getBillingApiBase() . "/products/".self::PRODUCT_CODE;
            Log::info("Fetching products catalog", [
                'api_url' => $apiUrl,
                'query_params' => $queryParams
            ]);
            
            $response = self::makeAuthenticatedRequest('GET', $apiUrl, $queryParams);
            
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
            
            $response = self::makeAuthenticatedRequest(
                'GET',
                self::getBillingApiBase() . "/products",
                [
                    'product_code' => $productCode,
                    'currency' => $currency
                ]
            );
            
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
        
        $result = $billingAccount->deductCredits($credits, $reason);
        
        // Check if credits are low after deduction and send notification
        if ($result) {
            $userId = is_numeric($user) ? $user : $user->id;
            self::checkAndNotifyLowCredits($userId);
        }
        
        return $result;
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
        
        // Count contacts at business scope (a business is the billing unit)
        $userObj = is_numeric($user) ? \App\Models\User::find($user) : $user;
        $businessId = $userObj?->business?->id;
        $currentCount = $businessId
            ? \App\Models\BusinessContact::where('business_id', $businessId)->count()
            : \App\Models\BusinessContact::where('user_id', is_numeric($user) ? $user : $user->id)->count();
        
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
        
        // Count contacts at business scope (a business is the billing unit)
        $userObj = is_numeric($user) ? \App\Models\User::find($user) : $user;
        $businessId = $userObj?->business?->id;
        $currentCount = $businessId
            ? \App\Models\BusinessContact::where('business_id', $businessId)->count()
            : \App\Models\BusinessContact::where('user_id', is_numeric($user) ? $user : $user->id)->count();
        
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
    }

    /**
     * Check if user can create a new booking calendar
     *
     * @param User $user
     * @return array ['can_create', 'current', 'max', 'plan', 'message', 'upgrade_required']
     */
    public static function canCreateBookingCalendar($user): array
    {
        $billingAccount = $user->business->billingAccount;
        $currentPlan = $billingAccount ? ($billingAccount->subscription_plan ?? 'trial') : 'trial';
        $planLimits = config("safarichat_billing.plans.{$currentPlan}.limits", []);
        
        $maxCalendars = $planLimits['max_booking_calendars'] ?? 0;
        
        // Check if feature is allowed
        if ($maxCalendars === 0) {
            return [
                'can_create' => false,
                'current' => 0,
                'max' => 0,
                'plan' => $currentPlan,
                'message' => 'Booking calendars are not available on your current plan. Upgrade to Starter or higher.',
                'upgrade_required' => true
            ];
        }
        
        // Unlimited calendars
        if ($maxCalendars === -1) {
            $currentCount = \App\Models\BookingCalendar::where('business_id', $user->business->id)->count();
            return [
                'can_create' => true,
                'current' => $currentCount,
                'max' => -1,
                'plan' => $currentPlan,
                'message' => 'You have unlimited booking calendars.',
                'upgrade_required' => false
            ];
        }
        
        // Check current count
        $currentCount = \App\Models\BookingCalendar::where('business_id', $user->business->id)->count();
        
        return [
            'can_create' => $currentCount < $maxCalendars,
            'current' => $currentCount,
            'max' => $maxCalendars,
            'plan' => $currentPlan,
            'message' => $currentCount >= $maxCalendars 
                ? "You have reached your booking calendar limit ({$maxCalendars} calendars). Please upgrade to create more."
                : "You can create " . ($maxCalendars - $currentCount) . " more booking calendar(s).",
            'upgrade_required' => $currentCount >= $maxCalendars
        ];
    }

    /**
     * Check if AI credits are low and send notification if needed
     * Notifications sent at: 20%, 10%, and 0% thresholds (only once per threshold)
     * 
     * @param int $userId
     * @return void
     */
    private static function checkAndNotifyLowCredits($userId): void
    {
        try {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return;
            }

            $billingAccount = self::getBillingAccountForUser($userId);
            if (!$billingAccount) {
                return;
            }

            $planType = $billingAccount->subscription_plan;
            $creditLimit = config("safarichat_billing.plans.{$planType}.limits.ai_credits");
            
            // Skip unlimited plans
            if ($creditLimit === 'unlimited' || $creditLimit <= 0) {
                return;
            }

            $remaining = $billingAccount->ai_credits ?? 0;
            $percentage = ($remaining / $creditLimit) * 100;

            $notificationService = app(\App\Services\AccountNotificationService::class);

            // Credits depleted (0%)
            if ($remaining <= 0) {
                // Check if we've already notified for depletion (using cache to prevent spam)
                $cacheKey = "credit_notification_depleted_{$userId}";
                if (!\Cache::has($cacheKey)) {
                    $notificationService->notifyCreditsDepletion($user, 'AI message');
                    \Cache::put($cacheKey, true, now()->addHours(6)); // Notify max once per 6 hours
                }
            }
            // Critical threshold (10%)
            elseif ($percentage <= 10) {
                $cacheKey = "credit_notification_critical_{$userId}";
                if (!\Cache::has($cacheKey)) {
                    $notificationService->notifyLowCredits($user, $remaining, $creditLimit);
                    \Cache::put($cacheKey, true, now()->addHours(24)); // Notify max once per day
                }
            }
            // Warning threshold (20%)
            elseif ($percentage <= 20) {
                $cacheKey = "credit_notification_warning_{$userId}";
                if (!\Cache::has($cacheKey)) {
                    $notificationService->notifyLowCredits($user, $remaining, $creditLimit);
                    \Cache::put($cacheKey, true, now()->addDays(2)); // Notify max once per 2 days
                }
            }

        } catch (\Exception $e) {
            Log::error('Failed to check and notify low credits', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Create/Get subscription invoice for renewal
     * 
     * @param User $user
     * @param int $pricePlanId
     * @param float $amount
     * @param string $paymentGateway
     * @param string $successUrl
     * @param string $cancelUrl
     * @return array Invoice details with payment links and UCN
     */
    public static function createSubscriptionInvoice($user, $pricePlanId, $amount, $paymentGateway = 'flutterwave', $successUrl = null, $cancelUrl = null)
    {
        try {
            $business = $user->business;
            $billingAccount = $business->billingAccount ?? $business->getOrCreateBillingAccount();
            
            // Use business records for customer info; generate fallback email if missing
            $customerEmail = $business->email ?: null;
            if (empty($customerEmail)) {
                $customerPhone = preg_replace('/\D/', '', $business->phone ?? '');
                $customerEmail = time() . '.' . $customerPhone . '@safarichat.ai';
            }

            // Prepare invoice data
            $data = [
                'organization_id' => config('services.billing.organization_id', 1),
                'customer' => [
                    'name' => $business->name,
                    'email' => $customerEmail,
                    'phone' => $business->phone
                ],
                'products' => [
                    [
                        'price_plan_id' => $pricePlanId,
                        'amount' => $amount
                    ]
                ],
                'description' => 'SafariChat subscription renewal',
                'currency' => 'TZS',
                'status' => 'issued',
                'payment_gateway' => $paymentGateway,
                'success_url' => $successUrl ?? route('billing.success'),
                'cancel_url' => $cancelUrl ?? route('billing.cancel')
            ];

            Log::info('Creating subscription invoice with data', [
                'user_id' => $user->id,
                'billing_api_url' => self::getBillingApiBase() . '/invoices',
                'data' => $data
            ]);
            
            $response = self::makeAuthenticatedRequest(
                'POST',
                self::getBillingApiBase() . '/invoices',
                $data
            );
            
            if ($response->successful()) {
                $result = $response->json();
                
                // Check if response is valid JSON
                if ($result === null) {
                    Log::error('Billing API returned non-JSON response', [
                        'user_id' => $user->id,
                        'status' => $response->status(),
                        'content_type' => $response->header('Content-Type'),
                        'body_preview' => substr($response->body(), 0, 500)
                    ]);
                    
                    throw new \Exception('Billing API returned invalid JSON response. The API may be unavailable.');
                }
                
                Log::info('Billing API response for invoice creation', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                    'response_structure' => is_array($result) ? array_keys($result) : 'not_an_array',
                    'has_success_key' => isset($result['success']),
                    'has_data_key' => isset($result['data']),
                    'data_keys' => isset($result['data']) && is_array($result['data']) ? array_keys($result['data']) : []
                ]);
                
                if (isset($result['success']) && $result['success']) {
                    Log::info('Subscription invoice created successfully', [
                        'user_id' => $user->id,
                        'response_data' => $result,
                        'invoice_id' => $result['data']['invoice']['id'] ?? $result['data']['id'] ?? null
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $result['data'],
                        'message' => $result['message'] ?? null
                    ];
                }
            }
            
            Log::error('Billing API returned unsuccessful response', [
                'user_id' => $user->id,
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('yFailed to create subscription invoice', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Upgrade subscription to a higher plan
     * 
     * @param int $subscriptionId
     * @param int $newPricePlanId
     * @param string $paymentGateway
     * @return array Upgrade details with payment links and UCN
     */
    public static function upgradeSubscription($subscriptionId, $newPricePlanId, $paymentGateway = 'flutterwave')
    {
        try {
            $data = [
                'subscription_id' => $subscriptionId,
                'new_price_plan_id' => $newPricePlanId,
                'payment_gateway' => $paymentGateway
            ];
            
            $response = self::makeAuthenticatedRequest(
                'POST',
                self::getBillingApiBase() . '/invoices/plan-upgrade',
                $data
            );
            
            if ($response->successful()) {
                $result = $response->json();
                
                if ($result === null) {
                    throw new \Exception('Billing API returned invalid JSON response');
                }
                
                if (isset($result['success']) && $result['success']) {
                    Log::info('Subscription upgraded successfully', [
                        'subscription_id' => $subscriptionId,
                        'new_plan_id' => $newPricePlanId
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $result['data']
                    ];
                }
            }
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('Failed to upgrade subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Downgrade subscription to a lower plan
     * 
     * @param int $subscriptionId
     * @param int $newPricePlanId
     * @param bool $applyCredit
     * @return array Downgrade details
     */
    public static function downgradeSubscription($subscriptionId, $newPricePlanId, $applyCredit = true)
    {
        try {
            $data = [
                'subscription_id' => $subscriptionId,
                'new_price_plan_id' => $newPricePlanId,
                'apply_credit' => $applyCredit
            ];
            
            $response = self::makeAuthenticatedRequest(
                'POST',
                self::getBillingApiBase() . '/invoices/plan-downgrade',
                $data
            );
            
            if ($response->successful()) {
                $result = $response->json();
                
                if ($result === null) {
                    throw new \Exception('Billing API returned invalid JSON response');
                }
                
                if (isset($result['success']) && $result['success']) {
                    Log::info('Subscription downgraded successfully', [
                        'subscription_id' => $subscriptionId,
                        'new_plan_id' => $newPricePlanId
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $result['data']
                    ];
                }
            }
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('Failed to downgrade subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get subscription details including payment  gateways
     * 
     * @param int $subscriptionId
     * @return array Subscription details
     */
    public static function getSubscriptionDetails($subscriptionId)
    {
        try {
            $response = self::makeAuthenticatedRequest(
                'GET',
                self::getBillingApiBase() . '/subscriptions/' . $subscriptionId
            );
            
            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['success']) && $result['success']) {
                    Log::info('Subscription details fetched successfully', [
                        'subscription_id' => $subscriptionId
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $result['data']
                    ];
                }
            }
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch subscription details', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get customer's subscriptions
     * 
     * @param int $customerId
     * @return array Customer subscriptions
     */
    public static function getCustomerSubscriptions($customerId)
    {
        try {
            $response = self::makeAuthenticatedRequest(
                'GET',
                self::getBillingApiBase() . '/customers/' . $customerId . '/subscriptions'
            );
            
            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['success']) && $result['success']) {
                    Log::info('Customer subscriptions fetched successfully', [
                        'customer_id' => $customerId,
                        'subscriptions_count' => count($result['data'] ?? [])
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $result['data']
                    ];
                }
            }
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch customer subscriptions', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get the most recent invoice for a customer
     * 
     * @param int $customerId
     * @param string $status Filter by status (pending, issued, paid, etc.)
     * @return array Invoice details
     */
    public static function getCustomerLatestInvoice($customerId, $status = null)
    {
        try {
            $url = self::getBillingApiBase() . '/customers/' . $customerId . '/invoices/latest';
            if ($status) {
                $url .= '?status=' . $status;
            }
            
            $response = self::makeAuthenticatedRequest('GET', $url);
            
            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['success']) && $result['success']) {
                    Log::info('Latest invoice fetched successfully', [
                        'customer_id' => $customerId,
                        'invoice_id' => $result['data']['id'] ?? null,
                        'status' => $result['data']['status'] ?? null
                    ]);
                    
                    return [
                        'success' => true,
                        'data' => $result['data']
                    ];
                }
            }
            
            throw new \Exception('API returned error: ' . $response->body());
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch latest invoice', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
