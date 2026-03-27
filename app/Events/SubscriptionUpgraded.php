<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an existing active subscription is upgraded to a higher plan.
 */
class SubscriptionUpgraded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param User   $user     The business owner who upgraded.
     * @param string $fromPlan The previous plan code.
     * @param string $toPlan   The new (higher) plan code.
     */
    public function __construct(
        public readonly User   $user,
        public readonly string $fromPlan,
        public readonly string $toPlan,
    ) {}
}
