<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after AI credits are successfully added to a user's account
 * (either via credit top-up purchase or subscription renewal).
 */
class CreditsAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param User $user          The business owner receiving credits.
     * @param int  $creditsAdded  Number of credits added in this transaction.
     * @param int  $newBalance    The user's total credit balance after the addition.
     */
    public function __construct(
        public readonly User $user,
        public readonly int  $creditsAdded,
        public readonly int  $newBalance,
    ) {}
}
