<?php

namespace App\Http\Middleware;

use App\Services\AccountSuspensionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out users of a business the ShuleSoft admin panel has suspended, on their next
 * request, so an existing session does not outlive the suspension.
 */
class EnsureAccountNotSuspended
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && AccountSuspensionService::isSuspended($user->id)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'This account has been suspended. Please contact SafariChat support.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return redirect('/login')->with('error', $message);
        }

        return $next($request);
    }
}
