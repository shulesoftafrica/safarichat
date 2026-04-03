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

        // ALL non-system instances must be operational before the user can access the app.
        // An instance is operational when status IN (connected, active) OR connect_status = ready.
        // If ANY single instance is down the user is bounced to the connect/QR page so they
        // can reconnect it — the Sales Agents page only shows active instances, so this is the
        // only place where a disconnected instance is always visible.
        $userInstances = $user->whatsappInstances()
            ->where('is_system_default', false)
            ->get();

        $allOperational = $userInstances->isNotEmpty()
            && $userInstances->every(fn($i) => $i->isOperational());

        if (!$allOperational) {
            return redirect()->route('business.wasender')
                ->with('message', 'Please ensure all your WhatsApp instances are connected to continue.');
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