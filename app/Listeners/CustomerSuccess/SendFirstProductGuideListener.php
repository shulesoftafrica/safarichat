<?php

namespace App\Listeners\CustomerSuccess;

use App\Events\CsFirstProductCreated;
use App\Models\CsMessageLog;
use App\Services\CustomerSuccess\CsMessageRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendFirstProductGuideListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'cs';
    public int $tries   = 3;
    public int $backoff  = 60;

    public function __construct(private readonly CsMessageRenderer $renderer) {}

    public function handle(CsFirstProductCreated $event): void
    {
        $user    = $event->user;
        $product = $event->product;

        // Guard: only send once per user, ever
        if ($user->cs_first_product_message_sent_at) {
            return;
        }

        if (CsMessageLog::everSent($user->id, 'first_product')) {
            $user->updateQuietly(['cs_first_product_message_sent_at' => now()]);
            return;
        }

        $userPhone = $user->phone
            ?? $user->whatsappInstances()->where('status', 'connected')->value('phone_number')
            ?? null;

        if (!$userPhone) {
            Log::warning('SendFirstProductGuideListener: user has no phone, cannot send', [
                'user_id'    => $user->id,
                'product_id' => $product->id,
            ]);
            return;
        }

        $sent = $this->renderer->send($user, 'first_product', [
            'product_name' => $product->name,
            'your_number'  => $userPhone,
        ], $user->business_id ?? 0);

        if ($sent) {
            $user->updateQuietly(['cs_first_product_message_sent_at' => now()]);
            Log::info('SendFirstProductGuideListener: first-product guide dispatched', [
                'user_id'    => $user->id,
                'product_id' => $product->id,
            ]);
        }
    }

    public function failed(CsFirstProductCreated $event, \Throwable $exception): void
    {
        Log::error('SendFirstProductGuideListener: job failed after retries', [
            'user_id'    => $event->user->id,
            'product_id' => $event->product->id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
