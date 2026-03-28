<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Validate incoming webhook requests are from authorized IP addresses
 * 
 * This middleware ensures webhooks only come from the billing platform's servers.
 * Contact billing platform support to get their IP ranges.
 */
class ValidateBillingWebhookIP
{
    /**
     * Allowed IP addresses and ranges (CIDR notation)
     * 
     * TODO: Get actual IP ranges from billing platform support
     * These are placeholder examples - replace with real IPs
     * 
     * @var array
     */
    private const ALLOWED_IPS = [
        // Example IP ranges - REPLACE WITH ACTUAL BILLING PLATFORM IPs
        '144.91.101.154',       // Example: Broad Africa range
        
        // Single IP example:
        // '41.59.123.45',
        
        // For testing, you can add your testing server IPs here
        // '192.168.1.100',
    ];

    /**
     * Handle an incoming request
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $clientIp = $request->ip();
        
        // Allow localhost for local development and testing
        if ($this->isLocalEnvironment($clientIp)) {
            Log::debug('Webhook IP validation: Allowed (localhost)', [
                'ip' => $clientIp,
                'environment' => config('app.env')
            ]);
            return $next($request);
        }
        
        // Check if IP is in whitelist
        if ($this->isIpAllowed($clientIp)) {
            Log::debug('Webhook IP validation: Allowed', [
                'ip' => $clientIp
            ]);
            return $next($request);
        }
        
        // Unauthorized IP - log and reject
        Log::warning('Webhook rejected: Unauthorized IP address', [
            'ip' => $clientIp,
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'payload_preview' => substr($request->getContent(), 0, 200)
        ]);
        
        return response()->json([
            'success' => false,
            'error' => 'Unauthorized',
            'message' => 'Your IP address is not authorized to access this endpoint'
        ], 403);
    }

    /**
     * Check if request is from local environment
     *
     * @param string $ip
     * @return bool
     */
    private function isLocalEnvironment(string $ip): bool
    {
        // Allow localhost in local/development environment
        if (config('app.env') === 'local' || config('app.env') === 'testing') {
            return in_array($ip, ['127.0.0.1', '::1', 'localhost']);
        }
        
        return false;
    }

    /**
     * Check if IP address is in the allowed list
     *
     * @param string $ip
     * @return bool
     */
    private function isIpAllowed(string $ip): bool
    {
        foreach (self::ALLOWED_IPS as $allowedRange) {
            if ($this->ipInRange($ip, $allowedRange)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if an IP is within a given range (supports CIDR notation)
     *
     * @param string $ip The IP address to check
     * @param string $range Single IP or CIDR range (e.g., "192.168.1.0/24")
     * @return bool
     */
    private function ipInRange(string $ip, string $range): bool
    {
        // If no CIDR notation, it's a single IP
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        // Parse CIDR notation
        [$subnet, $mask] = explode('/', $range);
        
        // Convert IPs to long integers
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        
        // Invalid IP addresses
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }
        
        // Calculate network mask
        $maskLong = -1 << (32 - (int)$mask);
        
        // Check if IP is in range
        return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
    }
}
