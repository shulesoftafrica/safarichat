<?php

namespace App\Services\MultiChannel\Formatters;

class BulkSmsFormatter implements ChannelFormatterInterface
{
    public function format(array $context): array
    {
        $message = (string) ($context['message'] ?? '');
        $maxLength = (int) ($context['max_length'] ?? 160);

        return [
            'type' => $context['type'] ?? 'sms',
            'message' => mb_substr($message, 0, $maxLength),
            'is_bulk' => true,
            'is_truncated' => mb_strlen($message) > $maxLength,
            'campaign_id' => $context['campaign_id'] ?? null,
        ];
    }
}
