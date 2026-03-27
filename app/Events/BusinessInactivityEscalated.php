<?php

namespace App\Events;

use App\Models\CsInactivityEpisode;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class BusinessInactivityEscalated
{
    use Dispatchable;

    public function __construct(
        public readonly User                $user,
        public readonly CsInactivityEpisode $episode,
    ) {}
}
