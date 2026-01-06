<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of the user's API keys.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $apiKeys = $user->apiKeys()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'key_prefix' => $key->key_prefix,
                    'permissions' => $key->permissions,
                    'last_used_at' => $key->last_used_at,
                    'expires_at' => $key->expires_at,
                    'is_active' => $key->is_active,
                    'is_expired' => $key->isExpired(),
                    'created_at' => $key->created_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $apiKeys,
        ]);
    }

    /**
     * Store a newly created API key.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'required|array',
            'permissions.*' => 'required|string|in:crm:import:contacts,crm:import:conversations,crm:export:conversations,webhooks:manage',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $user = $request->user();

        // Check if user already has too many API keys
        if ($user->apiKeys()->active()->count() >= 10) {
            throw ValidationException::withMessages([
                'limit' => 'You cannot have more than 10 active API keys.',
            ]);
        }

        // Check for duplicate name
        if ($user->apiKeys()->where('name', $request->name)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'You already have an API key with this name.',
            ]);
        }

        $expiresAt = $request->expires_in_days 
            ? now()->addDays($request->expires_in_days)
            : null;

        $result = ApiKey::createForUser(
            $user, 
            $request->name, 
            $request->permissions, 
            $expiresAt
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $result['api_key']->id,
                'name' => $result['api_key']->name,
                'key' => $result['plain_key'], // Only returned once!
                'key_prefix' => $result['api_key']->key_prefix,
                'permissions' => $result['api_key']->permissions,
                'expires_at' => $result['api_key']->expires_at,
                'created_at' => $result['api_key']->created_at,
            ],
            'message' => 'API key created successfully. Store it securely - it won\'t be shown again!',
        ], 201);
    }

    /**
     * Display the specified API key.
     */
    public function show(Request $request, ApiKey $apiKey): JsonResponse
    {
        $this->authorize('view', $apiKey);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'permissions' => $apiKey->permissions,
                'metadata' => $apiKey->metadata,
                'last_used_at' => $apiKey->last_used_at,
                'expires_at' => $apiKey->expires_at,
                'is_active' => $apiKey->is_active,
                'is_expired' => $apiKey->isExpired(),
                'created_at' => $apiKey->created_at,
                'updated_at' => $apiKey->updated_at,
            ],
        ]);
    }

    /**
     * Update the specified API key.
     */
    public function update(Request $request, ApiKey $apiKey): JsonResponse
    {
        $this->authorize('update', $apiKey);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'permissions' => 'sometimes|required|array',
            'permissions.*' => 'required|string|in:crm:import:contacts,crm:import:conversations,crm:export:conversations,webhooks:manage',
            'is_active' => 'sometimes|boolean',
        ]);

        // Check for duplicate name if name is being updated
        if ($request->has('name') && $request->name !== $apiKey->name) {
            if ($apiKey->user->apiKeys()->where('name', $request->name)->exists()) {
                throw ValidationException::withMessages([
                    'name' => 'You already have an API key with this name.',
                ]);
            }
        }

        $apiKey->update($request->only(['name', 'permissions', 'is_active']));

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'permissions' => $apiKey->permissions,
                'is_active' => $apiKey->is_active,
                'updated_at' => $apiKey->updated_at,
            ],
            'message' => 'API key updated successfully.',
        ]);
    }

    /**
     * Remove the specified API key.
     */
    public function destroy(Request $request, ApiKey $apiKey): JsonResponse
    {
        $this->authorize('delete', $apiKey);

        $apiKey->revoke();

        return response()->json([
            'success' => true,
            'message' => 'API key revoked successfully.',
        ]);
    }

    /**
     * Test API key authentication.
     */
    public function test(Request $request): JsonResponse
    {
        $user = $request->user();
        $apiKey = $request->attributes->get('api_key');

        return response()->json([
            'success' => true,
            'data' => [
                'authenticated' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'api_key' => [
                    'id' => $apiKey->id,
                    'name' => $apiKey->name,
                    'key_prefix' => $apiKey->key_prefix,
                    'permissions' => $apiKey->permissions,
                ],
                'timestamp' => now()->toISOString(),
            ],
            'message' => 'API key authentication successful.',
        ]);
    }
}
