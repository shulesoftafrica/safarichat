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
            'provider' => $this->normalizeProvider($channel, $context['provider'] ?? null),
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

    /**
     * Resolve the `provider` field to a value the notifications API accepts.
     *
     * The API validates provider against a fixed set: twilio, whatsapp, sendgrid,
     * mailgun. Internal names like 'wa_sender' or 'internal_sms_api' are rejected
     * with a 422, so any non-conforming value is mapped to the correct provider
     * for the channel. A caller-supplied value is honored only if it is already valid.
     */
    private function normalizeProvider(string $channel, ?string $requested): string
    {
        $allowed = ['twilio', 'whatsapp', 'sendgrid', 'mailgun'];

        $requested = $requested !== null ? strtolower(trim($requested)) : null;
        if ($requested !== null && in_array($requested, $allowed, true)) {
            return $requested;
        }

        return match ($channel) {
            'email' => 'sendgrid',
            'phone_sms', 'bulk_sms' => 'twilio',
            default => 'whatsapp',
        };
    }
}
