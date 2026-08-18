<?php

namespace App\Jobs;

use App\Services\MultiChannel\OutboundOrchestratorService;
use App\Services\UserResolutionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
    public function handle(OutboundOrchestratorService $outboundOrchestrator)
    {
        try {
            Log::info('Processing bulk message job', [
                'user_id' => $this->userId,
                'recipient_count' => count($this->recipients),
                'source' => $this->source,
                'provider' => $this->provider,
                'rate_limit' => $this->rateLimit
            ]);

            return $this->handleViaOrchestrator($outboundOrchestrator);

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
     * Handle bulk messages via phase-4 orchestrator single entrypoint.
     */
    private function handleViaOrchestrator(OutboundOrchestratorService $outboundOrchestrator): array
    {
        $batchId = Str::uuid();
        $queuedCount = 0;
        $failedCount = 0;
        $delaySeconds = 0.0;
        $delayIncrement = 60 / max(1, (int) $this->rateLimit);
        $channel = $this->resolveChannel();

        foreach ($this->recipients as $recipient) {
            $phoneNumber = $this->extractPhoneNumber($recipient);
            if (!$phoneNumber) {
                $failedCount++;
                continue;
            }

            $personalizedMessage = $this->personalizeMessage($recipient);
            $normalizedPhone = UserResolutionService::normalizePhoneNumber($phoneNumber);
            $contactName = $this->extractName($recipient) ?? 'Bulk recipient';

            $dispatchResult = $outboundOrchestrator->dispatchDirect((int) $this->userId, $personalizedMessage, [
                'to' => $normalizedPhone,
                'channel' => $channel,
                'source' => $this->source,
                'provider' => $this->provider,
                'priority' => $this->priority,
                'delay_seconds' => (int) ceil($delaySeconds),
                'metadata' => [
                    'bulk_job' => true,
                    'batch_id' => (string) $batchId,
                    'recipient_name' => $contactName,
                    'recipient_data' => is_array($recipient) ? $recipient : ['phone' => $phoneNumber],
                    'bulk_mode' => $this->useUnifiedApi ? 'unified' : 'legacy',
                ],
            ]);

            if ($dispatchResult['success'] ?? false) {
                $queuedCount++;
            } else {
                $failedCount++;
                Log::warning('Bulk message recipient failed to queue', [
                    'user_id' => $this->userId,
                    'phone' => $normalizedPhone,
                    'batch_id' => $batchId,
                    'error' => $dispatchResult['error'] ?? 'unknown',
                ]);
            }

            $delaySeconds += $delayIncrement;
        }

        Log::info('Bulk messages routed via outbound orchestrator', [
            'user_id' => $this->userId,
            'batch_id' => $batchId,
            'channel' => $channel,
            'queued_messages' => $queuedCount,
            'failed_messages' => $failedCount,
            'rate_limit' => $this->rateLimit,
        ]);

        return [
            'success' => $failedCount === 0,
            'batch_id' => (string) $batchId,
            'queued_messages' => $queuedCount,
            'failed_messages' => $failedCount,
            'method' => 'outbound_orchestrator',
        ];
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

    private function resolveChannel(): string
    {
        $allowed = ['whatsapp', 'email', 'phone_sms', 'bulk_sms'];
        $source = strtolower((string) $this->source);

        if (in_array($source, $allowed, true)) {
            return $source;
        }

        return 'whatsapp';
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
