<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use App\Services\BillingService;
use App\Services\BillingCacheManager;
use App\Models\Conversation;

class AdminController extends Controller
{
    private $billingService;
    
    public function __construct()
    {
        // Initialize billing service if it exists
        if (class_exists('\App\Services\BillingService')) {
            $this->billingService = new BillingService();
        }
    }
    
    public function showLogin()
    {
        if (session('admin_logged_in')) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.login');
    }
    
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        
        // Simple hardcoded authentication
        if ($request->username === 'admin' && $request->password === 'safari123') {
            session(['admin_logged_in' => true]);
            
            Log::info('Admin logged in', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now(),
            ]);
            
            return redirect()->route('admin.dashboard');
        }
        
        return back()->withErrors(['Invalid credentials']);
    }
    
    public function logout()
    {
        session()->forget('admin_logged_in');
        return redirect('/admin')->with('message', 'Logged out successfully');
    }
    
    public function dashboard()
    {
        try {
            $stats = $this->getSystemStats();
            $health = $this->getSystemHealth();
            $currentPricing = $this->getCurrentPricing();
            $products  = $this->billingService ? $this->billingService->getProducts() : [];

            return view('admin.dashboard', compact('stats', 'health', 'currentPricing','products'));
        } catch (\Exception $e) {
            Log::error('Admin dashboard error: ' . $e->getMessage());
            
            // Fallback stats if there's an error
            $stats = [
                'total_customers' => 0,
                'trial_customers' => 0,
                'paid_customers' => 0,
                'churned_customers' => 0,
                'total_collections' => 0,
                'new_customers_30d' => 0,
                'total_conversations' => 0,
                'conversations_today' => 0,
                'total_input_tokens' => 0,
                'total_output_tokens' => 0,
            ];
            
            $health = [
                'database_size' => 'Unknown',
                'total_tables' => 0,
                'total_records' => [],
                'php_memory_usage' => 'Unknown',
                'php_memory_limit' => 'Unknown',
                'laravel_version' => 'Unknown',
                'recent_errors' => [],
                'failed_jobs' => 0,
                'billing_status' => ['cache_working' => false],
            ];
            
            $currentPricing = $this->getCurrentPricing();
            
            return view('admin.dashboard', compact('stats', 'health', 'currentPricing'));
        }
    }
    
    private function getSystemStats()
    {
        return [
            // Customer metrics
            'total_customers' => DB::table('users')->count(),
            'trial_customers' => DB::table('users')->where('subscription_plan', 'trial')->count(),
            'paid_customers' => DB::table('users')->whereIn('subscription_plan', ['starter', 'pro', 'premium'])->count(),
            'churned_customers' => DB::table('users')->where('subscription_status', 'cancelled')->count(),
            
            // Revenue metrics (mock data - replace with actual billing tables)
            'total_collections' => rand(500000, 2000000),
            'new_customers_30d' => DB::table('users')->where('created_at', '>=', now()->subDays(30))->count(),
            
            // AI Usage metrics
            'total_conversations' => DB::table('conversations')->count(),
            'conversations_today' => DB::table('conversations')->whereDate('created_at', today())->count(),
            'total_input_tokens' => DB::table('conversations')->sum('input_tokens') ?? 0,
            'total_output_tokens' => DB::table('conversations')->sum('output_tokens') ?? 0,
        ];
    }
    
    private function getSystemHealth()
    {
        return [
            // Database health
            'database_size' => $this->getDatabaseSize(),
            'total_tables' => count($this->getDatabaseTables()),
            'total_records' => [
                'leads' => DB::table('leads')->count() ?? 0,
                'conversations' => DB::table('conversations')->count(),
                'businesses' => DB::table('businesses')->count() ?? 0,
                'whatsapp_instances' => DB::table('whatsapp_instances')->count() ?? 0,
            ],
            
            // System performance
            'php_memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'php_memory_limit' => ini_get('memory_limit'),
            'laravel_version' => app()->version(),
            
            // Error monitoring
            'recent_errors' => $this->getRecentErrors(),
            'failed_jobs' => DB::table('failed_jobs')->count() ?? 0,
            
            // Billing system status
            'billing_status' => $this->getBillingSystemStatus(),
        ];
    }
    
    private function getDatabaseSize()
    {
        try {
            $result = DB::select("
                SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            return $result[0]->size_mb . ' MB';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }
    
    private function getDatabaseTables()
    {
        try {
            return DB::select('SHOW TABLES');
        } catch (\Exception $e) {
            return [];
        }
    }
    
    private function getRecentErrors()
    {
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) return [];
        
        try {
            $lines = array_slice(file($logFile), -100);
            $errors = [];
            
            foreach ($lines as $line) {
                if (strpos($line, '[ERROR]') !== false || strpos($line, 'CRITICAL') !== false) {
                    $errors[] = substr($line, 0, 200) . '...';
                }
            }
            
            return array_slice($errors, -10); // Last 10 errors
        } catch (\Exception $e) {
            return ['Error reading log file: ' . $e->getMessage()];
        }
    }
    
    private function getBillingSystemStatus()
    {
        try {
            // Test billing cache
            $testCache = cache()->get('billing_test', null);
            cache()->put('billing_test', 'working', 60);
            
            return [
                'cache_working' => true,
                'last_sync' => now()->format('Y-m-d H:i:s'),
                'pending_syncs' => rand(0, 5), // Mock data
            ];
        } catch (\Exception $e) {
            return [
                'cache_working' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    public function updatePricing(Request $request)
    {
        $request->validate([
            'price_per_message' => 'required|numeric|min:0',
            'price_per_month' => 'required|numeric|min:0',
            'free_messages_limit' => 'required|integer|min:0',
            'starter_price' => 'required|numeric|min:0',
            'pro_price' => 'required|numeric|min:0',
            'premium_price' => 'required|numeric|min:0',
        ]);

        try {
            // Try to update billing API, but don't fail if it's unavailable
            $billingApiResult = $this->updateBillingAPI($request);
            $apiSuccessful = false;
            
            if ($billingApiResult['success']) {
                $apiSuccessful = true;
            } else {
                Log::warning('Billing API update failed, continuing with database update: ' . $billingApiResult['message']);
            }
            
            // Always save to database for persistence
            $this->savePricingToDatabase($request);
            
            // Update runtime config values
            config(['billing.price_per_message' => $request->price_per_message]);
            config(['billing.price_per_month' => $request->price_per_month]);
            config(['billing.free_messages_limit' => $request->free_messages_limit]);
            config(['billing.starter_price' => $request->starter_price]);
            config(['billing.pro_price' => $request->pro_price]);
            config(['billing.premium_price' => $request->premium_price]);
            
            // Clear billing caches to force refresh with new pricing
            cache()->forget('pricing_config');
            $this->clearAllBillingCaches();
            
            // Log the pricing change
            Log::info('Admin updated pricing', [
                'admin_ip' => $request->ip(),
                'api_successful' => $apiSuccessful,
                'changes' => $request->only(['price_per_message', 'price_per_month', 'free_messages_limit', 'starter_price', 'pro_price', 'premium_price']),
                'timestamp' => now(),
            ]);
            
            // Different success messages based on API status
            if ($apiSuccessful) {
                return redirect()->route('admin.dashboard')
                                ->with('success', 'Pricing updated in Billing API and database! All users will see new pricing immediately.');
            } else {
                return redirect()->route('admin.dashboard')
                                ->with('warning', 'Pricing updated in database successfully, but Billing API sync failed. System will work with local pricing.');
            }
                            
        } catch (\Exception $e) {
            Log::error('Pricing update failed: ' . $e->getMessage());
            
            return redirect()->route('admin.dashboard')
                            ->with('error', 'Failed to update pricing: ' . $e->getMessage());
        }
    }
    
    private function savePricingToDatabase($request)
    {
        $pricingData = [
            'price_per_message' => $request->price_per_message,
            'price_per_month' => $request->price_per_month,
            'free_messages_limit' => $request->free_messages_limit,
            'starter_price' => $request->starter_price,
            'pro_price' => $request->pro_price,
            'premium_price' => $request->premium_price,
            'updated_at' => now(),
            'updated_by' => 'admin'
        ];
        
        // Store in settings table or create a pricing config record
        foreach ($pricingData as $key => $value) {
            if ($key !== 'updated_at' && $key !== 'updated_by') {
                DB::table('settings')->updateOrInsert(
                    ['key' => "billing.{$key}"],
                    ['value' => $value, 'updated_at' => now()]
                );
            }
        }
        
        // Store in cache for fast access
        cache()->put('pricing_config', $pricingData, 86400); // Cache for 24 hours
    }
    
    private function clearAllBillingCaches()
    {
        // Clear all potential billing caches
        $cacheKeys = [
            'billing_status_',
            'pricing_config',
            'plan_limits',
            'billing_test'
        ];
        
        foreach ($cacheKeys as $keyPattern) {
            if (strpos($keyPattern, '_') !== false) {
                // For patterns like 'billing_status_', we need to clear all matching keys
                // This is a simplified approach - in production you'd want more sophisticated cache clearing
                cache()->flush();
                break;
            } else {
                cache()->forget($keyPattern);
            }
        }
    }
    
    private function updateBillingAPI($request)
    {
        try {
            $billingApiUrl = config('app.url') . '/api/billing/configure-product';
            
            $planData = [
                'trial' => [
                    'price' => 0,
                    'limits' => [
                        'messages' => $request->free_messages_limit,
                        'contacts' => 50,
                        'products' => 5,
                        'whatsapp_channels' => 1
                    ]
                ],
                'starter' => [
                    'price' => $request->starter_price,
                    'limits' => [
                        'messages' => 1000,
                        'contacts' => 200,
                        'products' => 25,
                        'whatsapp_channels' => 2
                    ]
                ],
                'pro' => [
                    'price' => $request->pro_price,
                    'limits' => [
                        'messages' => 5000,
                        'contacts' => 1000,
                        'products' => 100,
                        'whatsapp_channels' => 5
                    ]
                ],
                'premium' => [
                    'price' => $request->premium_price,
                    'limits' => [
                        'messages' => -1, // unlimited
                        'contacts' => -1,
                        'products' => -1,
                        'whatsapp_channels' => -1
                    ]
                ]
            ];
            
            $response = Http::timeout(10)->post($billingApiUrl, [
                'product_code' => 'safarichat',
                'plans' => $planData,
                'token_pricing' => [
                    'price_per_message' => $request->price_per_message,
                    'price_per_month' => $request->price_per_month,
                    'free_messages_limit' => $request->free_messages_limit
                ]
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'message' => 'Billing API updated successfully',
                    'data' => $data
                ];
            } else {
                $statusCode = $response->status();
                $responseBody = $response->body();
                
                // Handle 404 specifically
                if ($statusCode === 404) {
                    return [
                        'success' => false,
                        'message' => 'Billing API endpoint not found (404). API may not be deployed or URL incorrect: ' . $billingApiUrl
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => "Billing API returned error ({$statusCode}): " . substr($responseBody, 0, 200)
                ];
            }
            
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return [
                'success' => false,
                'message' => 'Failed to connect to billing API (connection error): ' . $e->getMessage()
            ];
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return [
                'success' => false,
                'message' => 'Billing API request failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Unexpected error calling billing API: ' . $e->getMessage()
            ];
        }
    }
    
    public function getCurrentPricing()
    {
        // First try to load from billing API
        try {
            $billingApiUrl = config('app.url') . '/api/billing/get-pricing';
            $response = Http::timeout(5)->get($billingApiUrl);
            
            if ($response->successful()) {
                $apiData = $response->json();
                if (isset($apiData['success']) && $apiData['success']) {
                    // Convert billing API format to our format
                    return $this->convertBillingApiPricing($apiData['data']);
                }
            } else {
                Log::info('Billing API returned error, using database fallback: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::info('Failed to load pricing from billing API, using database: ' . $e->getMessage());
        }
        
        // Fallback to database/cache
        return $this->getCurrentPricingFromDB();
    }
    
    private function convertBillingApiPricing($billingData)
    {
        return [
            'price_per_message' => $billingData['token_pricing']['price_per_message'] ?? 100,
            'price_per_month' => $billingData['token_pricing']['price_per_month'] ?? 15000,
            'free_messages_limit' => $billingData['token_pricing']['free_messages_limit'] ?? 100,
            'starter_price' => $billingData['plans']['starter']['price'] ?? 15000,
            'pro_price' => $billingData['plans']['pro']['price'] ?? 45000,
            'premium_price' => $billingData['plans']['premium']['price'] ?? 85000,
        ];
    }
    
    private function getCurrentPricingFromDB()
    {
        // Try cache first
        $cached = cache()->get('pricing_config');
        if ($cached) {
            return $cached;
        }
        
        // Load from database
        $pricing = [];
        $settings = DB::table('settings')->where('key', 'like', 'billing.%')->get();
        
        foreach ($settings as $setting) {
            $key = str_replace('billing.', '', $setting->key);
            $pricing[$key] = $setting->value;
        }
        
        // Set defaults if not found
        $defaults = [
            'price_per_message' => 100,
            'price_per_month' => 15000,
            'free_messages_limit' => 100,
            'starter_price' => 15000,
            'pro_price' => 45000,
            'premium_price' => 85000,
        ];
        
        $pricing = array_merge($defaults, $pricing);
        
        // Cache for future requests
        cache()->put('pricing_config', $pricing, 86400);
        
        return $pricing;
    }
    
    // Billing API sync endpoints
    public function syncFromBillingAPI()
    {
        try {
            // Since the billing API endpoints don't exist yet, 
            // we'll refresh the database pricing and clear caches
            $currentPricing = $this->getCurrentPricingFromDB();
            
            // Clear all billing caches to force fresh data
            cache()->forget('pricing_config');
            $this->clearAllBillingCaches();
            
            // Re-cache the current pricing
            cache()->put('pricing_config', $currentPricing, 86400);
            
            Log::info('Admin manually synced pricing (API not available, used database fallback)');
            
            return response()->json([
                'success' => true,
                'message' => 'Pricing refreshed from database (Billing API not available)',
                'data' => $currentPricing
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error refreshing pricing data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error refreshing pricing data: ' . $e->getMessage()
            ]);
        }
    }
    
    public function testBillingAPI()
    {
        try {
            // Test if billing API endpoints would be accessible
            $billingApiUrl = config('app.url') . '/api/billing/get-pricing';
            $response = Http::timeout(5)->get($billingApiUrl);
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'status_code' => $response->status(),
                    'response_time' => 'Fast',
                    'data' => $response->json(),
                    'message' => 'Billing API is working'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status_code' => $response->status(),
                    'message' => 'Billing API endpoints not available (404). System working with database fallback.',
                    'fallback_status' => 'Database pricing active'
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Billing API not accessible: ' . $e->getMessage(),
                'fallback_status' => 'Database pricing active'
            ]);
        }
    }
    
    public function syncAllCustomers()
    {
        try {
            // Get all customers and trigger billing refresh
            $customers = DB::table('users')->pluck('id');
            $synced = 0;
            $errors = [];
            
            foreach ($customers as $customerId) {
                try {
                    BillingCacheManager::forceRefresh($customerId);
                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = "Customer {$customerId}: " . $e->getMessage();
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => "Synced {$synced} customers successfully",
                'synced_count' => $synced,
                'error_count' => count($errors),
                'errors' => array_slice($errors, 0, 10) // Show first 10 errors
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync customers: ' . $e->getMessage()
            ]);
        }
    }
    
    public function refreshBillingCache()
    {
        try {
            // Clear all billing-related caches
            cache()->flush();
            
            // Clear specific billing caches if service is available
            if ($this->billingService) {
                // This would clear all customer billing caches
                $customers = DB::table('users')->pluck('id');
                foreach ($customers as $customerId) {
                    cache()->forget('billing_status_' . $customerId);
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Billing cache refreshed successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache: ' . $e->getMessage()
            ]);
        }
    }
    
    public function clearCache(Request $request)
    {
        try {
            // Clear application cache
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            // Clear billing cache specifically if service exists
            if ($this->billingService && method_exists($this->billingService, 'clearCache')) {
                // Clear cache for all users - we'll do a general cache clear instead
                cache()->flush(); // Clear all cache including billing
            }
            
            Log::info('Admin cleared system cache', [
                'admin_ip' => $request->ip(),
                'timestamp' => now(),
            ]);
            
            return redirect()->route('admin.dashboard')
                            ->with('success', 'All caches cleared successfully!');
        } catch (\Exception $e) {
            Log::error('Cache clear error: ' . $e->getMessage());
            
            return redirect()->route('admin.dashboard')
                            ->with('error', 'Error clearing cache: ' . $e->getMessage());
        }
    }
}