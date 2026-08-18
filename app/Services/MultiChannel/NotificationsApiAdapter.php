<?php

namespace App\Services\MultiChannel;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class NotificationsApiAdapter
{
    private string $baseUrl;
    private string $token;
    private int $timeoutSeconds;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('multi_channel.transport.base_url', config('notifications.unified_api.base_url', 'https://notifications.shulesoft.africa/api')), '/');
        $this->token = (string) config('notifications.unified_api.bearer_token', '');
        $this->timeoutSeconds = (int) config('multi_channel.transport.timeout_seconds', config('notifications.unified_api.timeout', 30));
    }

    public function send(array $payload): array
    {
        if ($this->token === '') {
            throw new RuntimeException('Notifications API bearer token is not configured.');
        }

        $url = $this->baseUrl . '/notifications/send';

        $response = Http::withToken($this->token)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->timeout($this->timeoutSeconds)
            ->post($url, $payload);

        $body = $response->json();

        if (!$response->successful()) {
            Log::warning('Notifications API request failed', [
                'status' => $response->status(),
                'url' => $url,
                'channel' => $payload['channel'] ?? null,
                'to' => $payload['to'] ?? null,
                'body' => $body,
            ]);
        }

        return [
            'success' => $response->successful() && (($body['success'] ?? true) !== false),
            'status_code' => $response->status(),
            'body' => $body,
            'external_id' => $body['external_id'] ?? null,
            'message_id' => $body['message_id'] ?? ($body['id'] ?? null),
            'status' => $body['status'] ?? null,
        ];
    }
}
