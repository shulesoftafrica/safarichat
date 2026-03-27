<?php

namespace App\Events;

use App\Models\WhatsappInstance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsappInstanceConnected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WhatsappInstance $instance,
        public readonly bool $isFirstConnection = false,
    ) {}
}
