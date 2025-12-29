<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Services\BillingService;
use App\Services\LocalBillingValidator;
use App\Services\LocalCreditManager;
use App\Models\User;
use App\Models\Business;

/**
 * Billing API Controller - SafariChat Revenue Protection System
 * Minimal API endpoints optimized for cache-local billing validation
 */
class BillingApiController extends Controller
{
    /**
     * ONE-TIME: Configure product and plans (setup only)
     */
    public function configureProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_code' => 'required|string',
            'plans' => 'required|array',
            'token_pricing' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Store configuration in cache and config file
            $config = $request->all();
            
            // Cache configuration
            Cache::put('safarichat_billing_config', $config, 86400 * 30); // 30 days
            
            // Also save to config file for persistence
            $configPath = config_path('safarichat_billing.php');
            file_put_contents($configPath, "<?php\nreturn " . var_export($config, true) . ";\n");
            
            Log::info('Billing configuration updated', ['product_code' => $config['product_code']]);
            
            return response()->json([
                'success' => true,
                'message' => 'Product configuration saved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to configure billing product: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Configuration failed'
            ], 500);
        }
    }
    
    /**
     * BOOT-TIME: Get complete customer billing status
     */
    public function getCompleteStatus($customerId)
    {
        try {
            // Get user and business info
            $user = User::find($customerId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            $business = $user->business;
            
            // Get current usage counts from database
            $contactsCount = DB::table('business_contacts')->where('business_id', $business->id)->count();
            $productsCount = DB::table('products')->where('business_id', $business->id)->count();
            $channelsCount = DB::table('whatsapp_channels')->where('business_id', $business->id)->count() ?: 1;
            
            // Get AI credits from user wallet or business
            $aiCredits = $user->ai_credits ?? $business->ai_credits ?? 0;
            
            // Determine current plan and limits
            $plan = $user->subscription_plan ?? $business->subscription_plan ?? 'trial';
            $planLimits = BillingService::getPlanLimits($plan);
            
            // Build comprehensive status response
            $status = [
                'customer_id' => $customerId,
                'business_id' => $business->id,
                
                'subscription' => [
                    'status' => $this->getSubscriptionStatus($user, $business),
                    'plan' => $plan,
                    'expires_at' => $this->getSubscriptionExpiry($user, $business),
                    'is_trial' => $plan === 'trial',
                    'can_use_ai' => $aiCredits > 0,
                    'can_send_messages' => true,
                    'auto_renewal' => $user->auto_renewal ?? false
                ],
                
                'limits' => [
                    'contacts' => [
                        'current' => $contactsCount,
                        'max' => $planLimits['max_contacts'],
                        'unlimited' => false
                    ],
                    'products' => [
                        'current' => $productsCount,
                        'max' => $planLimits['max_products'],
                        'unlimited' => false
                    ],
                    'whatsapp_channels' => [
                        'current' => $channelsCount,
                        'max' => $planLimits['whatsapp_channels'],
                        'unlimited' => false
                    ],
                    'outgoing_messages' => [
                        'current' => 0, // Would need tracking if limited
                        'max' => -1,
                        'unlimited' => true
                    ]
                ],
                
                'wallet' => [
                    'ai_credits' => $aiCredits,
                    'status' => 'active',
                    'last_updated' => now()->toISOString()
                ],
                
                'cache_info' => [
                    'expires_at' => now()->addHours(2)->toISOString(),
                    'refresh_triggers' => ['payment_received', 'plan_changed', 'subscription_expired']
                ]
            ];
            
            Log::info("Complete billing status loaded for customer {$customerId}", [
                'plan' => $plan,
                'contacts' => $contactsCount,
                'products' => $productsCount,
                'credits' => $aiCredits
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to get complete status for customer {$customerId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load billing status'
            ], 500);
        }
    }
    
    /**
     * REVENUE PROTECTED: Sync credit deductions from local operations
     */
    public function syncCredits(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer',
            'deductions' => 'required|array',
            'local_balance' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $customerId = $request->customer_id;
            $deductions = $request->deductions;
            $localBalance = $request->local_balance;
            
            // Get current server-side balance
            $user = User::find($customerId);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }
            
            $serverBalance = $user->ai_credits ?? 0;
            
            // Calculate total deductions
            $totalDeductions = collect($deductions)->sum('amount');
            
            // Apply deductions to server balance
            $newServerBalance = max(0, $serverBalance - $totalDeductions);
            
            // Update server balance
            $user->ai_credits = $newServerBalance;
            $user->save();
            
            // Log all deductions for audit
            foreach ($deductions as $deduction) {
                DB::table('credit_usage_log')->insert([
                    'customer_id' => $customerId,
                    'amount' => $deduction['amount'],
                    'description' => $deduction['description'],
                    'metadata' => json_encode($deduction['metadata'] ?? []),
                    'synced_at' => now(),
                    'created_at' => now()
                ]);
            }
            
            // REVENUE PROTECTION: Check for discrepancies
            $expectedBalance = $serverBalance - $totalDeductions;
            $balanceDiscrepancy = $localBalance - $expectedBalance;
            
            $response = [
                'success' => true,
                'server_balance' => $newServerBalance,
                'operations_synced' => count($deductions),
                'total_deducted' => $totalDeductions
            ];
            
            if (abs($balanceDiscrepancy) > 1) { // Allow small rounding differences
                $response['balance_correction'] = $balanceDiscrepancy;
                $response['corrected_balance'] = $expectedBalance;
                
                Log::warning("Credit balance discrepancy detected for customer {$customerId}", [
                    'local_balance' => $localBalance,
                    'expected_balance' => $expectedBalance,
                    'discrepancy' => $balanceDiscrepancy
                ]);
            }
            
            Log::info("Credits synced for customer {$customerId}", [
                'operations' => count($deductions),
                'total_deducted' => $totalDeductions,
                'new_balance' => $newServerBalance
            ]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error("Credit sync failed for customer {$customerId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Credit sync failed'
            ], 500);
        }
    }
    
    /**
     * REVENUE PROTECTION: Server-side credit verification for high-value operations
     */
    public function verifyCredits(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|integer',
            'credits_needed' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $customerId = $request->customer_id;
            $creditsNeeded = $request->credits_needed;
            
            $user = User::find($customerId);
            if (!$user) {
                return response()->json([
                    'allowed' => false,
                    'reason' => 'customer_not_found'
                ]);
            }
            
            $availableCredits = $user->ai_credits ?? 0;
            
            if ($availableCredits < $creditsNeeded) {
                Log::info("Server-side credit verification failed for customer {$customerId}", [
                    'needed' => $creditsNeeded,
                    'available' => $availableCredits
                ]);
                
                return response()->json([
                    'allowed' => false,
                    'reason' => 'insufficient_credits',
                    'available' => $availableCredits,
                    'needed' => $creditsNeeded
                ]);
            }
            
            return response()->json([
                'allowed' => true,
                'available' => $availableCredits
            ]);
            
        } catch (\Exception $e) {
            Log::error("Credit verification failed: " . $e->getMessage());
            
            return response()->json([
                'allowed' => false,
                'reason' => 'verification_error'
            ]);
        }
    }
    
    /**
     * Refresh customer status (manual refresh)
     */
    public function refreshStatus(Request $request)
    {
        $customerId = $request->customer_id;
        
        if (!$customerId) {
            return response()->json([
                'success' => false,
                'message' => 'Customer ID required'
            ], 400);
        }
        
        try {
            // Clear cache and reload
            BillingService::clearCache($customerId);
            $status = BillingService::loadCompleteStatus($customerId);
            
            return response()->json([
                'success' => true,
                'data' => $status,
                'message' => 'Status refreshed successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh failed'
            ], 500);
        }
    }
    
    /**
     * Emergency cache refresh
     */
    public function emergencyRefresh(Request $request)
    {
        $customerId = $request->customer_id;
        
        try {
            $status = BillingService::forceRefresh($customerId);
            
            // Remove emergency mode if it was active
            if (isset($status['emergency_mode'])) {
                unset($status['emergency_mode']);
                unset($status['emergency_reason']);
                unset($status['emergency_timestamp']);
                
                BillingService::clearCache($customerId);
                Cache::put('billing_status_' . $customerId, $status, BillingService::CACHE_DURATION);
            }
            
            return response()->json([
                'success' => true,
                'data' => $status,
                'message' => 'Emergency refresh completed'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Emergency refresh failed for customer {$customerId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Emergency refresh failed'
            ], 500);
        }
    }
    
    /**
     * Helper: Get subscription status
     */
    private function getSubscriptionStatus($user, $business)
    {
        $expiryDate = $user->subscription_expires_at ?? $business->subscription_expires_at ?? null;
        
        if (!$expiryDate || now()->greaterThan($expiryDate)) {
            return 'expired';
        }
        
        return 'active';
    }
    
    /**
     * Helper: Get subscription expiry
     */
    private function getSubscriptionExpiry($user, $business)
    {
        $expiryDate = $user->subscription_expires_at ?? $business->subscription_expires_at;
        
        return $expiryDate ? $expiryDate->toISOString() : now()->addDays(3)->toISOString(); // Default 3 days for trial
    }
}