<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class NotificationApiMiddleware
{
    /**
     * Handle an incoming request for notification API endpoints
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Start request timing
        $startTime = microtime(true);
        
        // Log incoming request
        Log::info('Notification API Request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toISOString()
        ]);
        
        // Rate limiting
        $key = 'api_limit:' . $request->ip();
        $maxAttempts = config('notifications.rate_limit.max_attempts', 1000);
        $decayMinutes = config('notifications.rate_limit.decay_minutes', 60);
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            Log::warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'retry_after' => $seconds
            ]);
            
            return response()->json([
                'error' => 'Rate limit exceeded',
                'retry_after' => $seconds,
                'max_attempts' => $maxAttempts,
                'window' => $decayMinutes . ' minutes'
            ], 429);
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        try {
            $response = $next($request);
            
            // Log response
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::info('Notification API Response', [
                'status' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'user_id' => $request->user()?->id,
                'memory_usage' => memory_get_peak_usage(true),
                'timestamp' => now()->toISOString()
            ]);
            
            // Add performance headers
            $response->headers->set('X-Response-Time', round($duration, 2) . 'ms');
            $response->headers->set('X-RateLimit-Limit', $maxAttempts);
            $response->headers->set('X-RateLimit-Remaining', max(0, $maxAttempts - RateLimiter::attempts($key)));
            $response->headers->set('X-RateLimit-Reset', now()->addMinutes($decayMinutes)->timestamp);
            
            return $response;
            
        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;
            
            Log::error('Notification API Error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'duration_ms' => round($duration, 2),
                'user_id' => $request->user()?->id,
                'timestamp' => now()->toISOString()
            ]);
            
            // Return standardized error response
            return response()->json([
                'error' => 'Internal server error',
                'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
                'code' => $e->getCode() ?: 500,
                'timestamp' => now()->toISOString(),
                'request_id' => uniqid('req_')
            ], 500);
        }
    }
}