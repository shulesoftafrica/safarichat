<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates calls from the ShuleSoft admin panel to /api/admin-panel/*.
 *
 * The admin panel signs "{X-Admin-Timestamp}.{raw body}" with HMAC-SHA256 using the shared
 * secret ADMIN_PANEL_API_SECRET and sends the hex digest as X-Admin-Signature. Requests older
 * than five minutes are refused, and so is everything when no secret is configured.
 */
class VerifyAdminPanelSignature
{
    const MAX_SKEW_SECONDS = 300;

    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.admin_panel.secret');
        if (!$secret) {
            Log::error('Admin panel API called but ADMIN_PANEL_API_SECRET is not configured');
            return response()->json(['success' => false, 'message' => 'Admin API is not configured'], 503);
        }

        $timestamp = (string) $request->header('X-Admin-Timestamp');
        $signature = (string) $request->header('X-Admin-Signature');

        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > self::MAX_SKEW_SECONDS) {
            return response()->json(['success' => false, 'message' => 'Missing or stale timestamp'], 401);
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $request->getContent(), $secret);
        if (!hash_equals($expected, $signature)) {
            Log::warning('Admin panel API: invalid signature', ['ip' => $request->ip(), 'path' => $request->path()]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
