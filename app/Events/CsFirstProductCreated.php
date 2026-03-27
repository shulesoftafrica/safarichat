<?php

namespace App\Events;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CsFirstProductCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Product $product,
        public readonly User $user,
    ) {}
}
