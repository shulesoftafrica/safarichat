<?php

namespace App\Services\MultiChannel\Formatters;

interface ChannelFormatterInterface
{
    /**
     * Build channel-specific payload fragment.
     */
    public function format(array $context): array;
}
