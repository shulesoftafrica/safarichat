<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Local Billing Validator - Revenue Protected Validation System
 * All validation happens locally using cached billing status
 * NO API CALLS during normal operations
 */
class LocalBillingValidator
{
    /**
     * REVENUE PROTECTION: Always check cache validity first
     */
    public static function validateCacheOrFail($status)
    {
        if (!$status) {
            Log::warning('Billing validation failed: no_cache');
            return ['valid' => false, 'reason' => 'no_cache'];
        }
        
        if (isset($status['expires_at'])) {
            try {
                $expiresAt = Carbon::parse($status['expires_at']);
                if ($expiresAt->isPast()) {
                    Log::warning('Billing validation failed: cache_expired');
                    return ['valid' => false, 'reason' => 'cache_expired'];
                }
            } catch (\Exception $e) {
                Log::warning('Billing validation failed: invalid_expires_at', [
                    'expires_at' => $status['expires_at'],
                    'error' => $e->getMessage(),
                ]);
                return ['valid' => false, 'reason' => 'invalid_date_format'];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Check if action is allowed (REVENUE PROTECTED)
     */
    public static function canAddContact($status)
    {
        // CRITICAL: Validate cache first
        $cacheCheck = self::validateCacheOrFail($status);
        if (!$cacheCheck['valid']) {
            return ['allowed' => false, 'reason' => $cacheCheck['reason']];
        }
        
        // Check subscription status
        if (!$status['subscription']['active']) {
            return ['allowed' => false, 'reason' => 'subscription_inactive'];
        }
        
        // Check permission
        if (!$status['permissions']['add_contact']) {
            return ['allowed' => false, 'reason' => 'permission_denied'];
        }
        
        // Check limits
        $contacts = $status['limits']['contacts'] ?? [];
        if (isset($contacts['current'], $contacts['max'])) {
            if ($contacts['current'] >= $contacts['max']) {
                return ['allowed' => false, 'reason' => 'limit_reached', 'current' => $contacts['current'], 'max' => $contacts['max']];
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Check if can add product (REVENUE PROTECTED)
     */
    public static function canAddProduct($status)
    {
        $cacheCheck = self::validateCacheOrFail($status);
        if (!$cacheCheck['valid']) {
            return ['allowed' => false, 'reason' => $cacheCheck['reason']];
        }
        
        if (!$status['subscription']['active']) {
            return ['allowed' => false, 'reason' => 'subscription_inactive'];
        }
        
        if (!$status['permissions']['add_product']) {
            return ['allowed' => false, 'reason' => 'permission_denied'];
        }
        
        $products = $status['limits']['products'] ?? [];
        if (isset($products['current'], $products['max'])) {
            if ($products['current'] >= $products['max']) {
                return ['allowed' => false, 'reason' => 'limit_reached', 'current' => $products['current'], 'max' => $products['max']];
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Check if can send message (REVENUE PROTECTED)
     */
    public static function canSendMessage($status)
    {
        $cacheCheck = self::validateCacheOrFail($status);
        if (!$cacheCheck['valid']) {
            return ['allowed' => false, 'reason' => $cacheCheck['reason']];
        }
        
        if (!$status['subscription']['active']) {
            return ['allowed' => false, 'reason' => 'subscription_inactive'];
        }
        
        if (!$status['permissions']['send_message']) {
            return ['allowed' => false, 'reason' => 'permission_denied'];
        }
        
        // Check message limits if applicable
        $messages = $status['limits']['messages'] ?? [];
        if (isset($messages['unlimited']) && !$messages['unlimited']) {
            if (isset($messages['current'], $messages['max']) && $messages['current'] >= $messages['max']) {
                return ['allowed' => false, 'reason' => 'limit_reached'];
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * CRITICAL: Check if can use AI with credits validation
     */
    public static function canUseAI($status, $creditsNeeded = 1)
    {
        $cacheCheck = self::validateCacheOrFail($status);
        if (!$cacheCheck['valid']) {
            // REVENUE PROTECTION: Force refresh on expired cache
            Log::warning("AI usage blocked due to cache validation failure for customer {$status['customer_id']}");
            return ['allowed' => false, 'reason' => 'cache_refresh_required'];
        }
        
        if (!$status['subscription']['active']) {
            return ['allowed' => false, 'reason' => 'subscription_inactive'];
        }
        
        if (!$status['permissions']['use_ai']) {
            return ['allowed' => false, 'reason' => 'permission_denied'];
        }
        
        // REVENUE PROTECTION: Check credits availability
        $aiCredits = $status['limits']['ai_credits'] ?? $status['wallet']['ai_credits'] ?? [];
        $balance = $aiCredits['balance'] ?? $aiCredits ?? 0;
        
        if ($balance < $creditsNeeded) {
            Log::info("AI usage blocked: insufficient credits (needed: {$creditsNeeded}, available: {$balance})");
            return ['allowed' => false, 'reason' => 'insufficient_credits', 'needed' => $creditsNeeded, 'available' => $balance];
        }
        
        return ['allowed' => true, 'credits_available' => $balance];
    }
    
    /**
     * Check if can add WhatsApp channel
     */
    public static function canAddWhatsAppChannel($status)
    {
        $cacheCheck = self::validateCacheOrFail($status);
        if (!$cacheCheck['valid']) {
            return ['allowed' => false, 'reason' => $cacheCheck['reason']];
        }
        
        if (!$status['subscription']['active']) {
            return ['allowed' => false, 'reason' => 'subscription_inactive'];
        }
        
        $channels = $status['limits']['whatsapp_channels'] ?? [];
        if (isset($channels['current'], $channels['max'])) {
            if ($channels['current'] >= $channels['max']) {
                return ['allowed' => false, 'reason' => 'limit_reached', 'current' => $channels['current'], 'max' => $channels['max']];
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Check feature permissions
     */
    public static function hasFeaturePermission($status, $feature)
    {
        $cacheCheck = self::validateCacheOrFail($status);
        if (!$cacheCheck['valid']) {
            return ['allowed' => false, 'reason' => $cacheCheck['reason']];
        }
        
        if (!$status['subscription']['active']) {
            return ['allowed' => false, 'reason' => 'subscription_inactive'];
        }
        
        $hasPermission = $status['permissions'][$feature] ?? false;
        
        return [
            'allowed' => $hasPermission,
            'reason' => $hasPermission ? null : 'feature_not_in_plan',
            'plan' => $status['subscription']['plan']
        ];
    }
    
    /**
     * Get user's current limits summary
     */
    public static function getLimitsSummary($status)
    {
        $cacheCheck = self::validateCacheOrFail($status);
        if (!$cacheCheck['valid']) {
            return ['valid' => false, 'reason' => $cacheCheck['reason']];
        }
        
        $limits = $status['limits'] ?? [];
        $plan = $status['subscription']['plan'] ?? 'unknown';
        
        return [
            'valid' => true,
            'plan' => $plan,
            'subscription_active' => $status['subscription']['active'] ?? false,
            'limits' => [
                'contacts' => [
                    'current' => $limits['contacts']['current'] ?? 0,
                    'max' => $limits['contacts']['max'] ?? 0,
                    'can_add' => $limits['contacts']['canAdd'] ?? false,
                    'percentage' => self::calculatePercentage($limits['contacts']['current'] ?? 0, $limits['contacts']['max'] ?? 1)
                ],
                'products' => [
                    'current' => $limits['products']['current'] ?? 0,
                    'max' => $limits['products']['max'] ?? 0,
                    'can_add' => $limits['products']['canAdd'] ?? false,
                    'percentage' => self::calculatePercentage($limits['products']['current'] ?? 0, $limits['products']['max'] ?? 1)
                ],
                'ai_credits' => [
                    'balance' => $limits['ai_credits']['balance'] ?? 0,
                    'can_use' => $limits['ai_credits']['canUse'] ?? false
                ],
                'whatsapp_channels' => [
                    'current' => $limits['whatsapp_channels']['current'] ?? 0,
                    'max' => $limits['whatsapp_channels']['max'] ?? 1
                ]
            ],
            'permissions' => $status['permissions'] ?? []
        ];
    }
    
    /**
     * Calculate usage percentage
     */
    private static function calculatePercentage($current, $max)
    {
        if ($max <= 0) return 0;
        return round(($current / $max) * 100, 1);
    }
    
    /**
     * REVENUE PROTECTION: Emergency validation for high-risk operations
     */
    public static function validateHighRiskOperation($status, $operation, $params = [])
    {
        $cacheCheck = self::validateCacheOrFail($status);
        if (!$cacheCheck['valid']) {
            Log::warning("High-risk operation {$operation} blocked due to cache failure");
            return ['allowed' => false, 'reason' => 'cache_validation_required'];
        }
        
        // Log high-risk operations for audit
        Log::info("High-risk operation attempted", [
            'customer_id' => $status['customer_id'],
            'operation' => $operation,
            'params' => $params,
            'plan' => $status['subscription']['plan'],
            'is_fallback' => $status['is_fallback'] ?? false
        ]);
        
        // Block operations during fallback mode for high-value operations
        if ($status['is_fallback'] ?? false) {
            if (in_array($operation, ['ai_bulk_processing', 'export_data', 'api_integration'])) {
                return ['allowed' => false, 'reason' => 'fallback_mode_restriction'];
            }
        }
        
        return ['allowed' => true];
    }
}