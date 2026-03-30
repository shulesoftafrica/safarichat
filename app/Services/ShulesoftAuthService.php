<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Shulesoft OAuth Authentication Service
 * Handles 3-step authentication flow with automatic token refresh
 * 
 * Authentication Flow:
 * 1. Login with email/password → Get user access token
 * 2. Use user token → Create OAuth client → Get client_id & client_secret
 * 3. Use client credentials → Get API access token
 * 
 * Token Management:
 * - Access tokens expire after 90 days
 * - Tokens are cached and auto-refreshed on expiration (401 errors)
 * - Client credentials are stored permanently
 */
class ShulesoftAuthService
{
    // Cache keys
    const CACHE_KEY_ACCESS_TOKEN = 'shulesoft_access_token';
    const CACHE_KEY_USER_TOKEN = 'shulesoft_user_token';
    const CACHE_KEY_CLIENT_ID = 'shulesoft_client_id';
    const CACHE_KEY_CLIENT_SECRET = 'shulesoft_client_secret';
    const CACHE_KEY_TOKEN_EXPIRES = 'shulesoft_token_expires_at';
    
    // Token expiration: 90 days minus 1 day buffer for safety
    const TOKEN_LIFETIME = 89 * 24 * 60 * 60; // 89 days in seconds
    
    /**
     * Get active access token
     * Returns cached token or generates new one if expired
     * Falls back to static token if OAuth is unavailable
     * 
     * @return string|null Access token or null if OAuth unavailable
     */
    public static function getAccessToken()
    {
        // Check if we have a valid cached token
        $token = Cache::get(self::CACHE_KEY_ACCESS_TOKEN);
        $expiresAt = Cache::get(self::CACHE_KEY_TOKEN_EXPIRES);
        
        if ($token && $expiresAt && time() < $expiresAt) {
            return $token;
        }
        
        // Token expired or doesn't exist, try to refresh it
        try {
            return self::refreshAccessToken();
        } catch (Exception $e) {
            Log::warning('OAuth unavailable: ' . $e->getMessage());
            return null; // Will trigger fallback to static token
        }
    }
    
    /**
     * Force refresh the access token
     * Goes through the full OAuth flow if needed
     * 
     * @return string New access token
     * @throws Exception If authentication fails
     */
    public static function refreshAccessToken()
    {
        try {
            // Check if we have stored client credentials
            $clientId = Cache::get(self::CACHE_KEY_CLIENT_ID);
            $clientSecret = Cache::get(self::CACHE_KEY_CLIENT_SECRET);
            
            if (!$clientId || !$clientSecret) {
                // No client credentials, need to create them
                Log::info('No OAuth client credentials found, creating new client...');
                self::createOAuthClient();
                
                // Retrieve newly created credentials
                $clientId = Cache::get(self::CACHE_KEY_CLIENT_ID);
                $clientSecret = Cache::get(self::CACHE_KEY_CLIENT_SECRET);
            }
            
            // Step 3: Get access token using client credentials
            $token = self::getTokenFromClientCredentials($clientId, $clientSecret);
            
            // Cache the token with expiration
            $expiresAt = time() + self::TOKEN_LIFETIME;
            Cache::put(self::CACHE_KEY_ACCESS_TOKEN, $token, self::TOKEN_LIFETIME);
            Cache::put(self::CACHE_KEY_TOKEN_EXPIRES, $expiresAt, self::TOKEN_LIFETIME);
            
            Log::info('Shulesoft access token refreshed successfully', [
                'expires_at' => date('Y-m-d H:i:s', $expiresAt)
            ]);
            
            return $token;
            
        } catch (Exception $e) {
            Log::error('Failed to refresh Shulesoft access token: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            throw $e;
        }
    }
    
    /**
     * Create OAuth client credentials (Step 1 & 2)
     * This is typically done once and credentials are stored permanently
     * 
     * @throws Exception If client creation fails
     */
    private static function createOAuthClient()
    {
        try {
            // Step 1: Login to get user access token
            $userToken = self::loginAndGetUserToken();
            
            // Step 2: Create OAuth client using user token
            $email = config('services.shulesoft_billing.organization_email');
            $apiUrl = config('services.shulesoft_billing.api_url');
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $userToken,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])
                ->post($apiUrl . '/oauth/clients', [
                    'organization_email' => $email,
                    'name' => 'SafariChat Production Client',
                    'environment' => 'live',
                    'allowed_scopes' => ['*']
                ]);
            
            if (!$response->successful()) {
                $errorBody = $response->body();
                throw new Exception('Failed to create OAuth client: ' . $errorBody);
            }
            
            $data = $response->json();
            
            if (!isset($data['client']['client_id']) || !isset($data['client']['client_secret'])) {
                throw new Exception('Invalid response from OAuth client creation');
            }
            
            $clientId = $data['client']['client_id'];
            $clientSecret = $data['client']['client_secret'];
            
            // Store client credentials permanently (they don't expire)
            Cache::forever(self::CACHE_KEY_CLIENT_ID, $clientId);
            Cache::forever(self::CACHE_KEY_CLIENT_SECRET, $clientSecret);
            Cache::forever(self::CACHE_KEY_USER_TOKEN, $userToken);
            
            Log::info('OAuth client created successfully', [
                'client_id' => $clientId
            ]);
            
        } catch (Exception $e) {
            // Wrap the exception with additional context
            throw new Exception('Failed to create OAuth client: ' . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Step 1: Login with email/password to get user access token
     * 
     * @return string User access token
     * @throws Exception If login fails
     */
    private static function loginAndGetUserToken()
    {
        $email = config('services.shulesoft_billing.auth_email');
        $password = config('services.shulesoft_billing.auth_password');
        $apiUrl = config('services.shulesoft_billing.api_url');
        
        if (!$email || !$password) {
            throw new Exception('Shulesoft authentication credentials not configured. Set SHULESOFT_AUTH_EMAIL and SHULESOFT_AUTH_PASSWORD in .env');
        }
        
        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->post($apiUrl . '/auth/login', [
                'email' => $email,
                'password' => $password
            ]);
        
        if (!$response->successful()) {
            throw new Exception('Login failed: ' . $response->body());
        }
        
        $data = $response->json();
        
        if (!isset($data['access_token'])) {
            throw new Exception('Invalid login response, no access token received');
        }
        
        Log::info('User login successful');
        
        return $data['access_token'];
    }
    
    /**
     * Step 3: Get API access token using client credentials
     * 
     * @param string $clientId
     * @param string $clientSecret
     * @return string API access token
     * @throws Exception If token request fails
     */
    private static function getTokenFromClientCredentials($clientId, $clientSecret)
    {
        $apiUrl = config('services.shulesoft_billing.api_url');
        
        $response = Http::timeout(30)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->post($apiUrl . '/oauth/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => '*'
            ]);
        
        if (!$response->successful()) {
            throw new Exception('Failed to get access token: ' . $response->body());
        }
        
        $data = $response->json();
        
        if (!isset($data['access_token'])) {
            throw new Exception('Invalid token response, no access token received');
        }
        
        return $data['access_token'];
    }
    
    /**
     * Check if current token is expired
     * 
     * @return bool True if token is expired
     */
    public static function isTokenExpired()
    {
        $expiresAt = Cache::get(self::CACHE_KEY_TOKEN_EXPIRES);
        
        if (!$expiresAt) {
            return true;
        }
        
        return time() >= $expiresAt;
    }
    
    /**
     * Clear all cached authentication data
     * Useful for debugging or forcing re-authentication
     */
    public static function clearAuthCache()
    {
        Cache::forget(self::CACHE_KEY_ACCESS_TOKEN);
        Cache::forget(self::CACHE_KEY_USER_TOKEN);
        Cache::forget(self::CACHE_KEY_CLIENT_ID);
        Cache::forget(self::CACHE_KEY_CLIENT_SECRET);
        Cache::forget(self::CACHE_KEY_TOKEN_EXPIRES);
        
        Log::info('Shulesoft authentication cache cleared');
    }
    
    /**
     * Get current authentication status
     * Useful for debugging
     * 
     * @return array Authentication status information
     */
    public static function getAuthStatus()
    {
        $hasToken = Cache::has(self::CACHE_KEY_ACCESS_TOKEN);
        $hasClient = Cache::has(self::CACHE_KEY_CLIENT_ID) && Cache::has(self::CACHE_KEY_CLIENT_SECRET);
        $expiresAt = Cache::get(self::CACHE_KEY_TOKEN_EXPIRES);
        
        return [
            'has_access_token' => $hasToken,
            'has_client_credentials' => $hasClient,
            'token_expires_at' => $expiresAt ? date('Y-m-d H:i:s', $expiresAt) : null,
            'is_expired' => self::isTokenExpired(),
            'client_id' => Cache::get(self::CACHE_KEY_CLIENT_ID),
        ];
    }
    
    /**
     * Initialize authentication on application boot
     * This ensures we have a valid token ready
     */
    public static function initialize()
    {
        try {
            self::getAccessToken();
        } catch (Exception $e) {
            Log::error('Failed to initialize Shulesoft authentication: ' . $e->getMessage());
            // Don't throw, just log the error
        }
    }
    
    /**
     * Check if an error is due to OAuth not being available on the server
     * 
     * @param Exception $e The exception to check
     * @return bool True if this is a server-side OAuth unavailability error
     */
    private static function isOAuthServerError(Exception $e)
    {
        $message = $e->getMessage();
        
        // Check for database table missing errors (PostgreSQL)
        if (strpos($message, 'oauth_clients') !== false || 
            strpos($message, 'Undefined table') !== false ||
            strpos($message, '42P01') !== false) {
            return true;
        }
        
        // Check for other OAuth unavailability indicators
        if (strpos($message, 'oauth/clients') !== false && 
            (strpos($message, '404') !== false || strpos($message, '500') !== false)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Re-enable OAuth after it was disabled
     * Call this if you know the API server has been fixed
     */
    public static function enableOAuth()
    {
        Cache::forget('shulesoft_oauth_disabled');
        Log::info('OAuth re-enabled, will attempt authentication on next request');
    }
}
