<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class BusinessReEngaged
{
    use Dispatchable;

    public function __construct(
        public readonly User $user,
        public readonly int  $businessId,
    ) {}
}
