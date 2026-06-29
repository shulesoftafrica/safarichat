<?php

namespace App\Services;

use App\Models\IncomingMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessAiMessage;

class WebhookProcessorService
{
    private $aiWhatsAppService;

    public function __construct(AiWhatsAppService $aiWhatsAppService)
    {
        $this->aiWhatsAppService = $aiWhatsAppService;
    }

    /**
     * Process incoming webhook from WhatsApp
     */
    public function processWebhook(array $webhookData, User $user): array
    {
        try {
            // Validate webhook data
            if (!$this->validateWebhookData($webhookData)) {
                return [
                    'success' => false,
                    'message' => 'Invalid webhook data',
                    'processed_messages' => 0,
                ];
            }

            $processedCount = 0;
            $responses = [];

            // Extract messages from webhook
            $messages = $this->extractMessages($webhookData, $user);

            foreach ($messages as $messageData) {
                try {
                    // Create incoming message record
                    $incomingMessage = $this->createIncomingMessage($messageData, $user);
                    
                    if (!$incomingMessage) {
                        continue;
                    }

                    // Try instant processing first
                    $result = $this->processInstantly($incomingMessage);
                    
                    if ($result['success']) {
                        // Send immediate response
                        if (isset($result['response'])) {
                            $sent = $this->aiWhatsAppService->sendResponse(
                                $result['response'], 
                                $incomingMessage
                            );
                            
                            if ($sent) {
                                $incomingMessage->update([
                                    'status' => 'replied',
                                    'processing_method' => 'webhook',
                                ]);
                            }
                        }

                        $responses[] = [
                            'message_id' => $incomingMessage->id,
                            'response' => $result['response'] ?? null,
                            'processed_instantly' => true,
                            'requires_human' => $result['requires_human'] ?? false,
                        ];
                    } elseif (!isset($result['skipped'])) {
                        // Only queue if NOT already skipped (e.g. duplicate processing).
                        // WAITING_FOR_USER: do not queue when the message was already
                        // handled by the instant path above.
                        //
                        // The instant path ran processIncomingWhatsAppMessageWithAI(),
                        // which atomically set status='processing'. Reset it back to
                        // 'received' so the queue job's own atomic claim can succeed.
                        DB::table('incoming_messages')
                            ->where('id', $incomingMessage->id)
                            ->where('status', 'processing')
                            ->update(['status' => 'received']);

                        $this->queueForProcessing($incomingMessage);
                        
                        $responses[] = [
                            'message_id' => $incomingMessage->id,
                            'queued' => true,
                            'reason' => $result['error'] ?? 'Processing queued',
                        ];
                    }

                    $processedCount++;

                } catch (\Exception $e) {
                    Log::error('Webhook message processing failed: ' . $e->getMessage(), [
                        'message_data' => $messageData,
                        'user_id' => $user->id,
                    ]);

                    $responses[] = [
                        'message_data' => $messageData,
                        'error' => $e->getMessage(),
                        'processed' => false,
                    ];
                }
            }

            return [
                'success' => true,
                'processed_messages' => $processedCount,
                'responses' => $responses,
                'webhook_id' => $webhookData['webhook_id'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage(), [
                'webhook_data' => $webhookData,
                'user_id' => $user->id,
            ]);

            return [
                'success' => false,
                'message' => 'Webhook processing failed: ' . $e->getMessage(),
                'processed_messages' => 0,
            ];
        }
    }

    /**
     * Validate incoming webhook data
     */
    private function validateWebhookData(array $data): bool
    {
        // Basic validation - adjust based on your webhook format
        if (!isset($data['messages']) && !isset($data['message'])) {
            return false;
        }

        return true;
    }

    /**
     * Extract messages from webhook data
     */
    private function extractMessages(array $webhookData, User $user): array
    {
        $messages = [];

        // Handle different webhook formats
        if (isset($webhookData['messages'])) {
            // Multiple messages format
            foreach ($webhookData['messages'] as $message) {
                $messages[] = $this->normalizeMessage($message, $webhookData, $user);
            }
        } elseif (isset($webhookData['message'])) {
            // Single message format
            $messages[] = $this->normalizeMessage($webhookData['message'], $webhookData, $user);
        } else {
            // Direct message format (some webhooks send message data at root level)
            $messages[] = $this->normalizeMessage($webhookData, [], $user);
        }

        return array_filter($messages); // Remove null/invalid messages
    }

    /**
     * Normalize message data from different webhook formats
     */
    private function normalizeMessage(array $messageData, array $webhookContext, User $user): ?array
    {
        // Skip outgoing messages (from the bot itself)
        if (isset($messageData['fromMe']) && $messageData['fromMe']) {
            return null;
        }

        // Skip messages that are too old (more than 5 minutes)
        if (isset($messageData['timestamp'])) {
            $messageTime = is_numeric($messageData['timestamp']) 
                ? $messageData['timestamp'] 
                : strtotime($messageData['timestamp']);
                
            if ((time() - $messageTime) > 300) { // 5 minutes
                return null;
            }
        }

        return [
            'user_id' => $user->id,
            'instance_id' => $webhookContext['instanceId'] ?? $messageData['instanceId'] ?? 'default',
            'message_id' => $messageData['id'] ?? $messageData['messageId'] ?? uniqid(),
            'chat_id' => $messageData['chatId'] ?? $messageData['from'] ?? null,
            'phone_number' => $this->extractPhoneNumber($messageData),
            'sender_name' => $messageData['senderName'] ?? $messageData['pushName'] ?? null,
            'message_body' => $this->extractMessageBody($messageData),
            'message_type' => $this->determineMessageType($messageData),
            'media_data' => $this->extractMediaData($messageData),
            'is_group' => $this->isGroupMessage($messageData),
            'message_timestamp' => $this->extractTimestamp($messageData),
            'metadata' => $messageData,
        ];
    }

    /**
     * Extract phone number from message data
     */
    private function extractPhoneNumber(array $messageData): ?string
    {
        $chatId = $messageData['chatId'] ?? $messageData['from'] ?? null;
        
        if (!$chatId) {
            return null;
        }

        // Remove @c.us or @g.us suffix
        $phone = str_replace(['@c.us', '@g.us'], '', $chatId);
        
        // Clean and validate phone number
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        return strlen($phone) >= 10 ? $phone : null;
    }

    /**
     * Extract message body/content with UTF-8 sanitization
     */
    private function extractMessageBody(array $messageData): string
    {
        $body = $messageData['body'] ?? 
                $messageData['message'] ?? 
                $messageData['text'] ?? 
                '';

        // Handle quoted messages
        if (isset($messageData['quotedMsg'])) {
            $body = $messageData['quotedMsg']['body'] ?? $body;
        }

        // Handle media messages with captions
        if (isset($messageData['caption'])) {
            $body = $messageData['caption'];
        }

        // Sanitize for UTF-8 compliance
        $body = $this->sanitizeMessageText($body);

        return trim($body);
    }

    /**
     * Sanitize message text to ensure UTF-8 compliance
     */
    private function sanitizeMessageText(string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Remove null bytes and control characters
        $text = str_replace("\0", '', $text);
        
        // Convert to UTF-8 and remove invalid sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove problematic control characters but keep line breaks
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Final UTF-8 validation
        if (!mb_check_encoding($text, 'UTF-8')) {
            Log::warning('Message contains invalid UTF-8, attempting to fix', [
                'original_length' => strlen($text),
                'detected_encoding' => mb_detect_encoding($text)
            ]);
            
            // More aggressive cleaning
            $text = mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text) ?: 'UTF-8');
            
            // Remove any remaining invalid characters
            $text = filter_var($text, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }
        
        return $text;
    }

    /**
     * Determine message type
     */
    private function determineMessageType(array $messageData): string
    {
        $type = $messageData['type'] ?? 'text';
        
        // Map different webhook type formats
        $typeMapping = [
            'chat' => 'text',
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio',
            'document' => 'document',
            'location' => 'location',
            'contact' => 'contact',
            'sticker' => 'sticker',
        ];

        return $typeMapping[$type] ?? 'other';
    }

    /**
     * Extract media data if available
     */
    private function extractMediaData(array $messageData): ?array
    {
        if (!in_array($this->determineMessageType($messageData), ['image', 'video', 'audio', 'document'])) {
            return null;
        }

        return [
            'url' => $messageData['mediaUrl'] ?? $messageData['url'] ?? null,
            'filename' => $messageData['filename'] ?? null,
            'filesize' => $messageData['filesize'] ?? null,
            'mimetype' => $messageData['mimetype'] ?? null,
            'caption' => $messageData['caption'] ?? null,
        ];
    }

    /**
     * Check if message is from group
     */
    private function isGroupMessage(array $messageData): bool
    {
        $chatId = $messageData['chatId'] ?? $messageData['from'] ?? '';
        return str_contains($chatId, '@g.us') || 
               ($messageData['isGroup'] ?? false) === true;
    }

    /**
     * Extract timestamp
     */
    private function extractTimestamp(array $messageData): string
    {
        $timestamp = $messageData['timestamp'] ?? time();
        
        if (is_numeric($timestamp)) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        
        return $timestamp;
    }

    /**
     * Create incoming message record
     */
    private function createIncomingMessage(array $messageData, User $user): ?IncomingMessage
    {
        if (!$messageData['phone_number'] || !$messageData['message_body']) {
            return null;
        }

        // Check for duplicate message
        $existing = IncomingMessage::where('message_id', $messageData['message_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return null; // Skip duplicate
        }

        return IncomingMessage::create([
            'user_id' => $messageData['user_id'],
            'instance_id' => $messageData['instance_id'],
            'message_id' => $messageData['message_id'],
            'chat_id' => $messageData['chat_id'],
            'phone_number' => $messageData['phone_number'],
            'sender_name' => $messageData['sender_name'],
            'message_body' => $messageData['message_body'],
            'message_type' => $messageData['message_type'],
            'media_data' => $messageData['media_data'],
            'is_group' => $messageData['is_group'],
            'message_timestamp' => $messageData['message_timestamp'],
            'status' => 'received',
            'processing_method' => 'webhook',
            'metadata' => $messageData['metadata'],
        ]);
    }

    /**
     * Try to process message instantly (with timeout)
     */
    private function processInstantly(IncomingMessage $message): array
    {
        try {
            // Set a timeout for instant processing
            $startTime = microtime(true);
            $timeout = 8; // 8 seconds max for instant processing
            
            $result = $this->aiWhatsAppService->processIncomingWhatsAppMessageWithAI($message);
            
            $processingTime = microtime(true) - $startTime;
            
            if ($processingTime > $timeout) {
                Log::warning('Instant processing timeout', [
                    'message_id' => $message->id,
                    'processing_time' => $processingTime,
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Processing timeout',
                    'processing_time' => $processingTime,
                ];
            }

            return array_merge($result, ['processing_time' => $processingTime]);

        } catch (\Exception $e) {
            // Mark message for cron fallback
            $message->update([
                'failed_instant_at' => now(),
                'failure_reason' => $e->getMessage(),
                'processing_attempts' => 1,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'fallback_scheduled' => true,
            ];
        }
    }

    /**
     * Queue message for background processing
     */
    private function queueForProcessing(IncomingMessage $message): void
    {
        $message->update([
            'failed_instant_at' => now(),
            'processing_method' => 'queued',
        ]);

        // Dispatch job with delay based on priority
        $delay = $this->calculateProcessingDelay($message);
        
        ProcessAiMessage::dispatch($message)->delay($delay);
    }

    /**
     * Calculate processing delay based on message priority
     */
    private function calculateProcessingDelay(IncomingMessage $message): int
    {
        // Immediate for high-priority numbers or returning customers
        $existingLead = \App\Models\Lead::where('phone_number', $message->phone_number)->first();
        
        if ($existingLead && $existingLead->calculateLeadScore() >= 80) {
            return 0; // Process immediately
        }

        if ($existingLead && $existingLead->leadProducts()->count() > 0) {
            return 30; // 30 second delay for existing customers
        }

        // Default delay for new customers
        return 60; // 1 minute delay
    }

    /**
     * Get webhook processing statistics
     */
    public function getProcessingStats(User $user, int $hours = 24): array
    {
        $since = now()->subHours($hours);
        
        $stats = IncomingMessage::where('user_id', $user->id)
            ->where('created_at', '>=', $since)
            ->selectRaw("
                COUNT(*) as total_messages,
                SUM(CASE WHEN processing_method = 'webhook' AND status = 'replied' THEN 1 ELSE 0 END) as instant_processed,
                SUM(CASE WHEN failed_instant_at IS NOT NULL THEN 1 ELSE 0 END) as failed_instant,
                AVG(CASE WHEN status = 'replied' THEN processing_attempts ELSE NULL END) as avg_processing_attempts
            ")
            ->first();

        return [
            'period_hours' => $hours,
            'total_messages' => $stats->total_messages ?? 0,
            'instant_processed' => $stats->instant_processed ?? 0,
            'failed_instant' => $stats->failed_instant ?? 0,
            'instant_success_rate' => $stats->total_messages > 0 ? 
                round(($stats->instant_processed / $stats->total_messages) * 100, 2) : 0,
            'avg_processing_attempts' => round($stats->avg_processing_attempts ?? 0, 2),
        ];
    }
}