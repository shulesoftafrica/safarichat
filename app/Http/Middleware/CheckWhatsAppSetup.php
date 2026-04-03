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
        // 'wasender*' covers the index page, QR, session-status, verify, cleanup, disconnect
        $allowedPaths = [
            'wasender*',
            'auth/business/wasender',
            'logout',
            'lang',
            'home/settings',
        ];

        foreach ($allowedPaths as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // Check if user has a connected WhatsApp instance.
        // Accept both 'connected' and 'active' — seeder seeds with 'active',
        // and some older rows use 'active' before going through the WaSender flow.
        $hasConnectedWhatsApp = $user->whatsappInstances()
            ->whereIn('status', ['connected', 'active'])
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