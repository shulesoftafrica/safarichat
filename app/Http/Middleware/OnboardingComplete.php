<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;

class OnboardingComplete
{
    /**
     * Handle an incoming request to ensure onboarding is complete.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Skip onboarding check if user explicitly completed onboarding
            if ($request->get('onboarding_complete')) {
                return $next($request);
            }
            
            // Check if user has completed basic onboarding
            $hasProducts = Product::forUser($user->id)->exists();
            $hasWhatsAppConnection = $user->whatsappInstances()
                ->where('status', 1)
                ->exists();
            
            // If accessing dashboard or other core features without completing onboarding
            if (!$hasProducts && !$request->is('service*') && !$request->is('wasender*') && !$request->is('api*')) {
                // Redirect to products setup with onboarding flag
                return redirect('/service/index?onboarding=true&incomplete=products')
                    ->with('onboarding_message', 'Before you can access other features, please set up at least one product or service.');
            }
            
            // If accessing other features without WhatsApp connection
            if (!$hasWhatsAppConnection && !$request->is('wasender*') && !$request->is('service*') && !$request->is('api*')) {
                return redirect('/wasender')
                    ->with('onboarding_message', 'Please connect your WhatsApp account first.');
            }
        }

        return $next($request);
    }
}