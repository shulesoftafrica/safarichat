<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
     * Get products catalog - returns configured product with plans and pricing
     */
    public function getProducts(Request $request)
    {
        try {
            $productCode = $request->get('product_code', 'safarichat');
            $currency = $request->get('currency', 'TZS');
            $activeOnly = $request->get('active_only', true);
            
            // Get cached configuration
            $config = Cache::get('safarichat_billing_config');
            if (!$config) {
                // Fallback to config file
                $config = config('safarichat_billing');
            }
            
            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product configuration not found. Please configure billing first.'
                ], 404);
            }
            
            // Build product response matching the POSTMAN API format
            $product = [
                'product_code' => $productCode,
                'name' => 'SafariChat Business Communication Platform',
                'description' => 'Complete WhatsApp business communication and customer management platform',
                'default_currency' => $currency,
                'subscription_enabled' => true,
                'wallet_enabled' => true,
                'plans' => [],
                'wallet_types' => ['ai_credits', 'sms', 'whatsapp_messages'],
                'entitlements' => [
                    'max_contacts' => 400,
                    'max_products' => 200,
                    'max_channels' => 7,
                    'storage_gb' => 50,
                    'api_calls_per_month' => 50000
                ],
                'metadata' => [
                    'category' => 'business_communication',
                    'target_market' => 'SME businesses',
                    'region' => 'East Africa'
                ]
            ];
            
            // Add plans from configuration
            if (isset($config['plans'])) {
                foreach ($config['plans'] as $planCode => $planConfig) {
                    if (!$activeOnly || ($planConfig['active'] ?? true)) {
                        $product['plans'][$planCode] = [
                            'name' => ucfirst($planCode) . ' Plan',
                            'price' => $planConfig['price'] ?? 0,
                            'currency' => $planConfig['currency'] ?? $currency,
                            'billing_cycle' => $planConfig['billing_cycle'] ?? 'monthly',
                            'features' => $this->getPlanFeatures($planCode),
                            'limits' => $planConfig['limits'] ?? []
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'products' => [$product]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get products catalog: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products catalog'
            ], 500);
        }
    }
    
    /**
     * Get plan features based on plan code
     */
    private function getPlanFeatures($planCode)
    {
        $features = [
            'trial' => ['basic_messaging', 'contact_management', 'single_channel'],
            'starter' => ['unlimited_messaging', 'contact_management', 'single_channel', 'basic_ai'],
            'pro' => ['unlimited_messaging', 'contact_management', 'multi_channel', 'advanced_ai', 'customer_followups', 'sales_reports'],
            'premium' => ['unlimited_messaging', 'contact_management', 'multi_channel', 'advanced_ai', 'customer_followups', 'sales_reports', 'booking_calendars', 'custom_integrations']
        ];
        
        return $features[$planCode] ?? $features['trial'];
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
            $channelsCount = DB::table('whatsapp_instances')->where('user_id', $user->id)->count() ?: 1;
            
            // Get billing account and AI credits
            $billingAccount = $user->billingAccount;
            $aiCredits = $billingAccount ? $billingAccount->ai_credits : 0;
            
            // Determine current plan and limits
            $plan = $billingAccount ? $billingAccount->subscription_plan : 'trial';
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
            
            // Get current balance from billing account
            $serverBalance = \App\Services\BillingService::getRemainingCredits($user);
            
            // Calculate total deductions
            $totalDeductions = collect($deductions)->sum('amount');
            
            // Apply deductions via BillingService
            if ($totalDeductions > 0) {
                \App\Services\BillingService::deductCredits($user, $totalDeductions, 'Synced credit deductions');
            }
            
            $newServerBalance = max(0, $serverBalance - $totalDeductions);
            
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
            
            // Get credits from billing account
            $availableCredits = \App\Services\BillingService::getRemainingCredits($user);
            
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
        $billingAccount = $user->billingAccount;
        $expiryDate = $billingAccount ? $billingAccount->subscription_expires_at : null;
        
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
        $billingAccount = $user->billingAccount;
        $expiryDate = $billingAccount ? $billingAccount->subscription_expires_at : null;
        
        return $expiryDate ? $expiryDate->toISOString() : now()->addDays(3)->toISOString(); // Default 3 days for trial
    }
    
    /**
     * Upgrade user to a new plan
     */
    public function upgradePlan(Request $request)
    {
        $user = Auth::user();
        $planCode = $request->input('plan_code');
        $amount = $request->input('amount');
        $feature = $request->input('feature');
        
        try {
            // Validate plan code
            $validPlans = ['starter', 'pro', 'premium'];
            if (!in_array($planCode, $validPlans)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid plan selected'
                ], 400);
            }
            
            // Check if this is actually an upgrade
            $billingAccount = $user->billingAccount;
            $currentPlan = $billingAccount ? $billingAccount->subscription_plan : 'trial';
            $planHierarchy = ['trial' => 0, 'starter' => 1, 'pro' => 2, 'premium' => 3];
            
            if ($planHierarchy[$planCode] <= $planHierarchy[$currentPlan]) {
                return response()->json([
                    'success' => false,
                    'message' => 'Selected plan is not an upgrade from your current plan'
                ], 400);
            }
            
            // Call billing API to create plan upgrade invoice
            $billingApiUrl = config('services.billing.api_url', 'http://localhost/shulesoft_newversion/api/billing');
            $accessToken = config('services.billing.access_token');
            
            $invoiceData = [
                'product_code' => 'safarichat',
                'invoice_type' => 'plan_upgrade',
                'customer' => [
                    'name' => $user->business->name ?? $user->name,
                    'phone' => $user->business->phone ?? $user->phone ?? '',
                    'email' => $user->email 
                        ?? $user->business->email 
                        ?? ('safarichat.' . $user->id . '@safarichat.africa')
                ],
                'amount' => $amount,
                'currency' => 'TZS',
                'old_plan_code' => $currentPlan,
                'new_plan_code' => $planCode,
                'feature_code' => $feature ?? 'core',
                'proration_credit' => 0, // Calculate if needed
                'success_url' => route('billing.success', ['plan' => $planCode]),
                'cancel_url' => route('billing.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'business_id' => $user->business_id ?? null,
                    'upgrade_timestamp' => now()->toISOString()
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($billingApiUrl . '/create-invoice', $invoiceData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Plan upgrade invoice created successfully', [
                    'user_id' => $user->id,
                    'old_plan' => $currentPlan,
                    'new_plan' => $planCode,
                    'invoice_id' => $responseData['data']['invoice_id'] ?? 'unknown',
                    'amount' => $amount
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Invoice created for {$planCode} plan upgrade",
                    'payment_url' => $responseData['data']['payment_url'] ?? route('billing.payment', [
                        'plan_code' => $planCode,
                        'amount' => $amount,
                        'feature' => $feature
                    ]),
                    'invoice_id' => $responseData['data']['invoice_id'] ?? null,
                    'plan' => $planCode,
                    'amount' => $amount
                ]);
            } else {
                // Billing API failed, fall back to local payment page
                Log::warning('Billing API failed, using local payment flow', [
                    'user_id' => $user->id,
                    'api_response' => $response->body(),
                    'status_code' => $response->status()
                ]);

                $paymentUrl = route('billing.payment', [
                    'plan_code' => $planCode,
                    'amount' => $amount,
                    'feature' => $feature
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Redirecting to payment for {$planCode} plan upgrade",
                    'payment_url' => $paymentUrl,
                    'plan' => $planCode,
                    'amount' => $amount
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Plan upgrade initiation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            // Fall back to local payment page on error
            $paymentUrl = route('billing.payment', [
                'plan_code' => $planCode,
                'amount' => $amount,
                'feature' => $feature
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Redirecting to payment for {$planCode} plan upgrade",
                'payment_url' => $paymentUrl,
                'plan' => $planCode,
                'amount' => $amount
            ]);
        }
    }
    
    /**
     * Purchase additional credits
     */
    public function purchaseCredits(Request $request)
    {
        $user = Auth::user();
        $amount = $request->input('amount');
        
        try {
            // Validate amount
            if (!$amount || $amount < 1000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimum credit amount is TZS 1,000'
                ], 400);
            }
            
            // Calculate credits (1 TZS = 1 credit for simplicity)
            $credits = $amount;
            
            // Add credits to billing account (single source of truth)
            \App\Services\BillingService::addCredits($user, $credits, "Credit purchase - TZS {$amount}");
            
            // Get updated balance
            $newBalance = \App\Services\BillingService::getRemainingCredits($user);
            
            Log::info('Credits purchased', [
                'user_id' => $user->id,
                'amount' => $amount,
                'credits_added' => $credits,
                'new_balance' => $newBalance
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully added {$credits} AI credits!",
                'credits_added' => $credits,
                'new_balance' => $newBalance
            ]);
            
        } catch (\Exception $e) {
            Log::error('Credit purchase failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to purchase credits. Please try again.'
            ], 500);
        }
    }

    /**
     * Get current user's billing status (for /api/billing/status endpoint)
     */
    public function getBillingStatus(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $customerId = $user->customer_id ?? $user->id;
            
            // Use the existing getCompleteStatus method
            return $this->getCompleteStatus($customerId);
            
        } catch (\Exception $e) {
            Log::error('Failed to get billing status', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve billing status'
            ], 500);
        }
    }

    /**
     * Get available product plans (for /api/billing/plans endpoint)
     */
    public function getProductInfo(Request $request)
    {
        try {
            $plans = [
                'trial' => [
                    'name' => 'Trial Plan',
                    'price' => 0,
                    'currency' => 'TZS',
                    'duration_days' => 3,
                    'features' => [
                        'max_contacts' => 10,
                        'max_products' => 1,
                        'whatsapp_channels' => 1,
                        'ai_credits' => 1000,
                        'customer_followups' => false,
                        'customer_categorization' => false,
                        'booking_calendars' => false,
                        'sales_reports' => false
                    ]
                ],
                'starter' => [
                    'name' => 'Starter Plan',
                    'price' => 69000,
                    'currency' => 'TZS',
                    'billing_cycle' => 'monthly',
                    'features' => [
                        'max_contacts' => 50,
                        'max_products' => 5,
                        'whatsapp_channels' => 1,
                        'ai_credits' => 69000,
                        'customer_followups' => false,
                        'customer_categorization' => false,
                        'booking_calendars' => false,
                        'sales_reports' => false,
                        'unlimited_messages' => true
                    ]
                ],
                'pro' => [
                    'name' => 'Pro Plan',
                    'price' => 149000,
                    'currency' => 'TZS',
                    'billing_cycle' => 'monthly',
                    'features' => [
                        'max_contacts' => 150,
                        'max_products' => 50,
                        'whatsapp_channels' => 3,
                        'ai_credits' => 149000,
                        'customer_followups' => true,
                        'customer_categorization' => true,
                        'booking_calendars' => false,
                        'sales_reports' => true,
                        'unlimited_messages' => true
                    ]
                ],
                'premium' => [
                    'name' => 'Premium Plan',
                    'price' => 299000,
                    'currency' => 'TZS',
                    'billing_cycle' => 'monthly',
                    'features' => [
                        'max_contacts' => 400,
                        'max_products' => 200,
                        'whatsapp_channels' => 7,
                        'ai_credits' => 299000,
                        'customer_followups' => true,
                        'customer_categorization' => true,
                        'booking_calendars' => true,
                        'sales_reports' => true,
                        'unlimited_messages' => true
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'plans' => $plans,
                    'product_code' => 'safarichat',
                    'token_pricing' => [
                        'tokens_per_credit' => 3.846,
                        'cost_per_token_input' => 0.0015,
                        'cost_per_token_output' => 0.002
                    ]
                ],
                'message' => 'Plans retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get product info', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve plan information'
            ], 500);
        }
    }

    /**
     * Get wallet information including balance and UCN reference
     */
    public function getWalletInfo(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $billingAccount = $user->billingAccount;
            $balance = $billingAccount ? $billingAccount->ai_credits : 0;

            // Get or create UCN reference from billing platform
            $billingApiUrl = config('services.billing.api_url', 'http://localhost/shulesoft_newversion/api/billing');
            $accessToken = config('services.billing.access_token');

            try {
                // Try to fetch existing wallet from billing platform
                $customerId = $user->id;
                $productCode = 'safarichat';

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->get("{$billingApiUrl}/wallet/{$customerId}", [
                    'product_code' => $productCode
                ]);

                $ucnReference = null;

                if ($response->successful() && isset($response->json()['data'])) {
                    $walletData = $response->json()['data'];
                    
                    // Look for ai_credits wallet
                    if (isset($walletData['wallets'])) {
                        foreach ($walletData['wallets'] as $wallet) {
                            if ($wallet['wallet_type'] === 'ai_credits' && $wallet['product_code'] === 'safarichat') {
                                $ucnReference = $wallet['ucn_reference'] ?? null;
                                break;
                            }
                        }
                    }
                }

                // If no UCN reference found, create wallet
                if (!$ucnReference) {
                    $createResponse = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json'
                    ])->post("{$billingApiUrl}/wallet/create", [
                        'student_id' => $customerId,
                        'product_code' => $productCode,
                        'wallet_type' => 'ai_credits',
                        'customer' => [
                            'name' => $user->name,
                            'phone' => $user->phone ?? '',
                            'email' => $user->email
                        ]
                    ]);

                    if ($createResponse->successful() && isset($createResponse->json()['data']['ucn_reference'])) {
                        $ucnReference = $createResponse->json()['data']['ucn_reference'];
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => [
                        'balance' => $balance,
                        'ucn_reference' => $ucnReference,
                        'wallet_type' => 'ai_credits',
                        'currency' => 'TZS'
                    ]
                ]);

            } catch (\Exception $apiError) {
                Log::warning('Failed to fetch UCN from billing platform, using local balance only', [
                    'error' => $apiError->getMessage()
                ]);

                // Return local balance without UCN reference
                return response()->json([
                    'success' => true,
                    'data' => [
                        'balance' => $balance,
                        'ucn_reference' => null,
                        'wallet_type' => 'ai_credits',
                        'currency' => 'TZS'
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to get wallet info', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve wallet information'
            ], 500);
        }
    }

    /**
     * Top up wallet via payment gateway
     */
    public function topUpWallet(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $amount = $request->input('amount');
            $paymentMethod = $request->input('payment_method', 'stripe');
            $walletType = $request->input('wallet_type', 'ai_credits');

            // Validate amount
            if (!$amount || $amount < 1000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Minimum top-up amount is TZS 1,000'
                ], 400);
            }

            // Calculate units (credits) - 1 TZS = 1 credit
            $units = $amount;
            $unitPrice = 1;

            // Create invoice via billing platform
            $billingApiUrl = config('services.billing.api_url', 'http://localhost/shulesoft_newversion/api/billing');
            $accessToken = config('services.billing.access_token');

            $invoiceData = [
                'product_code' => 'safarichat',
                'invoice_type' => 'wallet_topup',
                'customer' => [
                    'name' => $user->name,
                    'phone' => $user->phone ?? '',
                    'email' => $user->email
                ],
                'amount' => $amount,
                'currency' => 'TZS',
                'units' => $units,
                'unit_price' => $unitPrice,
                'wallet_type' => $walletType,
                'success_url' => route('billing.success', ['type' => 'wallet_topup']),
                'cancel_url' => route('billing.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'payment_method' => $paymentMethod,
                    'timestamp' => now()->toISOString()
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($billingApiUrl . '/create-invoice', $invoiceData);

            if ($response->successful()) {
                $responseData = $response->json();
                
                Log::info('Wallet top-up invoice created', [
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'units' => $units,
                    'invoice_id' => $responseData['data']['invoice']['invoice_id'] ?? 'unknown'
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Top-up initiated successfully',
                    'data' => [
                        'invoice_id' => $responseData['data']['invoice']['invoice_id'] ?? null,
                        'payment_url' => $responseData['data']['payment_session']['payment_url'] ?? null,
                        'amount' => $amount,
                        'credits' => $units
                    ]
                ]);
            } else {
                throw new \Exception('Billing API returned error: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error('Failed to initiate wallet top-up', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate top-up. Please try again.'
            ], 500);
        }
    }
}