<?php

namespace App\Services;

use App\Models\OutgoingMessage;
use App\Models\MessageQueue;
use App\Models\Campaign;
use App\Models\CampaignAnalytics;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Handle webhook updates from WaSender for message delivery status
 * 
 * This service processes delivery status updates for campaign messages:
 * - Message delivered
 * - Message read
 * - Message failed
 * - Reply received
 * 
 * Updates both outgoing_messages and campaign_analytics tables
 */
class CampaignWebhookHandler
{
    /**
     * Handle message status update webhook
     *
     * @param array $webhookData
     * @return array
     */
    public function handleMessageStatusUpdate(array $webhookData)
    {
        try {
            Log::info('Processing campaign message status webhook', [
                'webhook_data' => $webhookData
            ]);

            // Extract webhook data
            $externalId = $webhookData['message_id'] ?? $webhookData['id'] ?? null;
            $status = $webhookData['status'] ?? null;
            $eventType = $webhookData['event'] ?? 'message.status';

            if (!$externalId || !$status) {
                Log::warning('Invalid webhook data: missing message_id or status', [
                    'webhook_data' => $webhookData
                ]);
                return ['success' => false, 'error' => 'Missing required fields'];
            }

            // Find outgoing message by external ID
            $outgoingMessage = OutgoingMessage::where('external_id', $externalId)->first();

            if (!$outgoingMessage) {
                Log::warning('Outgoing message not found for external ID', [
                    'external_id' => $externalId
                ]);
                return ['success' => false, 'error' => 'Message not found'];
            }

            // Process status update
            $result = $this->updateMessageStatus($outgoingMessage, $status, $webhookData);

            return ['success' => true, 'message' => 'Status updated', 'result' => $result];

        } catch (\Exception $e) {
            Log::error('Error processing campaign webhook', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData,
                'trace' => $e->getTraceAsString()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update message status based on webhook event
     *
     * @param OutgoingMessage $outgoingMessage
     * @param string $status
     * @param array $webhookData
     * @return array
     */
    protected function updateMessageStatus(OutgoingMessage $outgoingMessage, string $status, array $webhookData)
    {
        DB::transaction(function () use ($outgoingMessage, $status, $webhookData) {
            $previousStatus = $outgoingMessage->status;

            // Normalize status
            $normalizedStatus = $this->normalizeStatus($status);

            // Prevent backwards status transitions
            if (!$this->isValidStatusTransition($previousStatus, $normalizedStatus)) {
                Log::warning('Invalid status transition', [
                    'message_id' => $outgoingMessage->id,
                    'from' => $previousStatus,
                    'to' => $normalizedStatus
                ]);
                return;
            }

            // Update outgoing message
            $updates = [
                'status' => $normalizedStatus,
                'updated_at' => now()
            ];

            // Set timestamps based on status
            switch ($normalizedStatus) {
                case 'delivered':
                    $updates['delivered_at'] = $this->parseWebhookTimestamp($webhookData);
                    break;

                case 'read':
                    $updates['read_at'] = $this->parseWebhookTimestamp($webhookData);
                    // If delivered_at not set, assume it was delivered
                    if (!$outgoingMessage->delivered_at) {
                        $updates['delivered_at'] = now();
                    }
                    break;

                case 'failed':
                    $updates['error_message'] = $webhookData['error'] ?? $webhookData['error_message'] ?? 'Delivery failed';
                    break;
            }

            $outgoingMessage->update($updates);

            Log::info('Message status updated', [
                'outgoing_message_id' => $outgoingMessage->id,
                'from_status' => $previousStatus,
                'to_status' => $normalizedStatus,
                'campaign_id' => $outgoingMessage->campaign_id
            ]);

            // Update campaign analytics if message is part of campaign
            if ($outgoingMessage->campaign_id) {
                $this->updateCampaignAnalytics($outgoingMessage, $normalizedStatus, $previousStatus);
            }
        });

        return [
            'message_id' => $outgoingMessage->id,
            'previous_status' => $previousStatus,
            'new_status' => $normalizedStatus
        ];
    }

    /**
     * Handle incoming reply to campaign message
     *
     * @param array $webhookData
     * @return array
     */
    public function handleReply(array $webhookData)
    {
        try {
            Log::info('Processing campaign reply webhook', [
                'webhook_data' => $webhookData
            ]);

            // Extract data
            $phoneNumber = $webhookData['from'] ?? $webhookData['phone'] ?? null;
            $replyMessage = $webhookData['message'] ?? $webhookData['text'] ?? null;
            $replyTimestamp = $this->parseWebhookTimestamp($webhookData);

            if (!$phoneNumber) {
                return ['success' => false, 'error' => 'Missing phone number'];
            }

            // Find most recent outgoing message to this contact
            $outgoingMessage = OutgoingMessage::where('phone_number', $phoneNumber)
                ->whereNotNull('campaign_id')
                ->orderBy('sent_at', 'desc')
                ->first();

            if (!$outgoingMessage) {
                Log::info('No campaign message found for reply', [
                    'phone' => $phoneNumber
                ]);
                return ['success' => true, 'message' => 'No campaign message found'];
            }

            // Update outgoing message with reply info
            $outgoingMessage->update([
                'reply_received' => true,
                'reply_received_at' => $replyTimestamp,
                'reply_message' => $replyMessage
            ]);

            // Update campaign analytics
            if ($outgoingMessage->campaign_id) {
                $this->incrementReplyCount($outgoingMessage->campaign_id);
            }

            // Analyze sentiment of reply if AI is available
            $sentiment = $this->analyzeSentiment($replyMessage);
            if ($sentiment) {
                $this->updateReplySentiment($outgoingMessage->campaign_id, $sentiment);
            }

            Log::info('Reply processed successfully', [
                'outgoing_message_id' => $outgoingMessage->id,
                'campaign_id' => $outgoingMessage->campaign_id,
                'phone' => $phoneNumber,
                'sentiment' => $sentiment
            ]);

            return [
                'success' => true,
                'message' => 'Reply processed',
                'sentiment' => $sentiment
            ];

        } catch (\Exception $e) {
            Log::error('Error processing campaign reply', [
                'error' => $e->getMessage(),
                'webhook_data' => $webhookData
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update campaign analytics based on status change
     *
     * @param OutgoingMessage $outgoingMessage
     * @param string $newStatus
     * @param string $previousStatus
     * @return void
     */
    protected function updateCampaignAnalytics(OutgoingMessage $outgoingMessage, string $newStatus, string $previousStatus)
    {
        $analytics = CampaignAnalytics::firstOrCreate(
            ['campaign_id' => $outgoingMessage->campaign_id],
            [
                'total_sent' => 0,
                'total_delivered' => 0,
                'total_read' => 0,
                'total_replied' => 0,
                'total_failed' => 0
            ]
        );

        // Increment new status counter
        switch ($newStatus) {
            case 'delivered':
                $analytics->increment('total_delivered');
                $this->updateDeliveryRate($analytics);
                break;

            case 'read':
                $analytics->increment('total_read');
                $this->updateReadRate($analytics);
                break;

            case 'failed':
                $analytics->increment('total_failed');
                break;
        }

        // Calculate time-to-delivery if applicable
        if ($newStatus === 'delivered' && $outgoingMessage->sent_at && $outgoingMessage->delivered_at) {
            $deliveryTime = $outgoingMessage->sent_at->diffInSeconds($outgoingMessage->delivered_at);
            $this->updateAverageDeliveryTime($analytics, $deliveryTime);
        }

        // Calculate time-to-read if applicable
        if ($newStatus === 'read' && $outgoingMessage->sent_at && $outgoingMessage->read_at) {
            $readTime = $outgoingMessage->sent_at->diffInSeconds($outgoingMessage->read_at);
            $this->updateAverageReadTime($analytics, $readTime);
        }

        Log::info('Campaign analytics updated', [
            'campaign_id' => $outgoingMessage->campaign_id,
            'status' => $newStatus,
            'delivered' => $analytics->total_delivered,
            'read' => $analytics->total_read,
            'failed' => $analytics->total_failed
        ]);
    }

    /**
     * Normalize webhook status to standard values
     *
     * @param string $status
     * @return string
     */
    protected function normalizeStatus(string $status)
    {
        $status = strtolower($status);

        $statusMap = [
            'sent' => 'sent',
            'delivered' => 'delivered',
            'read' => 'read',
            'failed' => 'failed',
            'error' => 'failed',
            'delivery_failed' => 'failed',
            'seen' => 'read',
            'received' => 'delivered'
        ];

        return $statusMap[$status] ?? $status;
    }

    /**
     * Check if status transition is valid
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    protected function isValidStatusTransition(string $from, string $to)
    {
        // Valid transitions
        $validTransitions = [
            'sent' => ['delivered', 'read', 'failed'],
            'delivered' => ['read', 'failed'],
            'read' => [], // Read is final success state
            'failed' => [] // Failed is final failure state
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }

    /**
     * Parse timestamp from webhook data
     *
     * @param array $webhookData
     * @return Carbon|null
     */
    protected function parseWebhookTimestamp(array $webhookData)
    {
        $timestamp = $webhookData['timestamp'] 
                  ?? $webhookData['delivered_at'] 
                  ?? $webhookData['read_at']
                  ?? null;

        if (!$timestamp) {
            return now();
        }

        try {
            return Carbon::parse($timestamp);
        } catch (\Exception $e) {
            Log::warning('Failed to parse webhook timestamp', [
                'timestamp' => $timestamp
            ]);
            return now();
        }
    }

    /**
     * Update delivery rate percentage
     *
     * @param CampaignAnalytics $analytics
     * @return void
     */
    protected function updateDeliveryRate(CampaignAnalytics $analytics)
    {
        if ($analytics->total_sent > 0) {
            $rate = ($analytics->total_delivered / $analytics->total_sent) * 100;
            $analytics->update(['delivery_rate' => round($rate, 2)]);
        }
    }

    /**
     * Update read rate percentage
     *
     * @param CampaignAnalytics $analytics
     * @return void
     */
    protected function updateReadRate(CampaignAnalytics $analytics)
    {
        if ($analytics->total_sent > 0) {
            $rate = ($analytics->total_read / $analytics->total_sent) * 100;
            $analytics->update(['read_rate' => round($rate, 2)]);
        }
    }

    /**
     * Update average delivery time
     *
     * @param CampaignAnalytics $analytics
     * @param int $newDeliveryTime
     * @return void
     */
    protected function updateAverageDeliveryTime(CampaignAnalytics $analytics, int $newDeliveryTime)
    {
        $currentAvg = $analytics->avg_delivery_time_seconds ?? 0;
        $count = $analytics->total_delivered;

        // Calculate new average
        $newAvg = (($currentAvg * ($count - 1)) + $newDeliveryTime) / $count;

        $analytics->update(['avg_delivery_time_seconds' => round($newAvg, 2)]);
    }

    /**
     * Update average read time
     *
     * @param CampaignAnalytics $analytics
     * @param int $newReadTime
     * @return void
     */
    protected function updateAverageReadTime(CampaignAnalytics $analytics, int $newReadTime)
    {
        $currentAvg = $analytics->avg_read_time_seconds ?? 0;
        $count = $analytics->total_read;

        // Calculate new average
        $newAvg = (($currentAvg * ($count - 1)) + $newReadTime) / $count;

        $analytics->update(['avg_read_time_seconds' => round($newAvg, 2)]);
    }

    /**
     * Increment reply count for campaign
     *
     * @param int $campaignId
     * @return void
     */
    protected function incrementReplyCount(int $campaignId)
    {
        $analytics = CampaignAnalytics::firstOrCreate(
            ['campaign_id' => $campaignId],
            ['total_replied' => 0]
        );

        $analytics->increment('total_replied');

        // Update reply rate
        if ($analytics->total_sent > 0) {
            $rate = ($analytics->total_replied / $analytics->total_sent) * 100;
            $analytics->update(['reply_rate' => round($rate, 2)]);
        }
    }

    /**
     * Analyze sentiment of reply message
     *
     * @param string|null $message
     * @return string|null
     */
    protected function analyzeSentiment(?string $message)
    {
        if (!$message) {
            return null;
        }

        // Simple keyword-based sentiment analysis
        $positiveKeywords = ['thank', 'thanks', 'great', 'good', 'yes', 'interested', 'asante', 'sawa'];
        $negativeKeywords = ['no', 'not interested', 'stop', 'unsubscribe', 'hapana', 'remove'];

        $messageLower = strtolower($message);

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                $positiveCount++;
            }
        }

        foreach ($negativeKeywords as $keyword) {
            if (strpos($messageLower, $keyword) !== false) {
                $negativeCount++;
            }
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Update reply sentiment counts for campaign
     *
     * @param int $campaignId
     * @param string $sentiment
     * @return void
     */
    protected function updateReplySentiment(int $campaignId, string $sentiment)
    {
        $analytics = CampaignAnalytics::firstOrCreate(['campaign_id' => $campaignId]);

        $field = 'reply_sentiment_' . $sentiment;
        if (in_array($sentiment, ['positive', 'neutral', 'negative'])) {
            $analytics->increment($field);
        }
    }
}
