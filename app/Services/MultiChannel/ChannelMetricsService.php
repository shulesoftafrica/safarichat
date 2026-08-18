<?php

namespace App\Services\MultiChannel;

use App\Models\ContactChannelMetric;
use App\Models\IncomingMessage;
use App\Models\OutgoingMessage;

class ChannelMetricsService
{
    public function recordOutgoingTransition(OutgoingMessage $message, ?string $oldStatus, ?string $newStatus): void
    {
        $contactId = (int) ($message->business_contact_id ?? 0);
        if ($contactId <= 0) {
            return;
        }

        $channel = strtolower((string) ($message->selected_channel ?: 'whatsapp'));
        $metric = ContactChannelMetric::firstOrCreate(
            [
                'business_contact_id' => $contactId,
                'channel_key' => $channel,
            ],
            [
                'sent_count' => 0,
                'delivered_count' => 0,
                'replied_count' => 0,
                'converted_count' => 0,
                'failed_count' => 0,
            ]
        );

        $old = strtolower((string) $oldStatus);
        $new = strtolower((string) $newStatus);

        $updates = [];

        if (in_array($new, ['queued', 'sent', 'delivered', 'read'], true)
            && !in_array($old, ['queued', 'sent', 'delivered', 'read'], true)) {
            $updates['sent_count'] = $metric->sent_count + 1;
            $updates['last_sent_at'] = now();
        }

        if ($new === 'delivered' && $old !== 'delivered') {
            $updates['delivered_count'] = $metric->delivered_count + 1;
            $updates['last_success_at'] = now();
        }

        if ($new === 'failed' && $old !== 'failed') {
            $updates['failed_count'] = $metric->failed_count + 1;
            $updates['last_failure_at'] = now();
        }

        if (!empty($updates)) {
            $metric->update($updates);
            $this->refreshRates($metric);
        }
    }

    public function recordIncomingReply(IncomingMessage $message): void
    {
        $contactId = (int) ($message->business_contact_id ?? 0);
        if ($contactId <= 0) {
            return;
        }

        $metric = ContactChannelMetric::firstOrCreate(
            [
                'business_contact_id' => $contactId,
                'channel_key' => 'whatsapp',
            ],
            [
                'sent_count' => 0,
                'delivered_count' => 0,
                'replied_count' => 0,
                'converted_count' => 0,
                'failed_count' => 0,
            ]
        );

        $metric->update([
            'replied_count' => $metric->replied_count + 1,
            'last_reply_at' => now(),
        ]);

        $this->refreshRates($metric);
    }

    private function refreshRates(ContactChannelMetric $metric): void
    {
        $sent = max(0, (int) $metric->sent_count);
        $replied = max(0, (int) $metric->replied_count);
        $converted = max(0, (int) $metric->converted_count);

        $responseRate = $sent > 0 ? round(($replied / $sent) * 100, 2) : null;
        $conversionRate = $sent > 0 ? round(($converted / $sent) * 100, 2) : null;

        $metric->update([
            'response_rate' => $responseRate,
            'conversion_rate' => $conversionRate,
        ]);
    }
}
