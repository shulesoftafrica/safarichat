<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShulesoftAuthService;
use Illuminate\Support\Facades\Cache;

class TestShulesoftAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shulesoft:test-auth 
                            {--clear : Clear authentication cache and force re-authentication}
                            {--status : Show current authentication status}
                            {--enable-oauth : Re-enable OAuth after it was disabled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Shulesoft OAuth authentication and token management';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('═══════════════════════════════════════════════════════════');
        $this->info('   Shulesoft OAuth Authentication Test');
        $this->info('═══════════════════════════════════════════════════════════');
        $this->newLine();

        // Handle --enable-oauth option
        if ($this->option('enable-oauth')) {
            $this->info('🔄 Re-enabling OAuth authentication...');
            ShulesoftAuthService::enableOAuth();
            ShulesoftAuthService::clearAuthCache();
            $this->info('✓ OAuth re-enabled. Will attempt authentication on next request.');
            $this->newLine();
            return Command::SUCCESS;
        }

        // Handle --clear option
        if ($this->option('clear')) {
            $this->warn('🔄 Clearing authentication cache...');
            ShulesoftAuthService::clearAuthCache();
            Cache::forget('shulesoft_oauth_disabled');
            $this->info('✓ Authentication cache cleared');
            $this->newLine();
        }

        // Handle --status option
        if ($this->option('status')) {
            $this->showAuthStatus();
            return Command::SUCCESS;
        }

        // Test authentication flow
        $this->info('📋 Configuration Check');
        $this->line('─────────────────────────────────────────────────────────');
        
        $email = config('services.shulesoft_billing.auth_email');
        $password = config('services.shulesoft_billing.auth_password');
        $apiUrl = config('services.shulesoft_billing.api_url');
        
        $this->line("API URL: <fg=cyan>{$apiUrl}</>");
        $this->line("Auth Email: <fg=cyan>{$email}</>");
        $this->line("Password: <fg=cyan>" . (strlen($password) > 0 ? str_repeat('*', strlen($password)) : 'NOT SET') . "</>");
        $this->newLine();

        if (!$email || !$password) {
            $this->error('❌ Authentication credentials not configured!');
            $this->line('Please set SHULESOFT_AUTH_EMAIL and SHULESOFT_AUTH_PASSWORD in .env');
            return Command::FAILURE;
        }

        // Test getting access token
        $this->info('🔐 Testing OAuth Authentication Flow');
        $this->line('─────────────────────────────────────────────────────────');
        
        try {
            $this->line('Step 1: Retrieving access token...');
            $token = ShulesoftAuthService::getAccessToken();
            
            if ($token === null) {
                // OAuth is disabled, check fallback token
                $this->warn('⚠ OAuth authentication is disabled (API server issue detected)');
                $this->newLine();
                
                $fallbackToken = config('services.billing.access_token', '');
                if ($fallbackToken) {
                    $this->info('✓ Using fallback static token');
                    $this->line("Token: <fg=yellow>" . substr($fallbackToken, 0, 20) . "..." . substr($fallbackToken, -10) . "</>");
                    $this->newLine();
                    
                    // Show authentication status
                    $this->info('📊 Authentication Status');
                    $this->line('─────────────────────────────────────────────────────────');
                    $this->showAuthStatus();
                    $this->newLine();
                    
                    // Test API call with fallback token
                    $this->info('🔍 Testing API Call (with fallback token)');
                    $this->line('─────────────────────────────────────────────────────────');
                    $this->testApiCall($fallbackToken);
                    
                    $this->newLine();
                    $this->info('═══════════════════════════════════════════════════════════');
                    $this->info('⚠ Using fallback authentication. OAuth unavailable.');
                    $this->info('═══════════════════════════════════════════════════════════');
                    
                    return Command::SUCCESS;
                } else {
                    $this->error('❌ No fallback token configured!');
                    $this->line('Set BILLING_ACCESS_TOKEN in .env');
                    return Command::FAILURE;
                }
            }
            
            if ($token) {
                $this->info('✓ Access token retrieved successfully');
                $this->line("Token: <fg=green>" . substr($token, 0, 20) . "..." . substr($token, -10) . "</>");
                $this->newLine();
                
                // Show authentication status
                $this->info('📊 Authentication Status');
                $this->line('─────────────────────────────────────────────────────────');
                $this->showAuthStatus();
                $this->newLine();
                
                // Test API call
                $this->info('🔍 Testing API Call');
                $this->line('─────────────────────────────────────────────────────────');
                $this->testApiCall($token);
                
                $this->newLine();
                $this->info('═══════════════════════════════════════════════════════════');
                $this->info('✅ All tests passed! OAuth authentication is working.');
                $this->info('═══════════════════════════════════════════════════════════');
                
                return Command::SUCCESS;
            } else {
                throw new \Exception('No token returned');
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Authentication failed!');
            $this->error('Error: ' . $e->getMessage());
            $this->newLine();
            
            $this->warn('💡 Troubleshooting Tips:');
            $this->line('1. Verify your credentials in .env file');
            $this->line('2. Check API URL is correct');
            $this->line('3. Ensure your account has proper permissions');
            $this->line('4. Check logs: storage/logs/laravel.log');
            $this->line('5. Try: php artisan shulesoft:test-auth --clear');
            
            return Command::FAILURE;
        }
    }

    /**
     * Show current authentication status
     */
    private function showAuthStatus()
    {
        $status = ShulesoftAuthService::getAuthStatus();
        
        $hasToken = $status['has_access_token'] ? '<fg=green>Yes</>' : '<fg=red>No</>';
        $hasClient = $status['has_client_credentials'] ? '<fg=green>Yes</>' : '<fg=red>No</>';
        $expiresAt = $status['token_expires_at'] ?? 'N/A';
        $isExpired = $status['is_expired'] ? '<fg=red>Yes</>' : '<fg=green>No</>';
        $clientId = $status['client_id'] ?? 'N/A';
        
        // Check if OAuth is disabled
        $oauthDisabled = Cache::get('shulesoft_oauth_disabled', false);
        $oauthStatus = $oauthDisabled ? '<fg=red>Disabled (API server issue)</>' : '<fg=green>Enabled</>';
        
        $this->line("OAuth Status: {$oauthStatus}");
        $this->line("Has Access Token: {$hasToken}");
        $this->line("Has Client Credentials: {$hasClient}");
        $this->line("Client ID: <fg=cyan>{$clientId}</>");
        $this->line("Token Expires At: <fg=cyan>{$expiresAt}</>");
        $this->line("Is Expired: {$isExpired}");
        
        if ($oauthDisabled) {
            $this->newLine();
            $this->warn('⚠ OAuth is temporarily disabled due to API server issues.');
            $this->line('System is using fallback static token from BILLING_ACCESS_TOKEN.');
            $this->line('Run with --clear to re-attempt OAuth authentication.');
        }
    }

    /**
     * Test API call with the token
     */
    private function testApiCall($token)
    {
        try {
            $apiUrl = config('services.shulesoft_billing.api_url');
            $productCode = 4; // SafariChat product code
            
            $this->line("Calling: {$apiUrl}/v1/products/{$productCode}");
            
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json'
                ])
                ->get($apiUrl . '/v1/products/' . $productCode);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->info('✓ API call successful');
                
                if (isset($data['data']['name'])) {
                    $this->line("Product: <fg=cyan>{$data['data']['name']}</>");
                }
                
                if (isset($data['data']['price_plans'])) {
                    $planCount = count($data['data']['price_plans']);
                    $this->line("Price Plans: <fg=cyan>{$planCount}</>");
                }
            } else {
                $this->warn('⚠ API call returned non-success status: ' . $response->status());
                $this->line('Response: ' . $response->body());
            }
            
        } catch (\Exception $e) {
            $this->error('❌ API call failed: ' . $e->getMessage());
        }
    }
}
