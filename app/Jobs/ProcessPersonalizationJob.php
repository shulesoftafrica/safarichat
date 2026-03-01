<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\MessageQueue;
use App\Services\MessagePersonalizationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessPersonalizationJob
 * 
 * Queue job to process message personalization using AI.
 * Handles individual message personalization or batch processing for campaigns.
 * 
 * Usage:
 * - ProcessPersonalizationJob::dispatch($messageQueue); // Single message
 * - ProcessPersonalizationJob::dispatch(null, $campaign, 50); // Batch of 50
 */
class ProcessPersonalizationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The message queue item to personalize (single mode)
     */
    private ?MessageQueue $messageQueue;

    /**
     * The campaign to batch process (batch mode)
     */
    private ?Campaign $campaign;

    /**
     * Batch size for campaign processing
     */
    private int $batchSize;

    /**
     * Create a new job instance.
     *
     * @param MessageQueue|null $messageQueue Single message to process
     * @param Campaign|null $campaign Campaign to batch process
     * @param int $batchSize Number of messages to process in batch
     */
    public function __construct(
        ?MessageQueue $messageQueue = null,
        ?Campaign $campaign = null,
        int $batchSize = 50
    ) {
        $this->messageQueue = $messageQueue;
        $this->campaign = $campaign;
        $this->batchSize = $batchSize;

        // Set queue based on priority
        if ($messageQueue && $messageQueue->priority >= 8) {
            $this->onQueue('high-priority');
        } elseif ($campaign) {
            $this->onQueue('personalization');
        } else {
            $this->onQueue('default');
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MessagePersonalizationService $personalizationService)
    {
        try {
            if ($this->messageQueue) {
                // Single message processing
                $this->processSingleMessage($personalizationService);
            } elseif ($this->campaign) {
                // Batch processing
                $this->processCampaignBatch($personalizationService);
            } else {
                Log::error('ProcessPersonalizationJob called with no message or campaign');
                $this->fail(new \Exception('No message or campaign provided'));
            }

        } catch (\Exception $e) {
            Log::error('ProcessPersonalizationJob failed: ' . $e->getMessage(), [
                'message_queue_id' => $this->messageQueue?->id,
                'campaign_id' => $this->campaign?->id,
                'exception' => $e->getTraceAsString()
            ]);

            // Mark as failed in database
            if ($this->messageQueue) {
                $this->messageQueue->incrementRetry('Personalization job failed: ' . $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Process a single message
     */
    private function processSingleMessage(MessagePersonalizationService $service): void
    {
        Log::info("Processing personalization for MessageQueue {$this->messageQueue->id}");

        // Update status to analyzing
        $this->messageQueue->update(['status' => MessageQueue::STATUS_ANALYZING]);
        
        if ($this->messageQueue->campaign) {
            $this->messageQueue->campaign->incrementCounter('analyzing_count');
            $this->messageQueue->campaign->decrementCounter('queued_count');
        }

        // Personalize the message
        $result = $service->personalizeMessage($this->messageQueue);

        // Update message queue with results
        if ($result['refined_message']) {
            $this->messageQueue->update([
                'refined_message' => $result['refined_message'],
                'status' => MessageQueue::STATUS_PERSONALIZED,
                'detected_language' => $result['analysis']['detected_language'] ?? null,
                'detected_tone' => $result['analysis']['detected_tone'] ?? null,
                'relationship_stage' => $result['analysis']['relationship_stage'] ?? null,
                'ai_confidence_score' => $result['analysis']['ai_confidence_score'] ?? 0,
                'sentiment_filter_result' => $result['analysis']['sentiment_filter_result'] ?? null,
                'context_summary' => $result['analysis']['context_summary'] ?? [],
                'ai_metadata' => $result['analysis']['ai_metadata'] ?? [],
                'optimal_send_time' => $result['analysis']['optimal_send_time'] ?? null
            ]);

            if ($this->messageQueue->campaign) {
                $this->messageQueue->campaign->incrementCounter('refined_count');
                $this->messageQueue->campaign->decrementCounter('analyzing_count');
            }

            Log::info("Successfully personalized MessageQueue {$this->messageQueue->id}", [
                'confidence' => $result['analysis']['ai_confidence_score'],
                'language' => $result['analysis']['detected_language'],
                'sentiment' => $result['analysis']['sentiment_filter_result']
            ]);

            // If personalized and ready, schedule for sending
            if ($this->messageQueue->status === MessageQueue::STATUS_PERSONALIZED) {
                $this->scheduleMessageForSending();
            }

        } else {
            // Message needs review or contact opted out
            Log::warning("Message personalization returned no refined message for MessageQueue {$this->messageQueue->id}");
            
            if ($this->messageQueue->campaign) {
                $this->messageQueue->campaign->decrementCounter('analyzing_count');
                
                if ($this->messageQueue->status === MessageQueue::STATUS_HUMAN_REVIEW) {
                    $this->messageQueue->campaign->incrementCounter('human_review_count');
                } elseif ($this->messageQueue->status === MessageQueue::STATUS_OPTED_OUT) {
                    // Don't count opted out messages in failed counter
                }
            }
        }
    }

    /**
     * Process a batch of messages for a campaign
     */
    private function processCampaignBatch(MessagePersonalizationService $service): void
    {
        Log::info("Processing personalization batch for Campaign {$this->campaign->id}", [
            'batch_size' => $this->batchSize
        ]);

        // Update campaign status if needed
        if ($this->campaign->status === Campaign::STATUS_STAGING) {
            $this->campaign->update([
                'status' => Campaign::STATUS_PROCESSING,
                'started_at' => now()
            ]);
        }

        // Batch personalize
        $result = $service->batchPersonalizeCampaign($this->campaign, $this->batchSize);

        Log::info("Completed personalization batch for Campaign {$this->campaign->id}", $result);

        // Check if there are more messages to process
        $remainingMessages = MessageQueue::where('campaign_id', $this->campaign->id)
            ->where('status', MessageQueue::STATUS_STAGED)
            ->count();

        if ($remainingMessages > 0) {
            // Dispatch another batch job
            Log::info("Dispatching next batch for Campaign {$this->campaign->id}, remaining: {$remainingMessages}");
            ProcessPersonalizationJob::dispatch(null, $this->campaign, $this->batchSize)
                ->delay(now()->addSeconds(5)); // Small delay to prevent overwhelming API
        } else {
            // All messages processed, update campaign status
            Log::info("All messages processed for Campaign {$this->campaign->id}");
            
            // Check if any messages are ready to schedule
            $readyToSchedule = MessageQueue::where('campaign_id', $this->campaign->id)
                ->where('status', MessageQueue::STATUS_PERSONALIZED)
                ->count();

            if ($readyToSchedule > 0) {
                // Update status to scheduled
                $this->campaign->update(['status' => Campaign::STATUS_SCHEDULED]);
                Log::info("Campaign {$this->campaign->id} moved to SCHEDULED status");
            } else {
                // No messages ready (all failed or need review)
                $this->campaign->update(['status' => Campaign::STATUS_PAUSED]);
                Log::warning("Campaign {$this->campaign->id} paused - no messages ready to send");
            }
        }
    }

    /**
     * Schedule a personalized message for sending
     */
    private function scheduleMessageForSending(): void
    {
        // Determine send time
        $sendTime = $this->messageQueue->optimal_send_time 
            ? \Carbon\Carbon::parse($this->messageQueue->optimal_send_time)
            : now()->addMinutes(5); // Default: send in 5 minutes

        $this->messageQueue->update([
            'status' => MessageQueue::STATUS_SCHEDULED,
            'scheduled_send_at' => $sendTime
        ]);

        if ($this->messageQueue->campaign) {
            $this->messageQueue->campaign->incrementCounter('scheduled_count');
            $this->messageQueue->campaign->decrementCounter('refined_count');
        }

        Log::info("MessageQueue {$this->messageQueue->id} scheduled for {$sendTime}");

        // Phase 3: Dispatch send job at optimal time
        // The ScheduleMessageSendJob will be picked up by SendScheduledMessagesCommand
        // which runs every minute via cron. For immediate sends (within 1 minute),
        // we could dispatch directly, but relying on cron ensures consistency
        // Note: For urgent messages (priority >= 8), consider immediate dispatch
        if ($this->messageQueue->priority >= 8 && $sendTime->isFuture() && $sendTime->diffInMinutes(now()) <= 1) {
            // Dispatch immediately for urgent messages scheduled within 1 minute
            ScheduleMessageSendJob::dispatch($this->messageQueue);
            Log::info("Urgent message dispatched immediately", [
                'message_queue_id' => $this->messageQueue->id,
                'priority' => $this->messageQueue->priority
            ]);
        }
        // Otherwise, let the scheduler command pick it up (more scalable for bulk campaigns)
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('ProcessPersonalizationJob permanently failed', [
            'message_queue_id' => $this->messageQueue?->id,
            'campaign_id' => $this->campaign?->id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        if ($this->messageQueue) {
            $this->messageQueue->update([
                'status' => MessageQueue::STATUS_FAILED,
                'error_message' => 'Personalization failed after ' . $this->tries . ' attempts: ' . $exception->getMessage()
            ]);

            if ($this->messageQueue->campaign) {
                $this->messageQueue->campaign->incrementCounter('failed_count');
                $this->messageQueue->campaign->decrementCounter('analyzing_count');
            }
        }

        if ($this->campaign) {
            // Pause campaign if too many failures
            $failedCount = MessageQueue::where('campaign_id', $this->campaign->id)
                ->where('status', MessageQueue::STATUS_FAILED)
                ->count();

            $failureRate = $failedCount / max(1, $this->campaign->total_recipients);

            if ($failureRate > 0.1) { // More than 10% failure rate
                $this->campaign->pause();
                Log::critical("Campaign {$this->campaign->id} auto-paused due to high failure rate: {$failureRate}");
            }
        }
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        $tags = ['personalization'];

        if ($this->messageQueue) {
            $tags[] = 'message:' . $this->messageQueue->id;
            if ($this->messageQueue->campaign_id) {
                $tags[] = 'campaign:' . $this->messageQueue->campaign_id;
            }
        }

        if ($this->campaign) {
            $tags[] = 'campaign:' . $this->campaign->id;
            $tags[] = 'batch';
        }

        return $tags;
    }
}
