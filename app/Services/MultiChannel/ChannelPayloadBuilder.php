<?php

namespace App\Services\MultiChannel;

use App\Services\MultiChannel\Formatters\ChannelFormatterResolver;
use InvalidArgumentException;

class ChannelPayloadBuilder
{
    public function __construct(private ChannelFormatterResolver $formatterResolver)
    {
    }

    /**
     * Build payload for notifications.shulesoft.africa according to channel contract.
     */
    public function build(string $channel, array $context): array
    {
        $channel = strtolower(trim($channel));
        $allowedChannels = config('multi_channel.channels', ['whatsapp', 'email', 'phone_sms', 'bulk_sms']);

        if (!in_array($channel, $allowedChannels, true)) {
            throw new InvalidArgumentException("Unsupported channel '{$channel}'");
        }

        $payload = [
            'schema_name' => $context['schema_name'] ?? null,
            'channel' => $channel,
            'to' => $context['to'] ?? null,
            'message' => $context['message'] ?? null,
            'provider' => $context['provider'] ?? $this->defaultProviderFor($channel),
            'priority' => $context['priority'] ?? 'normal',
        ];

        // Merge optional fields from caller first.
        if (!empty($context['extras']) && is_array($context['extras'])) {
            $payload = array_merge($payload, $context['extras']);
        }

        // Phase-3 strategy formatters normalize channel-specific payload fields.
        $formattedFields = $this->formatterResolver->resolve($channel)->format($context);
        $payload = array_merge($payload, $formattedFields);

        $this->assertRequiredFields($payload, $channel);

        return $payload;
    }

    private function assertRequiredFields(array $payload, string $channel): void
    {
        $required = config('multi_channel.payload.required_fields', [
            'schema_name',
            'channel',
            'to',
            'message',
            'provider',
            'priority',
        ]);

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                throw new InvalidArgumentException("Missing required payload field: {$field}");
            }
        }

        $channelRequired = config('multi_channel.payload.channel_specific_required', []);
        foreach (($channelRequired[$channel] ?? []) as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === null || $payload[$field] === '') {
                throw new InvalidArgumentException("Missing required channel field for {$channel}: {$field}");
            }
        }

        $priorities = config('multi_channel.payload.priorities', ['low', 'normal', 'high']);
        if (!in_array($payload['priority'], $priorities, true)) {
            throw new InvalidArgumentException("Unsupported priority '{$payload['priority']}'");
        }
    }

    private function defaultProviderFor(string $channel): string
    {
        return match ($channel) {
            'email' => 'sendgrid',
            'phone_sms', 'bulk_sms' => 'internal_sms_api',
            default => 'wa_sender',
        };
    }
}
