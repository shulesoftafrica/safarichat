<?php

namespace App\Jobs;

use App\Models\OutgoingMessage;
use App\Models\IncomingMessage;
use App\Models\WhatsappInstance;
use App\Services\MessageStatusMapper;
use App\Services\UserResolutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $webhookData;
    protected $eventType;
    protected $source;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $webhookData, string $eventType = 'message.status', string $source = 'unified_api')
    {
        $this->webhookData = $webhookData;
        $this->eventType = $eventType;
        $this->source = $source;
        
        // Use webhook queue for processing
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Processing webhook notification', [
                'event_type' => $this->eventType,
                'source' => $this->source,
                'data_keys' => array_keys($this->webhookData)
            ]);

            switch ($this->eventType) {
                case 'message.status':
                case 'messages.update':
                    $this->handleMessageStatusUpdate();
                    break;

                case 'message.received':
                case 'messages.received':
                    $this->handleIncomingMessage();
                    break;

                case 'session.status':
                    $this->handleSessionStatusUpdate();
                    break;

                default:
                    Log::warning('Unknown webhook event type', [
                        'event_type' => $this->eventType,
                        'data' => $this->webhookData
                    ]);
            }

            Log::info('Webhook notification processed successfully', [
                'event_type' => $this->eventType
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'event_type' => $this->eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle message status updates
     */
    private function handleMessageStatusUpdate()
    {
        $messageId = $this->webhookData['message_id'] ?? $this->webhookData['id'] ?? null;
        $externalId = $this->webhookData['external_id'] ?? null;
        $status = $this->webhookData['status'] ?? $this->webhookData['delivery_status'] ?? null;

        if (!$messageId && !$externalId) {
            Log::warning('No message identifier in webhook data', $this->webhookData);
            return;
        }

        // Find the message by external_id or waapi_message_id
        $query = OutgoingMessage::query();
        
        if ($externalId) {
            $query->where('external_id', $externalId);
        } elseif ($messageId) {
            $query->where('waapi_message_id', $messageId);
        }

        $outgoingMessage = $query->first();

        if (!$outgoingMessage) {
            Log::warning('Message not found for webhook update', [
                'message_id' => $messageId,
                'external_id' => $externalId
            ]);
            return;
        }

        // Map API status to local status
        $localStatus = $status ? MessageStatusMapper::mapToLocal($status) : null;

        $updateData = [];

        if ($localStatus && $localStatus !== $outgoingMessage->status) {
            // Validate status transition
            $isValidTransition = MessageStatusMapper::isValidTransition(
                $outgoingMessage->status, 
                $localStatus
            );

            if ($isValidTransition) {
                $updateData['status'] = $localStatus;
                
                // Set delivery timestamp based on status
                switch ($localStatus) {
                    case 'sent':
                        $updateData['sent_at'] = now();
                        break;
                    case 'delivered':
                        $updateData['delivered_at'] = now();
                        break;
                    case 'read':
                        $updateData['read_at'] = now();
                        break;
                    case 'failed':
                        $updateData['error_message'] = $this->webhookData['error'] ?? 'Message delivery failed';
                        break;
                }
            } else {
                Log::warning('Invalid status transition attempted via webhook', [
                    'message_id' => $outgoingMessage->id,
                    'current_status' => $outgoingMessage->status,
                    'new_status' => $localStatus
                ]);
            }
        }

        // Update delivery information if available
        if (isset($this->webhookData['delivered_at'])) {
            $updateData['delivered_at'] = $this->webhookData['delivered_at'];
        }

        if (isset($this->webhookData['read_at'])) {
            $updateData['read_at'] = $this->webhookData['read_at'];
        }

        // Store webhook response for debugging
        $updateData['waapi_response'] = json_encode(array_merge(
            json_decode($outgoingMessage->waapi_response ?? '{}', true),
            ['webhook_update' => $this->webhookData]
        ));

        if (!empty($updateData)) {
            $outgoingMessage->update($updateData);
            
            Log::info('Message status updated via webhook', [
                'message_id' => $outgoingMessage->id,
                'old_status' => $outgoingMessage->getOriginal('status'),
                'new_status' => $updateData['status'] ?? 'unchanged',
                'updates' => array_keys($updateData)
            ]);
        }
    }

    /**
     * Handle incoming message webhooks
     */
    private function handleIncomingMessage()
    {
        $fromPhone = $this->webhookData['from'] ?? $this->webhookData['sender'] ?? null;
        $messageText = $this->webhookData['message'] ?? $this->webhookData['text'] ?? $this->webhookData['body'] ?? null;
        $messageId = $this->webhookData['message_id'] ?? $this->webhookData['id'] ?? null;
        $sessionId = $this->webhookData['session_id'] ?? $this->webhookData['instance_id'] ?? null;

        if (!$fromPhone || !$messageText) {
            Log::warning('Incomplete incoming message data', $this->webhookData);
            return;
        }

        // Normalize phone number
        $normalizedPhone = UserResolutionService::normalizePhoneNumber($fromPhone);

        // Find or create contact
        $contact = UserResolutionService::resolveOrCreateContact([
            'phone' => $normalizedPhone,
            'name' => $this->webhookData['sender_name'] ?? 'Unknown'
        ]);

        // Find WhatsApp instance
        $instance = null;
        if ($sessionId) {
            $instance = WhatsappInstance::where('instance_id', $sessionId)
                ->orWhere('api_key', $sessionId)
                ->first();
        }

        // Create incoming message record
        $incomingMessage = IncomingMessage::create([
            'user_id' => $instance ? $instance->user_id : null,
            'events_guest_id' => $contact->id,
            'instance_id' => $sessionId,
            'phone_number' => $normalizedPhone,
            'message_body' => $messageText,
            'message_type' => $this->determineMessageType($this->webhookData),
            'waapi_message_id' => $messageId,
            'received_at' => $this->webhookData['received_at'] ?? now(),
            'webhook_data' => json_encode($this->webhookData),
            'processed' => false
        ]);

        Log::info('Incoming message created via webhook', [
            'message_id' => $incomingMessage->id,
            'from_phone' => $normalizedPhone,
            'contact_id' => $contact->id,
            'instance_id' => $sessionId
        ]);

        // Trigger any auto-response logic if needed
        $this->triggerAutoResponse($incomingMessage, $instance);
    }

    /**
     * Handle session status updates
     */
    private function handleSessionStatusUpdate()
    {
        $sessionId = $this->webhookData['session_id'] ?? $this->webhookData['instance_id'] ?? null;
        $status = $this->webhookData['status'] ?? null;

        if (!$sessionId) {
            Log::warning('No session ID in webhook data', $this->webhookData);
            return;
        }

        $instance = WhatsappInstance::where('instance_id', $sessionId)
            ->orWhere('api_key', $sessionId)
            ->first();

        if (!$instance) {
            Log::warning('WhatsApp instance not found for webhook', [
                'session_id' => $sessionId
            ]);
            return;
        }

        $updateData = [];

        if ($status) {
            $updateData['status'] = $status;
            
            switch ($status) {
                case 'connected':
                    $updateData['connected_at'] = now();
                    $updateData['last_seen'] = now();
                    break;
                case 'disconnected':
                    $updateData['disconnected_at'] = now();
                    break;
            }
        }

        // Update device info if available
        if (isset($this->webhookData['device_info'])) {
            $updateData['device_info'] = json_encode($this->webhookData['device_info']);
        }

        // Update phone number if provided
        if (isset($this->webhookData['phone_number'])) {
            $updateData['phone_number'] = $this->webhookData['phone_number'];
        }

        $updateData['webhook_data'] = json_encode($this->webhookData);

        $instance->update($updateData);

        Log::info('WhatsApp instance updated via webhook', [
            'instance_id' => $instance->id,
            'session_id' => $sessionId,
            'status' => $status,
            'updates' => array_keys($updateData)
        ]);
    }

    /**
     * Determine message type from webhook data
     */
    private function determineMessageType(array $data): string
    {
        if (isset($data['type'])) {
            return $data['type'];
        }

        if (isset($data['media_url']) || isset($data['attachment'])) {
            return 'media';
        }

        if (isset($data['location'])) {
            return 'location';
        }

        return 'text';
    }

    /**
     * Trigger auto-response logic if configured
     */
    private function triggerAutoResponse(IncomingMessage $message, ?WhatsappInstance $instance)
    {
        // Check for auto-response rules
        // This can be expanded based on your business logic
        
        if (!$instance || !$instance->auto_reply_enabled) {
            return;
        }

        // Example: Auto-reply for specific keywords
        $messageText = strtolower($message->message_body);
        $autoReplyRules = [
            'hi' => 'Hello! Thank you for contacting us.',
            'hello' => 'Hello! Thank you for contacting us.',
            'help' => 'How can we help you today? Please describe your inquiry.',
            'info' => 'For more information, please visit our website or call our support line.'
        ];

        foreach ($autoReplyRules as $keyword => $reply) {
            if (str_contains($messageText, $keyword)) {
                // Dispatch auto-reply job
                SendWhatsAppMessage::dispatch(
                    $reply,
                    $message->phone_number,
                    'auto_reply',
                    $instance->user_id,
                    null,
                    $instance->instance_id,
                    [
                        'provider' => 'unified_api',
                        'priority' => 'normal'
                    ]
                );

                Log::info('Auto-reply triggered', [
                    'incoming_message_id' => $message->id,
                    'keyword' => $keyword,
                    'reply' => $reply
                ]);

                break; // Only send one auto-reply
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Webhook processing job failed permanently', [
            'event_type' => $this->eventType,
            'webhook_data' => $this->webhookData,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        return [10, 30, 60]; // Retry after 10s, 30s, 1min
    }
}