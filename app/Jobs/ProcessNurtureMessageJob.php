<?php

namespace App\Jobs;

use App\Models\MessageQueue;
use App\Models\BusinessContact;
use App\Models\NurtureLibrary;
use App\Models\NurtureAnalytics;
use App\Services\GhostingDetector;
use App\Services\NurtureMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessNurtureMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $messageQueueId;

    /**
     * Create a new job instance.
     */
    public function __construct($messageQueueId)
    {
        $this->messageQueueId = $messageQueueId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // 1. Fetch message queue entry
            $queueEntry = MessageQueue::find($this->messageQueueId);
            
            if (!$queueEntry) {
                Log::error("MessageQueue entry not found: {$this->messageQueueId}");
                return;
            }

            //2. Check if contact is ghosting
            $ghostingAnalysis = GhostingDetector::analyze($queueEntry->contact_id);
            
            Log::info("Ghosting analysis for contact {$queueEntry->contact_id}", [
                'is_ghosting' => $ghostingAnalysis['is_ghosting'],
                'unanswered_count' => $ghostingAnalysis['unanswered_count'],
            ]);

            if (!$ghostingAnalysis['is_ghosting']) {
                // Not ghosting, skip nurture mode
                Log::info("Contact {$queueEntry->contact_id} is not ghosting, skipping nurture mode");
                return;
            }

            // 3. Fetch contact
            $contact = BusinessContact::find($queueEntry->contact_id);
            
            if (!$contact) {
                Log::error("Contact not found: {$queueEntry->contact_id}");
                return;
            }

            // 4. Check if contact has opted out
            if (GhostingDetector::hasOptedOut($queueEntry->contact_id)) {
                Log::info("Contact {$queueEntry->contact_id} has opted out, marking as opted_out");
                $queueEntry->update(['status' => 'opted_out']);
                return;
            }

            // 5. Check for negative sentiment
            $sentimentAnalysis = GhostingDetector::detectNegativeSentiment($queueEntry->contact_id);
            if ($sentimentAnalysis['requires_human_review']) {
                Log::info("Contact {$queueEntry->contact_id} requires human review due to negative sentiment");
                $queueEntry->update([
                    'status' => 'human_review',
                    'human_review_reason' => 'Negative sentiment detected: ' . implode(', ', $sentimentAnalysis['matched_keywords']),
                ]);
                return;
            }

            // 6. Send to AI for reframing
            $refinedResult = NurtureMessageService::reframeMessage(
                $queueEntry->original_message,
                $ghostingAnalysis,
                $contact
            );

            if (!$refinedResult['success']) {
                Log::error("AI reframing failed for queue entry {$this->messageQueueId}: " . ($refinedResult['error'] ?? 'Unknown error'));
                // Fallback to original message
                $queueEntry->update([
                    'status' => 'refined',
                    'refined_message' => $queueEntry->original_message,
                ]);
                return;
            }

            Log::info("AI reframing successful for queue entry {$this->messageQueueId}");

            // 7. Update message queue
            $queueEntry->update([
                'is_nurture_mode' => true,
                'pre_nurture_message' => $queueEntry->original_message,
                'refined_message' => $refinedResult['refined_message'],
                'nurture_library_id' => $refinedResult['nugget_id'],
                'nurture_value_type' => $refinedResult['value_type'],
                'status' => 'refined',
                'ai_confidence_score' => $refinedResult['confidence_score'] ?? 0.75,
                'ai_metadata' => json_encode([
                    'tokens_used' => $refinedResult['tokens_used'] ?? 0,
                    'model' => 'gpt-4',
                    'primary_benefit' => $refinedResult['primary_benefit'] ?? null,
                    'reasoning' => $refinedResult['reasoning'] ?? null,
                ]),
            ]);

            // 8. Increment usage count on value nugget
            if ($refinedResult['nugget_id']) {
                $nugget = NurtureLibrary::find($refinedResult['nugget_id']);
                if ($nugget) {
                    $nugget->incrementUsage();
                }
            }

            // 9. Create analytics entry
            NurtureAnalytics::create([
                'nurture_library_id' => $refinedResult['nugget_id'],
                'campaign_id' => $queueEntry->campaign_id,
                'message_queue_id' => $queueEntry->id,
                'contact_id' => $contact->id,
                'days_since_last_contact' => $ghostingAnalysis['days_since_last_contact'],
                'unanswered_messages_count' => $ghostingAnalysis['unanswered_count'],
                'sent_at' => now(),
            ]);

            // 10. Send the refined message via WhatsApp
            $this->sendRefinedMessage($queueEntry, $contact);

            Log::info("Nurture message processing complete for queue entry {$this->messageQueueId}");


        } catch (\Exception $e) {
            Log::error("Error processing nurture message {$this->messageQueueId}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Mark as failed but don't throw exception (to prevent job retry)
            if (isset($queueEntry)) {
                $queueEntry->update([
                    'status' => 'failed',
                    'error_message' => 'Nurture processing error: ' . $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("ProcessNurtureMessageJob failed for queue entry {$this->messageQueueId}", [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Send the refined message via WhatsApp
     */
    private function sendRefinedMessage($queueEntry, $contact)
    {
        try {
            // Get user's WhatsApp instance
            $waSenderService = new \App\Services\WaSenderService();
            $instance = $waSenderService->getUserInstance($queueEntry->user_id);
            
            if (!$instance) {
                Log::error('No active WhatsApp instance found for user', [
                    'user_id' => $queueEntry->user_id,
                    'queue_entry_id' => $queueEntry->id,
                ]);
                $queueEntry->update([
                    'status' => 'failed',
                    'error_message' => 'No active WhatsApp instance found',
                ]);
                return;
            }

            // Dispatch SendWhatsAppMessage job with refined message
            \App\Jobs\SendWhatsAppMessage::dispatch(
                $queueEntry->refined_message,    // Use refined message instead of original
                $queueEntry->phone_number,
                'whatsapp',
                $queueEntry->user_id,
                null,                            // No files for now
                null,                            // Legacy instanceId
                [
                    'whatsapp_instance_id' => $instance->id,
                    'provider' => 'unified_api',
                    'is_nurture_message' => true, // Flag to identify nurture messages
                    'message_queue_id' => $queueEntry->id, // Link back to queue entry
                ]
            )->delay(now()->addSeconds(2)); // Small delay to ensure job processing completes

            // Update status to sent
            $queueEntry->update(['status' => 'sent']);

            Log::info("Dispatched refined nurture message", [
                'queue_entry_id' => $queueEntry->id,
                'phone' => $queueEntry->phone_number,
                'refined_message_preview' => substr($queueEntry->refined_message, 0, 100),
            ]);
        } catch (\Exception $e) {
            Log::error("Error sending refined message", [
                'queue_entry_id' => $queueEntry->id,
                'error' => $e->getMessage(),
            ]);
            
            $queueEntry->update([
                'status' => 'failed',
                'error_message' => 'Send error: ' . $e->getMessage(),
            ]);
        }
    }
}
