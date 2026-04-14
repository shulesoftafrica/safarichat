<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ShulesoftAuthService;
use Illuminate\Support\Facades\Cache;

class CheckShulesoftAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shulesoft:auth-status 
                            {--reset : Clear authentication cache and retry}
                            {--enable : Enable OAuth and clear backoff}
                            {--test : Test authentication credentials}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Shulesoft OAuth authentication status and diagnose issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=================================================');
        $this->info('  Shulesoft OAuth Authentication Status');
        $this->info('=================================================');
        $this->newLine();

        if ($this->option('reset')) {
            return $this->resetAuth();
        }

        if ($this->option('enable')) {
            return $this->enableOAuth();
        }

        if ($this->option('test')) {
            return $this->testAuth();
        }

        $this->showStatus();
    }

    /**
     * Display current authentication status
     */
    private function showStatus()
    {
        $status = ShulesoftAuthService::getAuthStatus();

        // Configuration
        $this->info('📋 Configuration:');
        $this->line('   API URL: ' . config('services.shulesoft_billing.api_url'));
        $this->line('   Auth Email: ' . config('services.shulesoft_billing.auth_email'));
        $this->line('   Password Set: ' . (config('services.shulesoft_billing.auth_password') ? '✓ Yes' : '✗ No'));
        $this->newLine();

        // Token Status
        $this->info('🔑 Token Status:');
        $this->line('   Has Access Token: ' . ($status['has_access_token'] ? '✓ Yes' : '✗ No'));
        $this->line('   Has Client Credentials: ' . ($status['has_client_credentials'] ? '✓ Yes' : '✗ No'));
        
        if ($status['client_id']) {
            $this->line('   Client ID: ' . substr($status['client_id'], 0, 20) . '...');
        }
        
        if ($status['token_expires_at']) {
            $this->line('   Token Expires: ' . $status['token_expires_at']);
            $this->line('   Is Expired: ' . ($status['is_expired'] ? '✗ Yes' : '✓ No'));
        }
        $this->newLine();

        // Failure Status
        $this->info('⚠️  Failure Status:');
        $this->line('   Failure Count: ' . $status['failure_count']);
        
        if ($status['last_failure_at']) {
            $this->line('   Last Failure: ' . $status['last_failure_at']);
        }
        
        if ($status['in_backoff_period']) {
            $backoffMinutes = ceil($status['backoff_remaining_seconds'] / 60);
            $this->warn('   ⏳ IN BACKOFF PERIOD - ' . $backoffMinutes . ' minutes remaining');
            $this->warn('   OAuth authentication is paused to prevent repeated failures.');
            $this->warn('   System is using static token fallback.');
        } else {
            $this->line('   In Backoff: ✓ No');
        }
        
        if ($status['last_error']) {
            $this->newLine();
            $this->error('   Last Error: ' . substr($status['last_error'], 0, 150));
        }
        
        $this->newLine();

        // Recommendations
        if ($status['failure_count'] > 0) {
            $this->warn('⚡ Recommendations:');
            
            if (strpos($status['last_error'] ?? '', 'HTML') !== false || 
                strpos($status['last_error'] ?? '', 'DOCTYPE') !== false) {
                $this->warn('   • API is returning HTML instead of JSON');
                $this->warn('   • Check if credentials are correct:');
                $this->line('     - SHULESOFT_AUTH_EMAIL in .env');
                $this->line('     - SHULESOFT_AUTH_PASSWORD in .env');
                $this->warn('   • Verify API URL is correct');
            }
            
            if ($status['in_backoff_period']) {
                $this->line('   • Wait for backoff period to expire, OR');
                $this->line('   • Fix credentials and run: php artisan shulesoft:auth-status --enable');
            } else {
                $this->line('   • Test authentication: php artisan shulesoft:auth-status --test');
                $this->line('   • Clear cache and retry: php artisan shulesoft:auth-status --reset');
            }
            
            $this->newLine();
        } elseif ($status['has_access_token']) {
            $this->info('✅ OAuth authentication is working correctly!');
            $this->newLine();
        } else {
            $this->warn('⚠️  No OAuth token yet. Will authenticate on next API request.');
            $this->newLine();
        }

        // Static Token Fallback
        $staticToken = config('services.billing.access_token');
        if ($staticToken) {
            $this->info('🔐 Static Token Fallback:');
            $this->line('   Configured: ✓ Yes');
            $this->line('   Token: ' . substr($staticToken, 0, 30) . '...');
            $this->line('   Status: ' . ($status['in_backoff_period'] ? 'ACTIVE (OAuth in backoff)' : 'Standby'));
        } else {
            $this->error('🔐 Static Token Fallback: ✗ Not Configured');
            $this->warn('   Set BILLING_ACCESS_TOKEN in .env as backup');
        }

        return 0;
    }

    /**
     * Reset authentication cache
     */
    private function resetAuth()
    {
        $this->warn('Clearing authentication cache...');
        ShulesoftAuthService::clearAuthCache();
        $this->info('✓ Authentication cache cleared!');
        $this->newLine();
        
        $this->info('Testing authentication...');
        $this->testAuth();
        
        return 0;
    }

    /**
     * Enable OAuth and clear backoff
     */
    private function enableOAuth()
    {
        $this->info('Enabling OAuth and clearing backoff...');
        ShulesoftAuthService::enableOAuth();
        $this->info('✓ OAuth re-enabled!');
        $this->newLine();
        
        $this->info('Testing authentication...');
        $this->testAuth();
        
        return 0;
    }

    /**
     * Test authentication
     */
    private function testAuth()
    {
        $this->info('Testing authentication with current credentials...');
        $this->newLine();
        
        try {
            $token = ShulesoftAuthService::getAccessToken();
            
            if ($token) {
                $this->info('✅ SUCCESS! OAuth authentication working.');
                $this->line('   Token: ' . substr($token, 0, 30) . '...');
            } else {
                $this->warn('⚠️  OAuth returned null (using static token fallback)');
                $this->line('   This usually means:');
                $this->line('   • OAuth is in backoff period, OR');
                $this->line('   • Credentials are incorrect, OR');
                $this->line('   • API server has issues');
            }
        } catch (\Exception $e) {
            $this->error('❌ FAILED: ' . $e->getMessage());
            $this->newLine();
            
            if (strpos($e->getMessage(), 'HTML') !== false || strpos($e->getMessage(), 'DOCTYPE') !== false) {
                $this->error('The API returned HTML instead of JSON.');
                $this->error('This indicates invalid credentials or wrong endpoint.');
                $this->newLine();
                $this->line('Current configuration:');
                $this->line('  Email: ' . config('services.shulesoft_billing.auth_email'));
                $this->line('  API: ' . config('services.shulesoft_billing.api_url'));
            }
        }
        
        $this->newLine();
        $this->showStatus();
        
        return 0;
    }
}
