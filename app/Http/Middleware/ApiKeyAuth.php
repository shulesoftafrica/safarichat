<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorizedResponse('API key is required. Provide it in the Authorization header as "Bearer YOUR_API_KEY".');
        }

        $apiKey = ApiKey::findByKey($token);

        if (!$apiKey) {
            return $this->unauthorizedResponse('Invalid API key provided.');
        }

        if (!$apiKey->isValid()) {
            return $this->unauthorizedResponse(
                $apiKey->isExpired() 
                    ? 'API key has expired.' 
                    : 'API key has been deactivated.'
            );
        }

        // Check permission if specified
        if ($permission && !$apiKey->hasPermission($permission)) {
            return $this->forbiddenResponse("This API key does not have the required permission: {$permission}");
        }

        // Update last used timestamp
        $apiKey->updateLastUsed();

        // Set the authenticated user and API key in the request
        $request->setUserResolver(function () use ($apiKey) {
            return $apiKey->user;
        });

        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }

    /**
     * Extract the API token from the request.
     */
    private function extractToken(Request $request): ?string
    {
        $authorization = $request->header('Authorization');

        if (!$authorization) {
            return null;
        }

        // Support both "Bearer TOKEN" and "TOKEN" formats
        if (str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }

        return $authorization;
    }

    /**
     * Return unauthorized JSON response.
     */
    private function unauthorizedResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Unauthorized',
        ], 401);
    }

    /**
     * Return forbidden JSON response.
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => 'Forbidden',
        ], 403);
    }
}
