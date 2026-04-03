<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\MessageSentby;
use App\Models\OutgoingMessage;
use App\Models\BusinessContact;
use App\Models\User;
use App\Services\WaSenderService;
use App\Services\UnifiedNotificationService;
use App\Services\SchemaMappingService;
use App\Services\MessageStatusMapper;
use App\Services\UserResolutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class SendWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageData;
    protected $phoneNumber;
    protected $source;
    protected $userId;
    protected $files;
    protected $instanceId;
    protected $whatsappInstanceId; // New field for multi-instance support
    protected $provider;
    protected $priority;
    protected $batchId;
    protected $outgoingMessageId;
    protected $messageType; // Add message type field

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
    public $timeout = 300;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($messageData, $phoneNumber, $source = 'whatsapp', $userId = null, $files = null, $instanceId = null, $options = [])
    {
        $this->messageData = $messageData;
        $this->phoneNumber = $phoneNumber;
        $this->source = $source;
        $this->userId = $userId;
        $this->files = $files;
        $this->instanceId = $instanceId;
        $this->whatsappInstanceId = $options['whatsapp_instance_id'] ?? null; // New field
        $this->provider = $options['provider'] ?? 'unified_api';
        $this->priority = $options['priority'] ?? 'normal';
        $this->batchId = $options['batch_id'] ?? null;
        $this->outgoingMessageId = $options['outgoing_message_id'] ?? null;
        $this->messageType = $options['message_type'] ?? null; // Add message type support
      
        // Set queue based on message priority
        $this->onQueue($this->determineQueue());
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(WaSenderService $waSenderService, UnifiedNotificationService $unifiedService)
    {
        
        try {
            Log::info('Processing WhatsApp message job', [
                'phone' => $this->phoneNumber,
                'user_id' => $this->userId,
                'source' => $this->source,
                'provider' => $this->provider,
                'priority' => $this->priority
            ]);

            // Create or update OutgoingMessage record
            $outgoingMessage = $this->createOrUpdateOutgoingMessage();

            $result = null;
            
            // OTP / password-reset → Meta API first (primary OTP channel), fallback to Unified API.
            // All other messages (system notifications, regular business messages) → provider routing.
            if ($this->isOtpMessage()) {
                Log::info('OTP/password-reset message — trying Meta WhatsApp API first', [
                    'phone'        => $this->phoneNumber,
                    'message_type' => $this->messageType
                ]);
                try {
                    $result = $this->sendViaMetaWhatsAppApi($outgoingMessage);
                    Log::info('OTP sent via Meta WhatsApp API', ['phone' => $this->phoneNumber]);
                } catch (Exception $metaException) {
                    Log::warning('Meta WhatsApp API failed for OTP, falling back to Unified API', [
                        'phone' => $this->phoneNumber,
                        'error' => $metaException->getMessage()
                    ]);
                    $result = $this->sendViaUnifiedApi($unifiedService, $outgoingMessage);
                }
            } else if ($this->provider === 'unified_api') {
                // All system notifications + regular business messages via Unified API
                $result = $this->sendViaUnifiedApi($unifiedService, $outgoingMessage);
            } else {
                // Legacy WaSender fallback
                $result = $this->sendViaWaSender($waSenderService, $outgoingMessage);
            }

        
            // Update message status based on response
            $this->updateMessageStatus($outgoingMessage, $result, 'sent');

            Log::info('WhatsApp message sent successfully', [
                'phone' => $this->phoneNumber,
                'message_id' => $outgoingMessage->id,
                'external_id' => $result['external_id'] ?? null,
                'provider' => $this->provider
            ]);

        } catch (Exception $e) {
            Log::error('Failed to send WhatsApp message', [
                'phone' => $this->phoneNumber,
                'provider' => $this->provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update message status to failed
            if ($this->outgoingMessageId) {
                $this->updateMessageStatus(
                    OutgoingMessage::find($this->outgoingMessageId),
                    ['error' => $e->getMessage()],
                    'failed'
                );
            }

            // Re-throw to trigger retry mechanism
            throw $e;
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
        $errorMessage = $exception->getMessage();
        $reason       = $this->categorizeFailure($errorMessage);
        $retryable    = $this->isRetryableFailure($reason);

        Log::error('WhatsApp message job failed permanently', [
            'phone'          => $this->phoneNumber,
            'user_id'        => $this->userId,
            'error'          => $errorMessage,
            'failure_reason' => $reason,
            'retryable'      => $retryable,
            'attempts'       => $this->attempts()
        ]);

        // Write failure metadata so the retry command can make smart decisions
        $record = $this->outgoingMessageId
            ? OutgoingMessage::find($this->outgoingMessageId)
            : null;

        if ($record) {
            $record->update([
                'status'         => 'failed',
                'failure_reason' => $reason,
                'retryable'      => $retryable,
                'error_message'  => $errorMessage,
                'last_retry_at'  => now(),
                'retry_count'    => ($record->retry_count ?? 0) + 1,
            ]);
        }
    }

    /**
     * Map an exception message to a categorized failure reason.
     * Used by the retry command to decide whether/when to re-queue.
     */
    private function categorizeFailure(string $error): string
    {
        $lower = strtolower($error);

        if (str_contains($lower, 'no active whatsapp session') ||
            str_contains($lower, 'session not found') ||
            str_contains($lower, 'disconnected')) {
            return 'instance_disconnected';
        }

        if (str_contains($lower, 'expired') ||
            str_contains($lower, 'subscription') ||
            str_contains($lower, 'plan')) {
            return 'instance_expired';
        }

        if (str_contains($lower, '429') ||
            str_contains($lower, 'rate limit') ||
            str_contains($lower, 'too many requests')) {
            return 'rate_limited';
        }

        if (str_contains($lower, 'invalid number') ||
            str_contains($lower, 'not a whatsapp')) {
            return 'invalid_number';
        }

        if (str_contains($lower, 'no whatsapp instance found') ||
            str_contains($lower, 'no valid') ||
            str_contains($lower, 'orphaned') ||
            str_contains($lower, 'has no uuid')) {
            return 'bug';
        }

        return 'unknown';
    }

    /**
     * Permanent failures that must never be automatically re-queued.
     */
    private function isRetryableFailure(string $reason): bool
    {
        return $reason !== 'invalid_number';
    }

    /**
     * Determine which queue to use based on message characteristics
     */
    private function determineQueue()
    {
        // High priority messages
        if ($this->priority === 'urgent' || $this->priority === 'high') {
            return 'urgent_messages';
        }

        // Bulk queue for batch messages
        if ($this->batchId) {
            return 'bulk_messages';
        }

        // Low priority messages
        if ($this->priority === 'low') {
            return 'low_priority';
        }

        // Default messages queue
        return 'messages';
    }

    /**
     * Create or update OutgoingMessage record
     */
    private function createOrUpdateOutgoingMessage()
    {
        if ($this->outgoingMessageId) {
            $existing = OutgoingMessage::find($this->outgoingMessageId);
            if ($existing) {
                return $existing;
            }
            // Record was deleted between dispatch and execution — fall through and create a fresh one
            Log::warning('OutgoingMessage record not found, creating replacement', [
                'outgoing_message_id' => $this->outgoingMessageId
            ]);
        }

        // Resolve or create contact
        $businessContact = null;
        if ($this->userId) {
            $businessContact = UserResolutionService::resolveOrCreateContact([
                'phone' => $this->phoneNumber,
                'name' => 'Auto-created from job',
                'user_id' => $this->userId
            ]);
        }

        // Create new OutgoingMessage record
        return OutgoingMessage::create([
            'user_id' => $this->userId,
            'business_contact_id' => $businessContact ? $businessContact->id : null,
            'instance_id' => $this->instanceId,
            'whatsapp_instance_id' => $this->whatsappInstanceId, // New field
            'phone_number' => $this->phoneNumber,
            'message' => is_array($this->messageData) ? json_encode($this->messageData) : $this->messageData,
            'message_body' => is_array($this->messageData) ? ($this->messageData['message'] ?? json_encode($this->messageData)) : $this->messageData,
            'message_type' => 'text',
            'status' => 'pending',
            'provider' => $this->provider,
            'priority' => $this->priority,
            'batch_id' => $this->batchId,
            'queued_at' => now(),
            'retry_count' => 0
        ]);
    }

    /**
     * Send message via Official Meta WhatsApp API using MetaWhatsAppService
     */
    private function sendViaMetaWhatsAppApi(OutgoingMessage $message)
    {
        // Use the MetaWhatsAppService for all Meta WhatsApp operations
        $metaService = app(\App\Services\MetaWhatsAppService::class);
        
        if (!$metaService->isConfigured()) {
            throw new Exception('Meta WhatsApp API credentials not configured');
        }

        $messageBody = is_array($this->messageData) ? 
            ($this->messageData['message'] ?? json_encode($this->messageData)) : 
            $this->messageData;
        
        // Detect if this is an OTP message and use appropriate method
        $isOtpMessage = $this->messageType === 'otp_verification' || 
                        $this->messageType === 'password_reset' ||
                        preg_match('/(verification code|otp code|verify|your code is|\d{4,6})/i', $messageBody);
        
        $response = null;
        
        if ($isOtpMessage) {
            // Extract OTP code from message
            $otpCode = null;
            if (preg_match('/(\d{4,6})/', $messageBody, $matches)) {
                $otpCode = $matches[1];
            }
            
            if ($otpCode) {
                Log::info('Sending OTP via Meta WhatsApp template', [
                    'phone' => $this->phoneNumber,
                    'otp_length' => strlen($otpCode)
                ]);
                
                // Use OTP template method
                $response = $metaService->sendOtpTemplate($this->phoneNumber, $otpCode);
            } else {
                // Fallback to text message if OTP code not found
                Log::warning('OTP pattern detected but code not extracted, using text message', [
                    'phone' => $this->phoneNumber,
                    'message' => $messageBody
                ]);
                $response = $metaService->sendTextMessage($this->phoneNumber, $messageBody);
            }
        } else {
            // Use regular text message method for non-OTP messages
            Log::info('Sending text message via Meta WhatsApp', [
                'phone' => $this->phoneNumber,
                'message_type' => $this->messageType ?? 'text'
            ]);
            
            $response = $metaService->sendTextMessage($this->phoneNumber, $messageBody);
        }
        
        // Handle response
        if ($response['success'] ?? false) {
            $messageId = null;
            
            // Extract message ID from response
            if (isset($response['data']['messages'][0]['id'])) {
                $messageId = $response['data']['messages'][0]['id'];
            } elseif (isset($response['wasender_response']['message_id'])) {
                // If fallback to WaSender was used
                $messageId = $response['wasender_response']['message_id'];
            }
            
            return [
                'success' => true,
                'message_id' => $messageId,
                'external_id' => $messageId,
                'status' => 'sent',
                'via' => $response['via'] ?? 'meta',
                'api_response' => $response['data'] ?? $response
            ];
        }
        
        // If failed, throw exception with detailed error
        $errorMessage = $response['error'] ?? 'Unknown Meta WhatsApp API error';
        $errorCode = $response['error_code'] ?? 'UNKNOWN';
        
        throw new Exception("Meta WhatsApp API error [{$errorCode}]: {$errorMessage}");
    }

    /**
     * Send message via unified notification API
     */
    private function sendViaUnifiedApi(UnifiedNotificationService $service, OutgoingMessage $message)
    {
        // Determine if this is a system message that should always use system default instance
        $isSystemMessage = $this->isSystemMessage();
        $whatsappInstance = null;
        
        if ($isSystemMessage) {
            // For system messages (OTP, welcome, etc.), always use system default instance
            $whatsappInstance = \App\Models\WhatsappInstance::getSystemDefault();
            Log::info('Using system default instance for system message', [
                'phone' => $this->phoneNumber,
                'message_type' => $this->messageType,
                'instance_id' => $whatsappInstance ? $whatsappInstance->id : 'not_found'
            ]);
        } else {
            // For regular messages: explicit instance → user's primary → user's any instance
            if ($this->whatsappInstanceId) {
                $whatsappInstance = \App\Models\WhatsappInstance::find($this->whatsappInstanceId);
            }
            if (!$whatsappInstance && $this->userId) {
                $whatsappInstance = \App\Models\WhatsappInstance::where('user_id', $this->userId)
                    ->where('is_primary', true)
                    ->first()
                    ?? \App\Models\WhatsappInstance::where('user_id', $this->userId)->first();
            }
        }

        // schema_name must be users.uuid — the remote API registers tenants under users.uuid,
        // NOT whatsapp_instances.uuid which is an internal app UUID the remote API never sees.
        if (!$whatsappInstance) {
            throw new Exception(
                "No WhatsApp instance found for message sending (user_id={$this->userId}, instance_id={$this->whatsappInstanceId})"
            );
        }

        $instanceUser = $whatsappInstance->user;
        if (!$instanceUser) {
            throw new Exception("WhatsApp instance {$whatsappInstance->id} has no associated user (orphaned record)");
        }
        if (!$instanceUser->uuid) {
            throw new Exception("User {$instanceUser->id} has no UUID — cannot resolve schema_name");
        }
        $schemaName = $instanceUser->uuid;

        // Write the resolved instance back to the OutgoingMessage for audit trail
        if ($message && $message->exists && !$message->whatsapp_instance_id) {
            $message->update(['whatsapp_instance_id' => $whatsappInstance->id]);
        }

        $apiData = [
            'schema_name' => $schemaName,
            'channel' => 'whatsapp',
            'to' => UserResolutionService::normalizePhoneNumber($this->phoneNumber),
            'message' => is_array($this->messageData) ? ($this->messageData['message'] ?? json_encode($this->messageData)) : $this->messageData,
            'priority' => $this->priority,
            "type"=>"wasender"
        ];
    Log::info('Unified API data being sent', [
        'phone' => $this->phoneNumber,
        'api_data' => $apiData,
        'schema_name' => $schemaName,
        'provider' => $this->provider
    ]);
        // Add files if present — API supports one attachment per message
        if ($this->files && is_array($this->files)) {
            if (count($this->files) > 1) {
                Log::warning('SendWhatsAppMessage: multiple files provided but API only supports one attachment — only the first valid file will be sent', [
                    'phone'      => $this->phoneNumber,
                    'file_count' => count($this->files)
                ]);
            }
            foreach ($this->files as $file) {
                if (isset($file['content']) && isset($file['name'])) {
                    $apiData['attachment']      = $file['content'];
                    $apiData['attachment_name'] = $file['name'];
                    $apiData['attachment_type'] = $file['type'] ?? 'application/octet-stream';
                    break;
                }
            }
        }

        // Send via unified API
        $response = $service->sendNotification($apiData);

        // Accept both 'message_id' and 'id' keys — API response shape may vary
        $messageId     = $response['message_id'] ?? $response['id'] ?? null;
        $responseStatus = $response['status'] ?? null;

        if ($response && ($messageId || in_array($responseStatus, ['queued', 'sent', 'delivered']))) {
            return [
                'success'     => true,
                'message_id'  => $messageId,
                'external_id' => $response['external_id'] ?? $messageId,
                'status'      => $responseStatus ?? 'sent',
                'api_response' => $response
            ];
        }

        // Rate limited — release back to queue for 5 minutes instead of burning a retry
        if (isset($response['status_code']) && (int) $response['status_code'] === 429) {
            Log::warning('Unified API rate limited (429) — releasing job for 5 minutes', [
                'phone' => $this->phoneNumber
            ]);
            $this->release(300);
            return ['success' => false, 'rate_limited' => true];
        }

        throw new Exception('Unified API response invalid: ' . json_encode($response));
    }

    /**
     * Send message via legacy WaSender service
     */
    private function sendViaWaSender(WaSenderService $service, OutgoingMessage $message)
    {
        if ($this->files) {
            // Send media message
            return $service->sendMediaMessage(
                $this->phoneNumber,
                $this->messageData,
                $this->files,
                $this->instanceId,
                $this->userId
            );
        } else {
            // Send text message
            return $service->sendTextMessage(
                $this->phoneNumber,
                $this->messageData,
                $this->instanceId,
                $this->userId
            );
        }
    }

    /**
     * Update message status with response data
     */
    private function updateMessageStatus(OutgoingMessage $message, array $result, string $status)
    {
        if (!$message) return;

        $updateData = [
            'status' => MessageStatusMapper::mapToLocal($status),
            'sent_at' => now(),
            'retry_count' => $this->attempts()
        ];

        if (isset($result['external_id'])) {
            $updateData['external_id'] = $result['external_id'];
        }

        if (isset($result['message_id'])) {
            $updateData['waapi_message_id'] = $result['message_id'];
        }

        if (isset($result['api_response'])) {
            $updateData['waapi_response'] = json_encode($result['api_response']);
        }

        if (isset($result['error'])) {
            $updateData['error_message'] = $result['error'];
            $updateData['status'] = 'failed';
        }

        $message->update($updateData);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        return [30, 60, 180]; // Retry after 30s, 1min, 3mins
    }

    /**
     * Determine if this is a system message that should use system default instance
     */
    /**
     * True only when message_type is an explicit system type.
     * Never uses content-pattern matching — that caused normal business messages
     * (e.g. order #12345) to be misclassified and sent from the wrong number.
     */
    private function isSystemMessage(): bool
    {
        return $this->messageType && in_array($this->messageType, [
            'otp_verification',
            'welcome_message',
            'password_reset',
            'payment_reminder',
            'system_notification',
        ]);
    }

    /**
     * True only for OTP and password-reset types — the only ones that use Meta API.
     */
    private function isOtpMessage(): bool
    {
        return $this->messageType && in_array($this->messageType, [
            'otp_verification',
            'password_reset',
        ]);
    }
}
