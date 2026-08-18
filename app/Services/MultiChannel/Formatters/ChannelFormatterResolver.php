<?php

namespace App\Services\MultiChannel\Formatters;

use InvalidArgumentException;
use App\Services\MultiChannel\Formatters\PhoneSmsFormatter;
use App\Services\MultiChannel\Formatters\BulkSmsFormatter;
use App\Services\MultiChannel\Formatters\EmailFormatter;
use App\Services\MultiChannel\Formatters\WhatsAppFormatter;

class ChannelFormatterResolver
{
    public function resolve(string $channel): ChannelFormatterInterface
    {
        $key = strtolower(trim($channel));

        return match ($key) {
            'email' => app(EmailFormatter::class),
            'phone_sms' => app(PhoneSmsFormatter::class),
            'bulk_sms' => app(BulkSmsFormatter::class),
            'whatsapp' => app(WhatsAppFormatter::class),
            default => throw new InvalidArgumentException("No formatter configured for channel '{$channel}'"),
        };
    }
}
