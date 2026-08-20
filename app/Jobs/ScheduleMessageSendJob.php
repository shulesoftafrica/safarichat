<?php

namespace App\Jobs;

use App\Models\MessageQueue;
use App\Models\Campaign;
use App\Models\OutgoingMessage;
use App\Models\CampaignAnalytics;
use App\Services\WaSenderService;
use App\Services\BillingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Job to send scheduled personalized messages from message_queue
 * 
 * This job is responsible for:
 * - Sending personalized messages at their optimal time
 * - Creating outgoing_message records for tracking
 * - Updating campaign counters and analytics
 * - Handling billing/credit deduction
 * - Managing retry logic for failed sends
 */
class ScheduleMessageSendJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageQueue;

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
    public $timeout = 60;

    /**
     * Create a new job instance.
     *
     * @param MessageQueue $messageQueue
     * @return void
     */
    public function __construct(MessageQueue $messageQueue)
    {
        $this->messageQueue = $messageQueue;

        // Determine queue based on priority
        $queue = $this->determineQueue($messageQueue->priority);
        $this->onQueue($queue);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(WaSenderService $waSenderService, BillingService $billingService)
    {
        try {
            Log::info('Processing scheduled message', [
                'message_queue_id' => $this->messageQueue->id,
                'contact' => $this->messageQueue->contact_name,
                'phone' => $this->messageQueue->phone_number,
                'scheduled_for' => $this->messageQueue->scheduled_send_at
            ]);

            // Reload message to get latest status
            $this->messageQueue->refresh();

            // Verify message is still scheduled
            if ($this->messageQueue->status !== MessageQueue::STATUS_SCHEDULED) {
                Log::warning('Message no longer scheduled, skipping', [
                    'message_queue_id' => $this->messageQueue->id,
                    'current_status' => $this->messageQueue->status
                ]);
                return;
            }

            // Verify user has sufficient credits
            if (!$this->verifyCredits($billingService)) {
                $this->handleInsufficientCredits();
                return;
            }

            // Mark as sending
            $this->messageQueue->update([
                'status' => 'sending',
                'retry_count' => $this->messageQueue->retry_count + 1
            ]);

            // Update campaign counter
            if ($this->messageQueue->campaign_id) {
                $this->messageQueue->campaign->decrementCounter('scheduled_count');
            }

            // Send via WaSender API
            $result = $this->sendMessage($waSenderService);

            if ($result['success']) {
                $this->handleSuccessfulSend($result);
            } else {
                $this->handleFailedSend($result['error'] ?? 'Unknown error');
            }

        } catch (Exception $e) {
            Log::error('Failed to send scheduled message', [
                'message_queue_id' => $this->messageQueue->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->handleFailedSend($e->getMessage());

            // Re-throw to trigger retry if attempts remain
            if ($this->attempts() < $this->tries) {
                throw $e;
            }
        }
    }

    /**
     * Send message via WaSender API
     *
     * @param WaSenderService $waSenderService
     * @return array
     */
    protected function sendMessage(WaSenderService $waSenderService)
    {
        try {
            // Use refined message if available, fallback to original
            $message = $this->messageQueue->refined_message ?? $this->messageQueue->original_message;

            // Prepare send options
            $options = [
                'priority' => $this->messageQueue->priority >= 8 ? 'urgent' : 'normal',
                'metadata' => [
                    'campaign_id' => $this->messageQueue->campaign_id,
                    'message_queue_id' => $this->messageQueue->id,
                    'is_personalized' => !empty($this->messageQueue->refined_message)
                ]
            ];

            // Send via WaSender (campaigns always use WaSender, never Meta)
            $result = $waSenderService->sendTextMessage(
                $this->messageQueue->phone_number,
                $message,
                null, // Use default instance
                $this->messageQueue->user_id,
                $options
            );

            Log::info('WaSender API response', [
                'message_queue_id' => $this->messageQueue->id,
                'success' => $result['success'] ?? false,
                'external_id' => $result['id'] ?? null
            ]);

            // Deliver any campaign attachments (PDF/doc) after the text send.
            // A failed attachment must NOT fail the whole send — the text already went.
            if (($result['success'] ?? false)) {
                $this->sendCampaignAttachments($waSenderService);
            }

            return $result;

        } catch (Exception $e) {
            Log::error('WaSender API error', [
                'message_queue_id' => $this->messageQueue->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Deliver campaign attachments (documents/PDF) via WaSender after the text send.
     *
     * Files were uploaded on the campaign and persisted on campaigns.attachments.
     * Each is uploaded to WaSender and sent as a document to the recipient.
     * Any failure is logged and swallowed so it never fails the (already sent) text.
     *
     * @param WaSenderService $waSenderService
     * @return void
     */
    protected function sendCampaignAttachments(WaSenderService $waSenderService): void
    {
        try {
            $campaign = $this->messageQueue->campaign;
            $attachments = $campaign?->attachments;

            if (empty($attachments) || !is_array($attachments)) {
                return;
            }

            foreach ($attachments as $attachment) {
                $path = is_array($attachment) ? ($attachment['path'] ?? null) : (is_string($attachment) ? $attachment : null);
                if (empty($path)) {
                    continue;
                }

                $filename = is_array($attachment) ? ($attachment['original_name'] ?? $attachment['filename'] ?? null) : null;

                try {
                    $docResult = $waSenderService->sendDocument(
                        $this->messageQueue->phone_number,
                        $path,                       // storage path — WaSenderService resolves + uploads it
                        $filename,
                        null,                        // no caption; text was already sent as its own message
                        null,                        // default instance
                        $this->messageQueue->user_id
                    );

                    Log::info('Campaign attachment sent', [
                        'message_queue_id' => $this->messageQueue->id,
                        'campaign_id'      => $campaign->id,
                        'file'             => $filename ?? $path,
                        'success'          => $docResult['success'] ?? false,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Failed to send campaign attachment', [
                        'message_queue_id' => $this->messageQueue->id,
                        'file'             => $filename ?? $path,
                        'error'            => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Never let attachment handling break the send pipeline.
            Log::error('sendCampaignAttachments failed', [
                'message_queue_id' => $this->messageQueue->id,
                'error'            => $e->getMessage(),
            ]);
        }
    }

    /**
     * Record the sent campaign message as a Conversation on the recipient's Lead,
     * so that when the recipient replies the AI has context on what it sent and can
     * answer "what is this?" instead of starting cold.
     *
     * Creates a lightweight Lead for the contact when none exists yet (campaign
     * contacts often have no Lead). Fail-open: never breaks the send.
     *
     * @param string $sentText
     * @return void
     */
    protected function recordCampaignConversation(string $sentText): void
    {
        try {
            $contactId = $this->messageQueue->contact_id;
            if (!$contactId) {
                return;
            }

            $contact = \App\Models\BusinessContact::find($contactId);
            if (!$contact) {
                return;
            }

            $userId = $this->messageQueue->user_id;
            $agentId = \App\Models\AiSalesAgent::where('user_id', $userId)->value('id');

            // Reuse the same lead the inbound handler will resolve on reply
            // (it looks up by business_contact_id + user_id).
            $lead = \App\Models\Lead::firstOrCreate(
                [
                    'business_contact_id' => $contact->id,
                    'user_id'             => $userId,
                ],
                [
                    'business_id'         => $contact->business_id,
                    'ai_sales_agent_id'   => $agentId,
                    'name'                => $contact->guest_name,
                    'phone_number'        => $contact->guest_phone,
                    'source'              => 'campaign',
                    'status'              => \App\Models\Lead::STATUS_OUTREACHED,
                    'last_contact_at'     => now(),
                    'last_interaction_at' => now(),
                    'lead_score'          => 10,
                ]
            );

            // Attach an agent if the lead had none, so inbound findBestAgent has a preference.
            if (!$lead->ai_sales_agent_id && $agentId) {
                $lead->update(['ai_sales_agent_id' => $agentId]);
            }

            // Record the outbound campaign message in the format getConversationHistory reads
            // (ai_response = what we sent). This gives the reply its context.
            // message_type/sender_type must satisfy the conversations CHECK constraints:
            //   message_type ∈ {CUSTOMER, AI_AGENT, HUMAN_AGENT}
            //   sender_type  ∈ {customer, ai_agent, user_manual, ai_agent_followup}
            \App\Models\Conversation::create([
                'lead_id'           => $lead->id,
                'ai_sales_agent_id' => $lead->ai_sales_agent_id,
                'message_type'      => \App\Models\Conversation::TYPE_AI_AGENT,
                'sender_type'       => 'ai_agent',
                'message_content'   => $sentText,
                'ai_response'       => $sentText,
                'state'             => 'active',
                'is_active'         => true,
                'created_at'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('recordCampaignConversation failed', [
                'message_queue_id' => $this->messageQueue->id,
                'error'            => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle successful message send
     *
     * @param array $result
     * @return void
     */
    protected function handleSuccessfulSend($result)
    {
        DB::transaction(function () use ($result) {
            // Create outgoing_message record for tracking
            $outgoingMessage = OutgoingMessage::create([
                'campaign_id' => $this->messageQueue->campaign_id,
                'message_queue_id' => $this->messageQueue->id,
                'user_id' => $this->messageQueue->user_id,
                'phone_number' => $this->messageQueue->phone_number,
                'message' => $this->messageQueue->refined_message ?? $this->messageQueue->original_message,
                'original_message' => $this->messageQueue->original_message,
                'is_personalized' => !empty($this->messageQueue->refined_message),
                'personalization_metadata' => [
                    'detected_language' => $this->messageQueue->detected_language,
                    'detected_tone' => $this->messageQueue->detected_tone,
                    'relationship_stage' => $this->messageQueue->relationship_stage,
                    'ai_confidence_score' => $this->messageQueue->ai_confidence_score,
                    'sentiment' => $this->messageQueue->sentiment_filter_result
                ],
                'external_id' => $result['id'] ?? $result['message_id'] ?? null,
                'status' => 'sent',
                'sent_at' => now(),
                'provider' => 'wasender',
                'credits_used' => $this->messageQueue->credits_used ?? 5 // 2 AI + 3 WaSender
            ]);

            // Update message queue
            $this->messageQueue->update([
                'status' => MessageQueue::STATUS_SENT,
                'sent_at' => now(),
                'external_message_id' => $result['id'] ?? $result['message_id'] ?? null
            ]);

            // Update campaign counters
            if ($this->messageQueue->campaign) {
                $campaign = $this->messageQueue->campaign;
                $campaign->incrementCounter('sent_count');

                // Check if campaign is complete
                $this->checkCampaignCompletion($campaign);
            }

            // Update campaign analytics
            $this->updateAnalytics('sent');

            Log::info('Message sent successfully', [
                'message_queue_id' => $this->messageQueue->id,
                'outgoing_message_id' => $outgoingMessage->id,
                'external_id' => $outgoingMessage->external_id
            ]);
        });

        // Record the sent message as a Conversation so a reply has context
        // ("what is this?" can be answered by the AI). Done AFTER the tracking
        // transaction commits so a hiccup here can never roll back the send record.
        $this->recordCampaignConversation(
            $this->messageQueue->refined_message ?? $this->messageQueue->original_message
        );
    }

    /**
     * Handle failed message send
     *
     * @param string $error
     * @return void
     */
    protected function handleFailedSend($error)
    {
        DB::transaction(function () use ($error) {
            // Update message queue
            $this->messageQueue->update([
                'status' => MessageQueue::STATUS_FAILED,
                'error_message' => $error
            ]);

            // Update campaign counters
            if ($this->messageQueue->campaign) {
                $campaign = $this->messageQueue->campaign;
                $campaign->incrementCounter('failed_count');

                // Auto-pause campaign if failure rate exceeds threshold
                $this->checkFailureRate($campaign);
            }

            // Update campaign analytics
            $this->updateAnalytics('failed');

            Log::error('Message send failed', [
                'message_queue_id' => $this->messageQueue->id,
                'error' => $error,
                'retry_count' => $this->messageQueue->retry_count
            ]);
        });
    }

    /**
     * Verify user has sufficient credits
     *
     * @param BillingService $billingService
     * @return bool
     */
    protected function verifyCredits(BillingService $billingService)
    {
        try {
            // Calculate required credits (3 for WaSender send)
            $requiredCredits = 3;

            // Check if user has sufficient credits
            $hasCredits = BillingService::hasCredits(
                $this->messageQueue->user_id,
                $requiredCredits
            );

            if (!$hasCredits) {
                Log::warning('Insufficient credits for message send', [
                    'user_id' => $this->messageQueue->user_id,
                    'required_credits' => $requiredCredits,
                    'message_queue_id' => $this->messageQueue->id
                ]);
            }

            return $hasCredits;

        } catch (Exception $e) {
            Log::error('Error verifying credits', [
                'user_id' => $this->messageQueue->user_id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle insufficient credits
     *
     * @return void
     */
    protected function handleInsufficientCredits()
    {
        $this->messageQueue->update([
            'status' => MessageQueue::STATUS_FAILED,
            'error_message' => 'Insufficient credits to send message'
        ]);

        // Pause campaign if credits exhausted
        if ($this->messageQueue->campaign) {
            $campaign = $this->messageQueue->campaign;
            $campaign->pause();
            $campaign->update([
                'error_message' => 'Campaign paused: Insufficient credits'
            ]);

            Log::critical('Campaign paused due to insufficient credits', [
                'campaign_id' => $campaign->id,
                'user_id' => $this->messageQueue->user_id
            ]);
        }
    }

    /**
     * Check if campaign should be marked complete
     *
     * @param Campaign $campaign
     * @return void
     */
    protected function checkCampaignCompletion(Campaign $campaign)
    {
        $pendingCount = $campaign->queued_count 
                      + $campaign->analyzing_count 
                      + $campaign->refined_count 
                      + $campaign->scheduled_count;

        if ($pendingCount <= 0) {
            $campaign->update([
                'status' => Campaign::STATUS_COMPLETED,
                'completed_at' => now()
            ]);

            Log::info('Campaign completed', [
                'campaign_id' => $campaign->id,
                'total_recipients' => $campaign->total_recipients,
                'sent_count' => $campaign->sent_count,
                'failed_count' => $campaign->failed_count
            ]);
        }
    }

    /**
     * Check campaign failure rate and auto-pause if too high
     *
     * @param Campaign $campaign
     * @return void
     */
    protected function checkFailureRate(Campaign $campaign)
    {
        $totalProcessed = $campaign->sent_count + $campaign->failed_count;

        if ($totalProcessed < 10) {
            return; // Need at least 10 messages to calculate meaningful rate
        }

        $failureRate = $campaign->failed_count / $totalProcessed;

        // Auto-pause if failure rate exceeds 10%
        if ($failureRate > 0.1) {
            $campaign->pause();
            $campaign->update([
                'error_message' => sprintf(
                    'Campaign auto-paused: High failure rate (%.1f%%)',
                    $failureRate * 100
                )
            ]);

            Log::critical('Campaign auto-paused due to high failure rate', [
                'campaign_id' => $campaign->id,
                'failure_rate' => $failureRate,
                'sent_count' => $campaign->sent_count,
                'failed_count' => $campaign->failed_count
            ]);
        }
    }

    /**
     * Update campaign analytics
     *
     * @param string $status
     * @return void
     */
    protected function updateAnalytics($status)
    {
        if (!$this->messageQueue->campaign_id) {
            return;
        }

        try {
            $analytics = CampaignAnalytics::firstOrCreate(
                ['campaign_id' => $this->messageQueue->campaign_id],
                [
                    'total_sent' => 0,
                    'total_delivered' => 0,
                    'total_read' => 0,
                    'total_replied' => 0,
                    'total_failed' => 0,
                    'avg_confidence_score' => 0,
                    'credits_spent' => 0
                ]
            );

            if ($status === 'sent') {
                $analytics->increment('total_sent');
                $analytics->increment('credits_spent', 3); // 3 credits for WaSender send

                // Update average confidence score
                $this->updateAverageConfidence($analytics);
            } elseif ($status === 'failed') {
                $analytics->increment('total_failed');
            }

        } catch (Exception $e) {
            Log::error('Failed to update campaign analytics', [
                'campaign_id' => $this->messageQueue->campaign_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update average confidence score for campaign analytics
     *
     * @param CampaignAnalytics $analytics
     * @return void
     */
    protected function updateAverageConfidence(CampaignAnalytics $analytics)
    {
        $avgScore = MessageQueue::where('campaign_id', $this->messageQueue->campaign_id)
            ->whereNotNull('ai_confidence_score')
            ->avg('ai_confidence_score');

        if ($avgScore) {
            $analytics->update(['avg_confidence_score' => round($avgScore, 2)]);
        }
    }

    /**
     * Determine which queue to use based on priority
     *
     * @param int $priority
     * @return string
     */
    protected function determineQueue($priority)
    {
        if ($priority >= 8) {
            return 'high-priority';
        } elseif ($priority <= 3) {
            return 'low-priority';
        }

        return 'default';
    }

    /**
     * Handle job failure after all retries exhausted
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::critical('Message send job failed after all retries', [
            'message_queue_id' => $this->messageQueue->id,
            'error' => $exception->getMessage(),
            'retry_count' => $this->messageQueue->retry_count
        ]);

        $this->handleFailedSend('Failed after ' . $this->tries . ' attempts: ' . $exception->getMessage());
    }

    /**
     * Job tags for monitoring
     *
     * @return array
     */
    public function tags()
    {
        return [
            'scheduled-send',
            'message:' . $this->messageQueue->id,
            'campaign:' . ($this->messageQueue->campaign_id ?? 'none'),
            'priority:' . $this->messageQueue->priority
        ];
    }
}
