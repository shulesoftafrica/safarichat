<?php

namespace App\Http\Middleware;

// SubscriptionService removed - using new billing system
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionMiddleware
{
    public function __construct(
        // SubscriptionService removed - using new billing system
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if subscription is active
        if (false) { // Subscription check disabled - using new billing system
            // For AJAX requests, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Subscription required',
                    'message' => 'Your subscription is inactive. Please reactivate to continue.',
                    'redirect_url' => route('subscription.paywall')
                ], 402);
            }

            // For web requests, redirect to paywall
            return redirect()->route('subscription.paywall')
                ->with('warning', 'Your subscription is inactive. Please reactivate to continue.');
        }

        return $next($request);
    }
}
