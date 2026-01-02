<?php

namespace App\Jobs;

use App\Jobs\SendWhatsAppMessage;
use App\Models\OutgoingMessage;
use App\Models\User;
use App\Services\UnifiedNotificationService;
use App\Services\UserResolutionService;
use App\Services\MessageStatusMapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;

class ProcessBulkMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recipients;
    protected $messageContent;
    protected $userId;
    protected $source;
    protected $batchSize;
    protected $rateLimit;
    protected $priority;
    protected $provider;
    protected $useUnifiedApi;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1; // Don't retry bulk jobs

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public $timeout = 1800; // 30 minutes

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($recipients, $messageContent, $userId, $options = [])
    {
        $this->recipients = $recipients;
        $this->messageContent = $messageContent;
        $this->userId = $userId;
        $this->source = $options['source'] ?? 'whatsapp';
        $this->batchSize = $options['batch_size'] ?? 50;
        $this->rateLimit = $options['rate_limit'] ?? 60; // messages per minute
        $this->priority = $options['priority'] ?? 'normal';
        $this->provider = $options['provider'] ?? 'unified_api';
        $this->useUnifiedApi = $options['use_unified_api'] ?? true;
        
        // Set appropriate queue based on size
        $this->onQueue($this->determineQueue());
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(UnifiedNotificationService $unifiedService)
    {
        try {
            Log::info('Processing bulk message job', [
                'user_id' => $this->userId,
                'recipient_count' => count($this->recipients),
                'source' => $this->source,
                'provider' => $this->provider,
                'rate_limit' => $this->rateLimit
            ]);

            if ($this->useUnifiedApi && $this->provider === 'unified_api') {
                return $this->handleViaUnifiedApi($unifiedService);
            } else {
                return $this->handleViaLegacyMethod();
            }

        } catch (\Exception $e) {
            Log::error('Bulk message job failed', [
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle bulk messages via unified notification API
     */
    private function handleViaUnifiedApi(UnifiedNotificationService $service)
    {
        $user = User::find($this->userId);
        $schemaName = $user ? ($user->uuid ?? $user->id) : 'default';
        $batchId = Str::uuid();

        // Prepare messages for bulk API
        $messages = [];
        $outgoingMessageIds = [];

        foreach ($this->recipients as $recipient) {
            $phoneNumber = $this->extractPhoneNumber($recipient);
            if (!$phoneNumber) continue;

            $personalizedMessage = $this->personalizeMessage($recipient);
            
            // Create OutgoingMessage record for tracking
            $outgoingMessage = $this->createOutgoingMessageRecord($recipient, $personalizedMessage, $batchId);
            $outgoingMessageIds[] = $outgoingMessage->id;

            $messages[] = [
                'to' => UserResolutionService::normalizePhoneNumber($phoneNumber),
                'message' => $personalizedMessage,
                'metadata' => [
                    'outgoing_message_id' => $outgoingMessage->id,
                    'recipient_data' => is_array($recipient) ? $recipient : ['phone' => $phoneNumber]
                ]
            ];
        }

        Log::info('Prepared bulk messages for unified API', [
            'message_count' => count($messages),
            'batch_id' => $batchId
        ]);

        // Send via unified API bulk endpoint
        $bulkData = [
            'schema_name' => $schemaName,
            'channel' => 'whatsapp',
            'priority' => $this->priority,
            'rate_limit' => $this->rateLimit,
            'batch_size' => $this->batchSize,
            'messages' => $messages
        ];

        $response = $service->sendBulkNotifications($bulkData);

        if ($response && $response['success']) {
            // Update all OutgoingMessage records with batch response
            OutgoingMessage::whereIn('id', $outgoingMessageIds)->update([
                'status' => 'queued',
                'sent_at' => now(),
                'waapi_response' => json_encode($response)
            ]);

            Log::info('Bulk messages sent via unified API', [
                'batch_id' => $response['batch_id'] ?? $batchId,
                'queued_messages' => $response['queued_messages'] ?? count($messages),
                'failed_messages' => $response['failed_messages'] ?? 0
            ]);

            return $response;
        }

        throw new \Exception('Unified API bulk send failed: ' . json_encode($response));
    }

    /**
     * Handle bulk messages via legacy individual job dispatch
     */
    private function handleViaLegacyMethod()
    {
        $batchId = Str::uuid();
        $jobs = [];

        foreach ($this->recipients as $recipient) {
            $phoneNumber = $this->extractPhoneNumber($recipient);
            if (!$phoneNumber) continue;

            $personalizedMessage = $this->personalizeMessage($recipient);
            
            // Create OutgoingMessage record for tracking
            $outgoingMessage = $this->createOutgoingMessageRecord($recipient, $personalizedMessage, $batchId);

            $jobs[] = new SendWhatsAppMessage(
                $personalizedMessage,
                $phoneNumber,
                $this->source,
                $this->userId,
                null, // no files
                null, // no specific instance
                [
                    'provider' => $this->provider,
                    'priority' => $this->priority,
                    'batch_id' => $batchId,
                    'outgoing_message_id' => $outgoingMessage->id
                ]
            );
        }

        // Dispatch jobs with rate limiting
        $this->dispatchJobsWithRateLimit($jobs, $batchId);

        return [
            'success' => true,
            'batch_id' => $batchId,
            'queued_messages' => count($jobs),
            'method' => 'legacy_jobs'
        ];
    }

    /**
     * Create OutgoingMessage record for tracking
     */
    private function createOutgoingMessageRecord($recipient, $personalizedMessage, $batchId)
    {
        $phoneNumber = $this->extractPhoneNumber($recipient);
        
        // Resolve or create contact
        $businessContact = null;
        if ($this->userId && $phoneNumber) {
            $contactData = [
                'phone' => $phoneNumber,
                'name' => $this->extractName($recipient) ?? 'Bulk recipient',
                'user_id' => $this->userId
            ];
            
            $businessContact = UserResolutionService::resolveOrCreateContact($contactData);
        }

        return OutgoingMessage::create([
            'user_id' => $this->userId,
            'business_contact_id' => $businessContact ? $businessContact->id : null,
            'phone_number' => $phoneNumber,
            'message' => $personalizedMessage,
            'message_body' => $personalizedMessage,
            'message_type' => 'text',
            'status' => 'pending',
            'provider' => $this->provider,
            'priority' => $this->priority,
            'batch_id' => $batchId,
            'queued_at' => now(),
            'retry_count' => 0,
            'metadata' => json_encode([
                'bulk_job' => true,
                'recipient_data' => is_array($recipient) ? $recipient : null
            ])
        ]);
    }

    /**
     * Dispatch jobs with rate limiting
     */
    private function dispatchJobsWithRateLimit(array $jobs, string $batchId)
    {
        $batchJobs = array_chunk($jobs, $this->batchSize);
        $delay = 0;
        $delayIncrement = 60 / $this->rateLimit; // Seconds per message based on rate limit

        foreach ($batchJobs as $batchIndex => $batchJob) {
            $delay += $delayIncrement * count($batchJob);
            
            Bus::batch($batchJob)
                ->name("Bulk Messages Batch {$batchIndex} - User {$this->userId}")
                ->allowFailures()
                ->onConnection('redis')
                ->onQueue($this->determineJobQueue())
                ->delay(now()->addSeconds($delay))
                ->then(function (Batch $batch) use ($batchIndex, $batchId) {
                    Log::info("Bulk message batch {$batchIndex} completed", ['batch_id' => $batchId]);
                })
                ->catch(function (Batch $batch, \Throwable $e) use ($batchIndex, $batchId) {
                    Log::error("Bulk message batch {$batchIndex} failed", [
                        'batch_id' => $batchId,
                        'error' => $e->getMessage()
                    ]);
                })
                ->finally(function (Batch $batch) use ($batchIndex, $batchId) {
                    Log::info("Bulk message batch {$batchIndex} finished", ['batch_id' => $batchId]);
                })
                ->dispatch();
        }

        Log::info('Bulk message job dispatched with rate limiting', [
            'user_id' => $this->userId,
            'total_jobs' => count($jobs),
            'total_batches' => count($batchJobs),
            'batch_id' => $batchId,
            'rate_limit' => $this->rateLimit
        ]);
    }

    /**
     * Determine queue for this bulk job
     */
    private function determineQueue()
    {
        $recipientCount = count($this->recipients);
        
        if ($recipientCount > 1000) {
            return 'large_bulk';
        } elseif ($recipientCount > 100) {
            return 'medium_bulk';
        } else {
            return 'bulk_messages';
        }
    }

    /**
     * Determine queue for individual jobs
     */
    private function determineJobQueue()
    {
        return match($this->priority) {
            'urgent', 'high' => 'urgent_messages',
            'low' => 'low_priority',
            default => 'messages'
        };
    }

    /**
     * Extract name from recipient data
     */
    private function extractName($recipient)
    {
        if (is_array($recipient)) {
            return $recipient['guest_name'] ?? $recipient['name'] ?? null;
        }

        if (is_object($recipient)) {
            return $recipient->guest_name ?? $recipient->name ?? null;
        }

        return null;
    }

    /**
     * Extract phone number from recipient data
     */
    private function extractPhoneNumber($recipient)
    {
        if (is_string($recipient)) {
            return $recipient;
        }

        if (is_array($recipient)) {
            return $recipient['guest_phone'] ?? $recipient['phone'] ?? null;
        }

        if (is_object($recipient)) {
            return $recipient->guest_phone ?? $recipient->phone ?? null;
        }

        return null;
    }

    /**
     * Personalize message content with recipient data
     */
    private function personalizeMessage($recipient)
    {
        $message = $this->messageContent;
        
        if (is_array($recipient) || is_object($recipient)) {
            $recipientArray = is_object($recipient) ? (array) $recipient : $recipient;
            
            // Replace common placeholders
            $replacements = [
                '#name' => $recipientArray['guest_name'] ?? $recipientArray['name'] ?? 'Valued Customer',
                '#phone' => $recipientArray['guest_phone'] ?? $recipientArray['phone'] ?? '',
                '#email' => $recipientArray['guest_email'] ?? $recipientArray['email'] ?? '',
                '#pledge' => $recipientArray['guest_pledge'] ?? $recipientArray['pledge'] ?? '0',
            ];

            foreach ($replacements as $placeholder => $value) {
                $message = str_ireplace($placeholder, $value, $message);
            }
        }

        return $message;
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('Bulk message job failed permanently', [
            'user_id' => $this->userId,
            'recipient_count' => count($this->recipients),
            'error' => $exception->getMessage()
        ]);

        // Send notification to admin about bulk failure
        // You can implement admin notification here
    }
}
