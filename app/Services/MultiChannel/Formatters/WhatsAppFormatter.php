<?php

namespace App\Services\MultiChannel\Formatters;

class WhatsAppFormatter implements ChannelFormatterInterface
{
    public function format(array $context): array
    {
        $fragment = [
            'type' => $context['type'] ?? 'wasender',
        ];

        if (!empty($context['template_name'])) {
            $fragment['template_name'] = $context['template_name'];
        }

        if (!empty($context['media_url'])) {
            $fragment['media_url'] = $context['media_url'];
        }

        return $fragment;
    }
}
