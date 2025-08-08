<?php

namespace App\Jobs;

use App\Jobs\SendWhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

class ProcessBulkMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $recipients;
    protected $messageContent;
    protected $userId;
    protected $source;
    protected $batchSize;

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
    public function __construct($recipients, $messageContent, $userId, $source = 'whatsapp', $batchSize = 50)
    {
        $this->recipients = $recipients;
        $this->messageContent = $messageContent;
        $this->userId = $userId;
        $this->source = $source;
        $this->batchSize = $batchSize;
        $this->onQueue('bulk_messages');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Log::info('Processing bulk message job', [
                'user_id' => $this->userId,
                'recipient_count' => count($this->recipients),
                'source' => $this->source
            ]);

            // Split recipients into batches
            $batches = array_chunk($this->recipients, $this->batchSize);
            $totalBatches = count($batches);

            Log::info('Split into batches', [
                'total_recipients' => count($this->recipients),
                'batch_size' => $this->batchSize,
                'total_batches' => $totalBatches
            ]);

            // Create jobs for each batch
            $jobs = [];
            foreach ($batches as $batchIndex => $batch) {
                foreach ($batch as $recipient) {
                    $phoneNumber = $this->extractPhoneNumber($recipient);
                    $personalizedMessage = $this->personalizeMessage($recipient);
                    
                    if ($phoneNumber) {
                        $jobs[] = new SendWhatsAppMessage(
                            $personalizedMessage,
                            $phoneNumber,
                            $this->source,
                            $this->userId
                        );
                    }
                }
            }

            // Dispatch jobs in batches with proper delay to avoid rate limiting
            $batchJobs = array_chunk($jobs, $this->batchSize);
            $delay = 0;

            foreach ($batchJobs as $batchIndex => $batchJob) {
                // Add progressive delay between batches to manage rate limits
                $delay += 10; // 10 seconds between each batch
                
                Bus::batch($batchJob)
                    ->name("Bulk Messages Batch {$batchIndex} - User {$this->userId}")
                    ->allowFailures()
                    ->onConnection('redis')
                    ->onQueue('messages')
                    ->delay(now()->addSeconds($delay))
                    ->then(function (Batch $batch) use ($batchIndex) {
                        Log::info("Bulk message batch {$batchIndex} completed successfully");
                    })
                    ->catch(function (Batch $batch, \Throwable $e) use ($batchIndex) {
                        Log::error("Bulk message batch {$batchIndex} failed", [
                            'error' => $e->getMessage()
                        ]);
                    })
                    ->finally(function (Batch $batch) use ($batchIndex) {
                        Log::info("Bulk message batch {$batchIndex} finished processing");
                    })
                    ->dispatch();
            }

            Log::info('Bulk message job completed', [
                'user_id' => $this->userId,
                'total_jobs_created' => count($jobs),
                'total_batches_dispatched' => count($batchJobs)
            ]);

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
