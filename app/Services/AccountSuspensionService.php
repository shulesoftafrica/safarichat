<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Admin suspension of a business (billing_accounts.status = 'suspended'), set from the
 * ShuleSoft admin panel. A suspended business's AI agent stops replying and its users
 * lose dashboard access until it is reactivated.
 *
 * The flag is checked on every web request and every AI billing check, so it is cached
 * per user; AdminPanelController forgets the cache whenever it changes the flag.
 */
class AccountSuspensionService
{
    const CACHE_PREFIX = 'account_suspended_';
    const CACHE_SECONDS = 300;

    public static function isSuspended($userId): bool
    {
        if (!$userId) {
            return false;
        }

        return Cache::remember(self::CACHE_PREFIX . $userId, self::CACHE_SECONDS, function () use ($userId) {
            $user = User::find($userId);
            // Team members inherit the owner's billing account through the parent business.
            $billingAccount = $user ? $user->effectiveBillingAccount() : null;

            return $billingAccount && $billingAccount->status === 'suspended';
        });
    }

    /** Forget the cached flag (and billing status) for everyone working under a business. */
    public static function forgetBusiness(int $businessId): void
    {
        $userIds = User::where('parent_business_id', $businessId)->pluck('id')
            ->merge(\App\Models\Business::where('id', $businessId)->pluck('user_id'))
            ->unique();

        foreach ($userIds as $userId) {
            Cache::forget(self::CACHE_PREFIX . $userId);
            BillingService::clearCache($userId);
        }
    }

    /** Overlay a suspension onto a billing status array: nothing is allowed while suspended. */
    public static function applyTo(array $status, $userId): array
    {
        if (!self::isSuspended($userId)) {
            return $status;
        }

        $status['subscription']['active'] = false;
        $status['suspended'] = true;
        foreach (array_keys($status['permissions'] ?? []) as $permission) {
            $status['permissions'][$permission] = false;
        }

        return $status;
    }
}
