<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a user's subscription is activated (new subscription or trial-to-paid).
 */
class SubscriptionActivated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param User   $user     The business owner whose subscription was activated.
     * @param string $planCode The plan code that was activated (e.g. 'starter', 'pro', 'premium').
     */
    public function __construct(
        public readonly User   $user,
        public readonly string $planCode,
    ) {}
}
