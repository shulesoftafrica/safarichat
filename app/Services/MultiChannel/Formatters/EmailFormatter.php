<?php

namespace App\Services\MultiChannel\Formatters;

class EmailFormatter implements ChannelFormatterInterface
{
    public function format(array $context): array
    {
        return [
            'subject' => $context['subject'] ?? 'Notification',
        ];
    }
}
