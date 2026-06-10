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
use Exception;

/**
 * Job to personalize campaign messages using AI
 * 
 * This job processes messages in the MessageQueue that are in 'staged' status,
 * analyzes conversation history, and uses AI to personalize each message
 * based on the contact's language, tone, and relationship stage.
 * 
 * Features:
 * - Batch processing (configurable batch size)
 * - AI-powered personalization via MessagePersonalizationService
 * - Language and tone detection
 * - Sentiment analysis and opt-out detection
 * - Optimal send time calculation
 * - Automatic scheduling of personalized messages
 */
class PersonalizeCampaignMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Campaign ID to process
     *
     * @var int|null
     */
    protected $campaignId;

    /**
     * Batch size for processing
     *
     * @var int
     */
    protected $batchSize;

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
    public $timeout = 300; // 5 minutes for batch processing

    /**
     * Create a new job instance.
     *
     * @param int|null $campaignId Specific campaign ID, or null to process all campaigns
     * @param int $batchSize Number of messages to process in this batch
     * @return void
     */
    public function __construct($campaignId = null, $batchSize = 50)
    {
        $this->campaignId = $campaignId;
        $this->batchSize = $batchSize;
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     *
     * @param MessagePersonalizationService $personalizationService
     * @return void
     */
    public function handle(MessagePersonalizationService $personalizationService)
    {
        try {
            Log::info('Starting campaign message personalization', [
                'campaign_id' => $this->campaignId,
                'batch_size' => $this->batchSize
            ]);

            // Get messages to personalize
            $query = MessageQueue::where('status', MessageQueue::STATUS_STAGED);
            
            if ($this->campaignId) {
                $query->where('campaign_id', $this->campaignId);
            }
            
            $messages = $query->orderBy('created_at', 'asc')
                ->limit($this->batchSize)
                ->get();

            if ($messages->isEmpty()) {
                Log::info('No staged messages found for personalization', [
                    'campaign_id' => $this->campaignId
                ]);
                return;
            }

            $stats = [
                'total' => $messages->count(),
                'personalized' => 0,
                'opted_out' => 0,
                'human_review' => 0,
                'failed' => 0
            ];

            // Process each message
            foreach ($messages as $message) {
                try {
                    $this->processMessage($message, $personalizationService, $stats);
                } catch (Exception $e) {
                    Log::error('Failed to personalize individual message', [
                        'message_queue_id' => $message->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    $message->update([
                        'status' => MessageQueue::STATUS_FAILED,
                        'error_message' => $e->getMessage()
                    ]);
                    
                    if ($message->campaign_id) {
                        $message->campaign->incrementCounter('failed_count');
                    }
                    
                    $stats['failed']++;
                }
            }

            Log::info('Campaign message personalization batch completed', array_merge([
                'campaign_id' => $this->campaignId
            ], $stats));

            // If there are more messages to process, dispatch another job
            $remainingCount = MessageQueue::where('status', MessageQueue::STATUS_STAGED)
                ->when($this->campaignId, function ($q) {
                    return $q->where('campaign_id', $this->campaignId);
                })
                ->count();

            if ($remainingCount > 0) {
                Log::info('Dispatching next personalization batch', [
                    'campaign_id' => $this->campaignId,
                    'remaining_messages' => $remainingCount
                ]);
                
                // Dispatch next batch with a 10-second delay to avoid rate limits
                self::dispatch($this->campaignId, $this->batchSize)
                    ->delay(now()->addSeconds(10));
            }

        } catch (Exception $e) {
            Log::error('Campaign message personalization job failed', [
                'campaign_id' => $this->campaignId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Process a single message through personalization
     *
     * @param MessageQueue $message
     * @param MessagePersonalizationService $personalizationService
     * @param array $stats
     * @return void
     */
    protected function processMessage(MessageQueue $message, MessagePersonalizationService $personalizationService, &$stats)
    {
        // Update status to analyzing
        $message->update(['status' => MessageQueue::STATUS_ANALYZING]);
        
        // Update campaign counter if available
        if ($message->campaign_id) {
            $message->campaign->incrementCounter('analyzing_count');
            $message->campaign->decrementCounter('queued_count');
        }

        Log::info('Personalizing message', [
            'message_queue_id' => $message->id,
            'contact_id' => $message->contact_id,
            'phone' => $message->phone_number
        ]);

        // Call personalization service
        $result = $personalizationService->personalizeMessage($message);

        // Handle result based on analysis
        if (isset($result['analysis']['opted_out']) && $result['analysis']['opted_out']) {
            // Contact has opted out
            $message->update([
                'status' => MessageQueue::STATUS_OPTED_OUT,
                'sentiment_filter_result' => MessageQueue::SENTIMENT_OPT_OUT
            ]);
            
            if ($message->campaign_id) {
                $message->campaign->incrementCounter('opted_out_count');
                $message->campaign->decrementCounter('analyzing_count');
            }
            
            $stats['opted_out']++;
            
            Log::warning('Contact has opted out', [
                'message_queue_id' => $message->id,
                'contact_id' => $message->contact_id
            ]);
            
            return;
        }

        // Check if message needs human review
        if (!$result['refined_message'] || 
            (isset($result['analysis']['ai_confidence_score']) && 
             $result['analysis']['ai_confidence_score'] < 0.6)) {
            
            $message->update([
                'status' => MessageQueue::STATUS_HUMAN_REVIEW,
                'human_review_reason' => $result['analysis']['error'] ?? 'Low AI confidence score',
                'ai_confidence_score' => $result['analysis']['ai_confidence_score'] ?? 0,
                'ai_metadata' => $result['analysis']['ai_metadata'] ?? []
            ]);
            
            if ($message->campaign_id) {
                $message->campaign->incrementCounter('human_review_count');
                $message->campaign->decrementCounter('analyzing_count');
            }
            
            $stats['human_review']++;
            
            Log::info('Message flagged for human review', [
                'message_queue_id' => $message->id,
                'reason' => $result['analysis']['error'] ?? 'Low confidence'
            ]);
            
            return;
        }

        // Check for negative sentiment
        if (isset($result['analysis']['sentiment_filter_result']) && 
            in_array($result['analysis']['sentiment_filter_result'], [
                MessageQueue::SENTIMENT_OPT_OUT,
                MessageQueue::SENTIMENT_NEGATIVE
            ])) {
            
            $message->update([
                'status' => MessageQueue::STATUS_HUMAN_REVIEW,
                'human_review_reason' => 'Negative sentiment or opt-out detected',
                'refined_message' => $result['refined_message'],
                'sentiment_filter_result' => $result['analysis']['sentiment_filter_result'],
                'detected_language' => $result['analysis']['detected_language'] ?? null,
                'detected_tone' => $result['analysis']['detected_tone'] ?? null,
                'ai_confidence_score' => $result['analysis']['ai_confidence_score'] ?? 0,
                'context_summary' => $result['analysis']['context_summary'] ?? [],
                'ai_metadata' => $result['analysis']['ai_metadata'] ?? []
            ]);
            
            if ($message->campaign_id) {
                $message->campaign->incrementCounter('human_review_count');
                $message->campaign->decrementCounter('analyzing_count');
            }
            
            $stats['human_review']++;
            
            Log::warning('Negative sentiment detected, flagged for review', [
                'message_queue_id' => $message->id,
                'sentiment' => $result['analysis']['sentiment_filter_result']
            ]);
            
            return;
        }

        // Successfully personalized - update message with AI analysis
        $message->update([
            'refined_message' => $result['refined_message'],
            'status' => MessageQueue::STATUS_REFINED,
            'detected_language' => $result['analysis']['detected_language'] ?? null,
            'detected_tone' => $result['analysis']['detected_tone'] ?? null,
            'relationship_stage' => $result['analysis']['relationship_stage'] ?? null,
            'ai_confidence_score' => $result['analysis']['ai_confidence_score'] ?? 0,
            'sentiment_filter_result' => $result['analysis']['sentiment_filter_result'] ?? null,
            'context_summary' => $result['analysis']['context_summary'] ?? [],
            'ai_metadata' => $result['analysis']['ai_metadata'] ?? [],
            'optimal_send_time' => $result['analysis']['optimal_send_time'] ?? null
        ]);

        if ($message->campaign_id) {
            $message->campaign->incrementCounter('refined_count');
            $message->campaign->decrementCounter('analyzing_count');
        }

        $stats['personalized']++;

        Log::info('Message successfully personalized', [
            'message_queue_id' => $message->id,
            'detected_language' => $result['analysis']['detected_language'] ?? 'unknown',
            'detected_tone' => $result['analysis']['detected_tone'] ?? 'unknown',
            'ai_confidence' => $result['analysis']['ai_confidence_score'] ?? 0,
            'optimal_send_time' => $result['analysis']['optimal_send_time'] ?? 'immediate'
        ]);

        // Schedule message for optimal send time
        $this->scheduleMessage($message);
    }

    /**
     * Schedule a personalized message for delivery
     *
     * @param MessageQueue $message
     * @return void
     */
    protected function scheduleMessage(MessageQueue $message)
    {
        // Determine send time
        $sendTime = $message->optimal_send_time 
            ? \Carbon\Carbon::parse($message->optimal_send_time) 
            : now()->addMinutes(5); // Default to 5 minutes if no optimal time

        // Update status to scheduled
        $message->update([
            'status' => MessageQueue::STATUS_SCHEDULED,
            'scheduled_send_at' => $sendTime
        ]);

        if ($message->campaign_id) {
            $message->campaign->incrementCounter('scheduled_count');
            $message->campaign->decrementCounter('refined_count');
        }

        // Dispatch scheduled send job
        ScheduleMessageSendJob::dispatch($message)
            ->delay($sendTime);

        Log::info('Message scheduled for delivery', [
            'message_queue_id' => $message->id,
            'scheduled_for' => $sendTime->toDateTimeString(),
            'delay_minutes' => now()->diffInMinutes($sendTime)
        ]);
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('PersonalizeCampaignMessagesJob failed permanently', [
            'campaign_id' => $this->campaignId,
            'batch_size' => $this->batchSize,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Mark campaign as having errors if campaign_id is set
        if ($this->campaignId) {
            try {
                $campaign = Campaign::find($this->campaignId);
                if ($campaign) {
                    $campaign->update([
                        'error_message' => 'Personalization job failed: ' . $exception->getMessage()
                    ]);
                }
            } catch (Exception $e) {
                Log::error('Failed to update campaign error status', [
                    'campaign_id' => $this->campaignId,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
