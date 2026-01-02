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
    
            if ($this->provider === 'unified_api') {
                // Use Unified Notification API
                $result = $this->sendViaUnifiedApi($unifiedService, $outgoingMessage);
            } else {
                // Fallback to legacy WaSender
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
     * Send message via unified notification API
     */
    private function sendViaUnifiedApi(UnifiedNotificationService $service, OutgoingMessage $message)
    {
        // Get WhatsApp instance to extract UUID for schema_name
        $whatsappInstance = null;
        if ($this->whatsappInstanceId) {
            $whatsappInstance = \App\Models\WhatsappInstance::find($this->whatsappInstanceId);
        }
          
        // Use WhatsApp instance UUID as schema_name (required for multi-tenant messaging)
        $schemaName = 'default'; // fallback
        if ($whatsappInstance && $whatsappInstance->uuid) {
            $schemaName = $whatsappInstance->uuid;
        } else {
            // If no instance UUID, try to find user's primary instance
            $user = User::find($this->userId);
            if ($user) {
                $primaryInstance = $user->whatsappInstances()->where('is_primary', true)->first();
                if ($primaryInstance && $primaryInstance->uuid) {
                    $schemaName = $primaryInstance->uuid;
                } else {
                    // Get any instance from the user
                    $anyInstance = $user->whatsappInstances()->first();
                    if ($anyInstance && $anyInstance->uuid) {
                        $schemaName = $anyInstance->uuid;
                    }
                }
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
}
