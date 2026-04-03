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
            
            // Special handling for system messages: Try Meta API first, fallback to Unified API
            if ($this->isSystemMessage()) {
                Log::info('System message detected, attempting Meta WhatsApp API first', [
                    'phone' => $this->phoneNumber,
                    'message_type' => $this->messageType
                ]);
                
                try {
                    // Try Meta WhatsApp API first for system messages
                    $result = $this->sendViaMetaWhatsAppApi($outgoingMessage);
                    Log::info('System message sent successfully via Meta WhatsApp API', [
                        'phone' => $this->phoneNumber,
                        'message_id' => $outgoingMessage->id
                    ]);
                } catch (Exception $metaException) {
                    // If Meta API fails, fallback to Unified API for system messages
                    Log::warning('Meta WhatsApp API failed for system message, falling back to Unified API', [
                        'phone' => $this->phoneNumber,
                        'error' => $metaException->getMessage()
                    ]);
                    
                    $result = $this->sendViaUnifiedApi($unifiedService, $outgoingMessage);
                    Log::info('System message sent successfully via Unified API (fallback)', [
                        'phone' => $this->phoneNumber,
                        'message_id' => $outgoingMessage->id
                    ]);
                }
            } else if ($this->provider === 'unified_api') {
                // Use Unified Notification API for non-system messages
                $result = $this->sendViaUnifiedApi($unifiedService, $outgoingMessage);
            } else {
                // Fallback to legacy WaSender for non-system messages
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
        Log::error('WhatsApp message job failed permanently', [
            'phone' => $this->phoneNumber,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Send notification to admin about permanent failure
        // You can implement admin notification here
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
            return OutgoingMessage::find($this->outgoingMessageId);
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
            // For regular messages, try to find the specified instance
            if ($this->whatsappInstanceId) {
                $whatsappInstance = \App\Models\WhatsappInstance::find($this->whatsappInstanceId);
            }
        }
          
        // Resolve schema_name as users.uuid — the remote notifications API registers tenants
        // under users.uuid (see WaSenderController::registerWithUnifiedNotificationApi).
        // whatsapp_instances.uuid is an internal app UUID and is NOT recognised by the API.
        $schemaName = null;

        // Priority 1: walk the WhatsApp instance → its owning user → users.uuid
        if ($whatsappInstance) {
            $instanceUser = $whatsappInstance->relationLoaded('user')
                ? $whatsappInstance->user
                : $whatsappInstance->user()->first();
            if ($instanceUser && $instanceUser->uuid) {
                $schemaName = $instanceUser->uuid;
                Log::debug('schema_name resolved via whatsapp instance owner', [
                    'instance_id'  => $whatsappInstance->id,
                    'user_id'      => $instanceUser->id,
                    'schema_name'  => $schemaName,
                ]);
            } elseif ($instanceUser) {
                $schemaName = 'user_' . $instanceUser->id;
            }
        }

        // Priority 2: resolve directly from $this->userId
        if (!$schemaName && $this->userId) {
            $user = User::find($this->userId);
            if ($user && $user->uuid) {
                $schemaName = $user->uuid;
                Log::debug('schema_name resolved via userId', [
                    'user_id'     => $user->id,
                    'schema_name' => $schemaName,
                ]);
            } elseif ($user) {
                $schemaName = 'user_' . $user->id;
            }
        }

        // Final fallback — should never be reached in normal operation
        if (!$schemaName) {
            Log::warning('schema_name could not be resolved from instance or userId; falling back to system default user', [
                'phone'       => $this->phoneNumber,
                'user_id'     => $this->userId,
                'instance_id' => $whatsappInstance?->id,
            ]);
            $systemInstance = \App\Models\WhatsappInstance::getSystemDefault();
            $systemUser = $systemInstance?->user()->first();
            if ($systemUser && $systemUser->uuid) {
                $schemaName = $systemUser->uuid;
            } elseif ($systemUser) {
                $schemaName = 'user_' . $systemUser->id;
            } else {
                throw new Exception('No valid users.uuid could be resolved for schema_name');
            }
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
        // Add files if present
        if ($this->files && is_array($this->files)) {
            foreach ($this->files as $file) {
                if (isset($file['content']) && isset($file['name'])) {
                    $apiData['attachment'] = $file['content'];
                    $apiData['attachment_name'] = $file['name'];
                    $apiData['attachment_type'] = $file['type'] ?? 'application/octet-stream';
                    break; // API supports single attachment per message
                }
            }
        }

        // Send via unified API
        $response = $service->sendNotification($apiData);

        if ($response && isset($response['message_id'])) {
            return [
                'success' => true,
                'message_id' => $response['message_id'],
                'external_id' => $response['external_id'] ?? null,
                'status' => $response['status'] ?? 'sent',
                'api_response' => $response
            ];
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
    private function isSystemMessage(): bool
    {
        // Check if message type is explicitly set as a system message type
        if ($this->messageType) {
            $systemMessageTypes = ['otp_verification', 'welcome_message', 'password_reset', 'payment_reminder', 'system_notification'];
            if (in_array($this->messageType, $systemMessageTypes)) {
                return true;
            }
        }

        // Check if this appears to be a system message based on priority and user context
        if ($this->priority === 'high' && !$this->userId) {
            return true; // High priority message with no user ID is likely system message
        }

        // Check message content patterns for OTP, welcome, etc.
        $messageText = is_array($this->messageData) ? 
            ($this->messageData['message'] ?? json_encode($this->messageData)) : 
            $this->messageData;
        
        $messageText = strtolower($messageText);
        
        // OTP patterns
        if (preg_match('/(verification code|otp|verify|code|\d{4,6})/', $messageText)) {
            return true;
        }
        
        // Welcome patterns
        if (preg_match('/(welcome|greeting|hello.*safarichat|thank you for joining)/', $messageText)) {
            return true;
        }
        
        // Password reset patterns
        if (preg_match('/(password|reset|forgot|change password)/', $messageText)) {
            return true;
        }

        // Check if whatsappInstanceId points to system default instance
        if ($this->whatsappInstanceId) {
            $instance = \App\Models\WhatsappInstance::find($this->whatsappInstanceId);
            if ($instance && $instance->is_system_default) {
                return true;
            }
        }

        return false;
    }
}
