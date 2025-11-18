<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\OfficialWhatsAppService;
use App\Models\OfficialWhatsappCredential;
use App\Models\OutgoingMessage;

class SendOfficialWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $credentialId;
    protected $to;
    protected $messageType;
    protected $messageData;
    protected $options;

    public $tries = 3;
    public $backoff = [30, 60, 120]; // Backoff in seconds

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $credentialId, $to, $messageType, $messageData, $options = [])
    {
        $this->userId = $userId;
        $this->credentialId = $credentialId;
        $this->to = $to;
        $this->messageType = $messageType;
        $this->messageData = $messageData;
        $this->options = $options;
        
        // Set queue priority based on message type
        $priority = $this->getMessagePriority($messageType);
        $this->onQueue($priority);
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            Log::info('Processing official WhatsApp message', [
                'user_id' => $this->userId,
                'credential_id' => $this->credentialId,
                'to' => $this->to,
                'type' => $this->messageType
            ]);

            // Get the credential
            $credential = OfficialWhatsappCredential::forUser($this->userId)
                ->findOrFail($this->credentialId);

            // Check if credential is still active
            if (!$credential->isActive()) {
                throw new \Exception('WhatsApp credential is no longer active');
            }

            // Create service instance
            $whatsappService = new OfficialWhatsAppService($credential);

            // Send message based on type
            $result = $this->sendMessageByType($whatsappService);

            Log::info('Official WhatsApp message sent successfully', [
                'user_id' => $this->userId,
                'credential_id' => $this->credentialId,
                'to' => $this->to,
                'type' => $this->messageType,
                'message_id' => $result['message_id'] ?? null
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Failed to send official WhatsApp message', [
                'user_id' => $this->userId,
                'credential_id' => $this->credentialId,
                'to' => $this->to,
                'type' => $this->messageType,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // If this is the final attempt, mark as permanently failed
            if ($this->attempts() >= $this->tries) {
                $this->recordFailedMessage($e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Send message based on type
     */
    protected function sendMessageByType(OfficialWhatsAppService $service)
    {
        switch ($this->messageType) {
            case 'text':
                return $service->sendTextMessage(
                    $this->to,
                    $this->messageData['text'],
                    $this->options
                );

            case 'template':
                return $service->sendTemplateMessage(
                    $this->to,
                    $this->messageData['template_name'],
                    $this->messageData['components'] ?? [],
                    $this->messageData['language_code'] ?? 'en'
                );

            case 'image':
                return $service->sendImageMessage(
                    $this->to,
                    $this->messageData['image_url'],
                    $this->messageData['caption'] ?? null
                );

            case 'document':
                return $service->sendDocumentMessage(
                    $this->to,
                    $this->messageData['document_url'],
                    $this->messageData['filename'] ?? null,
                    $this->messageData['caption'] ?? null
                );

            case 'interactive_button':
                return $service->sendButtonMessage(
                    $this->to,
                    $this->messageData['body_text'],
                    $this->messageData['buttons'],
                    $this->messageData['header_text'] ?? null,
                    $this->messageData['footer_text'] ?? null
                );

            case 'interactive_list':
                return $service->sendListMessage(
                    $this->to,
                    $this->messageData['body_text'],
                    $this->messageData['sections'],
                    $this->messageData['button_text'] ?? 'Choose Option',
                    $this->messageData['header_text'] ?? null,
                    $this->messageData['footer_text'] ?? null
                );

            case 'location':
                return $service->sendLocationMessage(
                    $this->to,
                    $this->messageData['latitude'],
                    $this->messageData['longitude'],
                    $this->messageData['name'] ?? null,
                    $this->messageData['address'] ?? null
                );

            default:
                throw new \Exception('Unsupported message type: ' . $this->messageType);
        }
    }

    /**
     * Get message priority queue based on type
     */
    protected function getMessagePriority($messageType)
    {
        $priorities = [
            'template' => 'high_priority',    // Templates have higher sending limits
            'text' => 'messages',             // Regular messages
            'image' => 'messages',
            'document' => 'messages',
            'interactive_button' => 'messages',
            'interactive_list' => 'messages',
            'location' => 'messages'
        ];

        return $priorities[$messageType] ?? 'messages';
    }

    /**
     * Record failed message for manual review
     */
    protected function recordFailedMessage($errorMessage)
    {
        try {
            OutgoingMessage::create([
                'user_id' => $this->userId,
                'to_number' => $this->to,
                'from_number' => null, // Will be set by service if available
                'message_type' => $this->messageType,
                'content' => $this->getMessageContent(),
                'payload' => json_encode([
                    'message_data' => $this->messageData,
                    'options' => $this->options
                ]),
                'status' => 'failed',
                'error_message' => $errorMessage,
                'failed_at' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to record failed message', [
                'error' => $e->getMessage(),
                'original_error' => $errorMessage
            ]);
        }
    }

    /**
     * Get message content for logging
     */
    protected function getMessageContent()
    {
        switch ($this->messageType) {
            case 'text':
                return $this->messageData['text'] ?? '';
            case 'template':
                return 'Template: ' . ($this->messageData['template_name'] ?? 'Unknown');
            case 'image':
                return 'Image: ' . ($this->messageData['caption'] ?? 'No caption');
            case 'document':
                return 'Document: ' . ($this->messageData['filename'] ?? 'Unknown file');
            case 'interactive_button':
                return 'Interactive Buttons: ' . ($this->messageData['body_text'] ?? '');
            case 'interactive_list':
                return 'Interactive List: ' . ($this->messageData['body_text'] ?? '');
            case 'location':
                return sprintf(
                    'Location: %s, %s',
                    $this->messageData['latitude'] ?? 'Unknown',
                    $this->messageData['longitude'] ?? 'Unknown'
                );
            default:
                return 'Unknown message type: ' . $this->messageType;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Official WhatsApp message job failed permanently', [
            'user_id' => $this->userId,
            'credential_id' => $this->credentialId,
            'to' => $this->to,
            'type' => $this->messageType,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        $this->recordFailedMessage($exception->getMessage());
    }

    /**
     * Static helper to dispatch text message
     */
    public static function sendText($userId, $credentialId, $to, $text, $options = [])
    {
        return self::dispatch($userId, $credentialId, $to, 'text', [
            'text' => $text
        ], $options);
    }

    /**
     * Static helper to dispatch template message
     */
    public static function sendTemplate($userId, $credentialId, $to, $templateName, $components = [], $languageCode = 'en')
    {
        return self::dispatch($userId, $credentialId, $to, 'template', [
            'template_name' => $templateName,
            'components' => $components,
            'language_code' => $languageCode
        ]);
    }

    /**
     * Static helper to dispatch image message
     */
    public static function sendImage($userId, $credentialId, $to, $imageUrl, $caption = null)
    {
        return self::dispatch($userId, $credentialId, $to, 'image', [
            'image_url' => $imageUrl,
            'caption' => $caption
        ]);
    }

    /**
     * Static helper to dispatch interactive button message
     */
    public static function sendButtons($userId, $credentialId, $to, $bodyText, $buttons, $headerText = null, $footerText = null)
    {
        return self::dispatch($userId, $credentialId, $to, 'interactive_button', [
            'body_text' => $bodyText,
            'buttons' => $buttons,
            'header_text' => $headerText,
            'footer_text' => $footerText
        ]);
    }

    /**
     * Static helper to dispatch interactive list message
     */
    public static function sendList($userId, $credentialId, $to, $bodyText, $sections, $buttonText = 'Choose Option', $headerText = null, $footerText = null)
    {
        return self::dispatch($userId, $credentialId, $to, 'interactive_list', [
            'body_text' => $bodyText,
            'sections' => $sections,
            'button_text' => $buttonText,
            'header_text' => $headerText,
            'footer_text' => $footerText
        ]);
    }
}
