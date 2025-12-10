<?php

namespace App\Http\Middleware;

use App\Services\CreditService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CreditCheckMiddleware
{
    public function __construct(
        protected CreditService $creditService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, int $requiredCredits = 1): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has sufficient credits
        if (!$this->creditService->hasSufficientCredits($user, $requiredCredits)) {
            // For AJAX requests, return JSON error
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Insufficient credits',
                    'message' => "You need at least {$requiredCredits} credits to perform this action.",
                    'current_balance' => $user->available_credits,
                    'required' => $requiredCredits,
                    'topup_url' => route('payment.topup')
                ], 402);
            }

            // For web requests, redirect with error
            return back()->with('error', "You need at least {$requiredCredits} credits to perform this action. Current balance: {$user->available_credits}");
        }

        return $next($request);
    }
}
