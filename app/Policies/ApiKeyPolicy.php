<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApiKeyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any API keys.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can view their own API keys
    }

    /**
     * Determine whether the user can view the API key.
     */
    public function view(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can create API keys.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create API keys
    }

    /**
     * Determine whether the user can update the API key.
     */
    public function update(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can delete the API key.
     */
    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->id === $apiKey->user_id;
    }
}
