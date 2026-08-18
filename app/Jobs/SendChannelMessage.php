<?php

namespace App\Jobs;

use App\Models\OutgoingMessage;
use App\Services\MultiChannel\NotificationsApiAdapter;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendChannelMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(
        private int $outgoingMessageId,
        private array $payload,
        private array $options = []
    ) {
        $channel = (string) ($this->options['channel'] ?? $this->payload['channel'] ?? 'whatsapp');
        $this->onQueue($this->queueForChannel($channel));
    }

    public function handle(NotificationsApiAdapter $adapter): void
    {
        $message = OutgoingMessage::find($this->outgoingMessageId);
        if (!$message) {
            throw new Exception("OutgoingMessage {$this->outgoingMessageId} not found");
        }

        $previousStatus = $message->status;

        $message->update([
            'status' => 'processing',
            'queued_at' => $message->queued_at ?? now(),
        ]);

        $response = $adapter->send($this->payload);

        $status = $response['status'] ?? null;
        $isSuccess = ($response['success'] ?? false) && in_array($status, ['queued', 'sent', 'delivered', null], true);

        if ($isSuccess) {
            $message->update([
                'status' => in_array($status, ['queued', 'sent', 'delivered'], true) ? $status : 'sent',
                'external_id' => $response['external_id'] ?? ($response['message_id'] ?? null),
                'sent_at' => now(),
                'error_message' => null,
            ]);

            app(\App\Services\MultiChannel\ChannelMetricsService::class)
                ->recordOutgoingTransition($message->fresh(), $previousStatus, $message->fresh()->status);

            return;
        }

        if ((int) ($response['status_code'] ?? 0) === 429) {
            Log::warning('SendChannelMessage rate limited; releasing job', [
                'outgoing_message_id' => $message->id,
                'channel' => $this->payload['channel'] ?? null,
            ]);

            $this->release(300);
            return;
        }

        $error = json_encode($response['body'] ?? $response);
        throw new Exception('Channel transport failed: ' . $error);
    }

    public function failed(\Throwable $exception): void
    {
        $message = OutgoingMessage::find($this->outgoingMessageId);
        if ($message) {
            $previousStatus = $message->status;
            $message->update([
                'status' => 'failed',
                'retry_count' => ($message->retry_count ?? 0) + 1,
                'error_message' => $exception->getMessage(),
                'last_retry_at' => now(),
            ]);

            app(\App\Services\MultiChannel\ChannelMetricsService::class)
                ->recordOutgoingTransition($message->fresh(), $previousStatus, 'failed');
        }

        Log::error('SendChannelMessage failed permanently', [
            'outgoing_message_id' => $this->outgoingMessageId,
            'error' => $exception->getMessage(),
        ]);
    }

    private function queueForChannel(string $channel): string
    {
        return match (strtolower($channel)) {
            'email' => 'messages_email',
            'phone_sms' => 'messages_sms',
            'bulk_sms' => 'messages_bulk_sms',
            default => 'messages_whatsapp',
        };
    }
}
