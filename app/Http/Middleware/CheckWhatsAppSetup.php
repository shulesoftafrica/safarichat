<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckWhatsAppSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip check for WhatsApp setup pages and auth pages
        $allowedPaths = [
            'auth/business/wasender',
            'wasender/create-session',
            'wasender/session-status',
            'wasender/verify-connection',
            'wasender/cleanup-session',
            'logout',
            'lang'
        ];

        foreach ($allowedPaths as $path) {
            if ($request->is($path) || $request->is($path . '/*')) {
                return $next($request);
            }
        }

        // Check if user has connected WhatsApp instance
        $hasConnectedWhatsApp = $user->whatsappInstances()
            ->where('status', 'connected')
            ->exists();

        if (!$hasConnectedWhatsApp) {
            // Redirect to WhatsApp setup
            return redirect()->route('business.wasender')
                ->with('message', 'Please connect your WhatsApp account first to continue.');
        }

        // Check if user has defined at least one product
        $hasProducts = $user->products()->exists();

        if (!$hasProducts) {
            // Skip product check for the products pages themselves
            if (!$request->is('products*') && !$request->is('service*')) {
                return redirect()->route('products.index')
                    ->with('message', 'Please define at least one product or service before continuing.');
            }
        }

        return $next($request);
    }
}