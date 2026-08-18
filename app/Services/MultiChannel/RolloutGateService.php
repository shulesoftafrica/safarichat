<?php

namespace App\Services\MultiChannel;

use App\Models\Business;

class RolloutGateService
{
    /**
     * Resolve whether multi-channel routing should run for this user/business.
     */
    public function isEnabledForUser(int $userId, ?int $businessId = null): bool
    {
        if (!(bool) config('multi_channel.enabled', false)) {
            return false;
        }

        $mode = (string) config('multi_channel.rollout.mode', 'global');
        if ($mode === 'global') {
            return true;
        }

        $enabledUsers = array_map('intval', (array) config('multi_channel.rollout.enabled_user_ids', []));
        if (in_array($userId, $enabledUsers, true)) {
            return true;
        }

        $resolvedBusinessId = $businessId;
        if (!$resolvedBusinessId) {
            $resolvedBusinessId = Business::where('user_id', $userId)->value('id');
        }

        if (!$resolvedBusinessId) {
            return false;
        }

        $enabledBusinesses = array_map('intval', (array) config('multi_channel.rollout.enabled_business_ids', []));
        return in_array((int) $resolvedBusinessId, $enabledBusinesses, true);
    }
}
