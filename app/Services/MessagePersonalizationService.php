<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\MessageQueue;
use App\Models\BusinessContact;
use App\Models\Conversation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * MessagePersonalizationService
 * 
 * Handles AI-powered personalization of campaign messages using OpenAI GPT-4.
 * Features:
 * - Language detection (English, Swahili, mixed)
 * - Tone matching (formal, casual, urgent, friendly)
 * - Relationship stage detection
 * - Sentiment filtering (auto-flag complaints/opt-outs)
 * - Optimal send-time calculation
 * - Conversation context integration
 */
class MessagePersonalizationService
{
    /**
     * OpenAI API configuration
     */
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private string $model = 'gpt-4';
    private int $maxTokens = 500;
    private float $temperature = 0.7;

    /**
     * Cache configuration
     */
    private int $cacheTtl = 3600; // 1 hour

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o');
        $this->maxTokens = (int) config('services.openai.max_tokens', 1000);
    }

    /**
     * Personalize a message for a specific contact
     * 
     * @param MessageQueue $message
     * @return array ['refined_message', 'analysis']
     */
    public function personalizeMessage(MessageQueue $message): array
    {
        try {
            // Load contact information
            $contact = $message->contact;
            
            if (!$contact) {
                Log::warning("MessageQueue {$message->id} has no contact, skipping personalization");
                return [
                    'refined_message' => $message->original_message,
                    'analysis' => [
                        'error' => 'No contact found',
                        'ai_confidence_score' => 0
                    ]
                ];
            }

            // Check if contact has opted out
            if ($contact->opt_out_status) {
                Log::info("Contact {$contact->id} has opted out, marking message for review");
                $message->markAsOptedOut();
                return [
                    'refined_message' => null,
                    'analysis' => ['opted_out' => true]
                ];
            }

            // Gather conversation context
            $conversationHistory = $this->getConversationHistory($contact, $limit = 10);
            
            // Get contact preferences
            $preferences = $this->getContactPreferences($contact);
            
            // Build AI prompt
            $prompt = $this->buildPersonalizationPrompt(
                $message->original_message,
                $contact,
                $conversationHistory,
                $preferences,
                $message->attachment_context
            );

            // Call OpenAI API
            $aiResponse = $this->callOpenAI($prompt);

            // Parse AI response
            $analysis = $this->parseAIResponse($aiResponse);

            // Check for opt-out sentiment
            if ($analysis['sentiment_filter_result'] === MessageQueue::SENTIMENT_OPT_OUT_DETECTED) {
                Log::warning("Opt-out sentiment detected for contact {$contact->id}");
                $message->markForReview('Opt-out language detected in analysis');
                return [
                    'refined_message' => $message->original_message,
                    'analysis' => $analysis
                ];
            }

            // Check if human review is needed (low confidence)
            if ($analysis['ai_confidence_score'] < 0.6) {
                Log::info("Low AI confidence ({$analysis['ai_confidence_score']}) for message {$message->id}");
                $message->markForReview('Low AI confidence score');
                return [
                    'refined_message' => $message->original_message,
                    'analysis' => $analysis
                ];
            }

            // Calculate optimal send time
            $optimalSendTime = $this->calculateOptimalSendTime($contact, $conversationHistory);

            // Update contact learning preferences
            $this->updateContactPreferences($contact, $analysis);

            // Return refined message and full analysis
            return [
                'refined_message' => $analysis['refined_message'],
                'analysis' => array_merge($analysis, [
                    'optimal_send_time' => $optimalSendTime,
                    'conversation_turns' => count($conversationHistory)
                ])
            ];

        } catch (\Exception $e) {
            Log::error("Personalization failed for MessageQueue {$message->id}: " . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'refined_message' => $message->original_message,
                'analysis' => [
                    'error' => $e->getMessage(),
                    'ai_confidence_score' => 0
                ]
            ];
        }
    }

    /**
     * Build the AI prompt for personalization
     */
    private function buildPersonalizationPrompt(
        string $originalMessage,
        BusinessContact $contact,
        array $conversationHistory,
        array $preferences,
        ?string $attachmentContext = null
    ): string {
        $contactName = $contact->guest_name ?? 'Customer';
        $preferredLanguage = $preferences['preferred_language'] ?? 'auto-detect';
        $preferredTone = $preferences['preferred_tone'] ?? 'friendly';
        $relationshipStage = $preferences['relationship_stage'] ?? 'new';

        // Format conversation history
        $historyText = '';
        if (!empty($conversationHistory)) {
            $historyText = "\n\n### Recent Conversation History (last 10 messages):\n";
            foreach ($conversationHistory as $msg) {
                $sender = $msg['is_incoming'] ? $contactName : 'Business';
                $historyText .= "- {$sender}: {$msg['message']}\n";
            }
        }

        // Attachment context
        $attachmentInfo = '';
        if ($attachmentContext) {
            $attachmentInfo = "\n\n### Attachments:\n{$attachmentContext}\n";
        }

        $prompt = <<<PROMPT
You are an expert WhatsApp marketing message personalizer for SafariChat, a Kenyan business communication platform.

### Task:
Personalize the following marketing message for a specific contact based on their profile, conversation history, and preferences.

### Original Message:
{$originalMessage}

### Contact Information:
- Name: {$contactName}
- Phone: {$contact->guest_phone}
- Preferred Language: {$preferredLanguage} (English/Swahili/Mixed)
- Preferred Tone: {$preferredTone} (formal/casual/urgent/friendly)
- Relationship Stage: {$relationshipStage} (new/engaged/converting/customer/inactive)
- Engagement Score: {$contact->engagement_score}/100
{$historyText}{$attachmentInfo}

### Instructions:
1. **Language Detection**: Analyze the conversation history and detect the contact's preferred language (English, Swahili, or mixed).
2. **Tone Matching**: Match the tone to the contact's preferred communication style based on past interactions.
3. **Personalization**: 
   - Use the contact's name naturally
   - Reference past conversation context if relevant
   - Adapt message length to their typical response patterns
   - Maintain the core message intent while making it feel personal
4. **Sentiment Check**: Flag if the contact has shown signs of wanting to opt-out, unsubscribe, or has been negative/complaining.
5. **Cultural Sensitivity**: For Kenyan context, use appropriate greetings and cultural references.

### Response Format (MUST be valid JSON):
{
  "refined_message": "The personalized message text here",
  "detected_language": "english|swahili|mixed",
  "detected_tone": "formal|casual|urgent|friendly",
  "relationship_stage": "new|engaged|converting|customer|inactive",
  "sentiment_filter_result": "positive|neutral|negative|opt_out_detected",
  "ai_confidence_score": 0.85,
  "reasoning": "Brief explanation of personalization choices",
  "context_summary": {
    "last_interaction_topic": "Brief summary of last conversation",
    "contact_intent": "What the contact seems to want/need",
    "suggested_follow_up": "Recommended next action"
  }
}

### Important Rules:
- Keep the refined message concise (under 300 characters unless necessary)
- Preserve any {{variable}} placeholders in the original message
- If you detect opt-out language or negative sentiment, set sentiment_filter_result to "opt_out_detected" or "negative"
- Confidence score should be 0.0-1.0 based on how much personalization was possible
- Always respond with valid JSON only, no additional text

Return ONLY the JSON response, nothing else.
PROMPT;

        return $prompt;
    }

    /**
     * Call OpenAI API with retry logic
     */
    private function callOpenAI(string $prompt, int $maxRetries = 3): array
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $maxRetries) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->apiUrl, [
                        'model' => $this->model,
                        'messages' => [
                            [
                                'role' => 'system',
                                'content' => 'You are an expert AI assistant specializing in WhatsApp marketing message personalization for African businesses. Always respond with valid JSON.'
                            ],
                            [
                                'role' => 'user',
                                'content' => $prompt
                            ]
                        ],
                        'max_tokens' => $this->maxTokens,
                        'temperature' => $this->temperature,
                        'response_format' => ['type' => 'json_object']
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data;
                }

                Log::warning("OpenAI API returned non-200 status: " . $response->status(), [
                    'response_body' => $response->body()
                ]);

            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning("OpenAI API call attempt " . ($attempts + 1) . " failed: " . $e->getMessage());
            }

            $attempts++;
            if ($attempts < $maxRetries) {
                sleep(2 ** $attempts); // Exponential backoff: 2s, 4s, 8s
            }
        }

        throw new \Exception("OpenAI API call failed after {$maxRetries} attempts: " . ($lastException ? $lastException->getMessage() : 'Unknown error'));
    }

    /**
     * Parse AI response into structured analysis
     */
    private function parseAIResponse(array $apiResponse): array
    {
        try {
            if (!isset($apiResponse['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid API response structure');
            }

            $content = $apiResponse['choices'][0]['message']['content'];
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to parse JSON response: ' . json_last_error_msg());
            }

            // Validate required fields
            $required = ['refined_message', 'detected_language', 'detected_tone', 'ai_confidence_score'];
            foreach ($required as $field) {
                if (!isset($parsed[$field])) {
                    throw new \Exception("Missing required field: {$field}");
                }
            }

            return [
                'refined_message' => $parsed['refined_message'],
                'detected_language' => $parsed['detected_language'] ?? 'english',
                'detected_tone' => $parsed['detected_tone'] ?? 'casual',
                'relationship_stage' => $parsed['relationship_stage'] ?? 'engaged',
                'sentiment_filter_result' => $parsed['sentiment_filter_result'] ?? 'neutral',
                'ai_confidence_score' => (float) $parsed['ai_confidence_score'],
                'context_summary' => $parsed['context_summary'] ?? [],
                'ai_metadata' => [
                    'reasoning' => $parsed['reasoning'] ?? '',
                    'model' => $this->model,
                    'tokens_used' => $apiResponse['usage']['total_tokens'] ?? 0,
                    'processed_at' => now()->toIso8601String()
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Failed to parse AI response: " . $e->getMessage(), [
                'response' => $apiResponse
            ]);

            return [
                'refined_message' => null,
                'detected_language' => 'english',
                'detected_tone' => 'casual',
                'relationship_stage' => 'new',
                'sentiment_filter_result' => 'neutral',
                'ai_confidence_score' => 0.0,
                'context_summary' => [],
                'ai_metadata' => ['error' => $e->getMessage()]
            ];
        }
    }

    /**
     * Get conversation history for contact
     */
    private function getConversationHistory(BusinessContact $contact, int $limit = 10): array
    {
        $cacheKey = "conversation_history:{$contact->id}:{$limit}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($contact, $limit) {
            $leadIds = $contact->leads()->pluck('id');

            if ($leadIds->isEmpty()) {
                return [];
            }

            $conversations = Conversation::whereIn('lead_id', $leadIds)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get(['message_content', 'message_type', 'created_at']);

            return $conversations->map(function ($conv) {
                return [
                    'message' => $conv->message_content,
                    'is_incoming' => $conv->message_type === 'CUSTOMER',
                    'timestamp' => $conv->created_at->toIso8601String()
                ];
            })->toArray();
        });
    }

    /**
     * Get contact preferences for personalization
     */
    private function getContactPreferences(BusinessContact $contact): array
    {
        return [
            'preferred_language' => $contact->preferred_language ?? 'auto-detect',
            'preferred_tone' => $contact->preferred_tone ?? 'friendly',
            'last_message_sentiment' => $contact->last_message_sentiment ?? 'neutral',
            'engagement_score' => $contact->engagement_score ?? 50,
            'relationship_stage' => $this->determineRelationshipStage($contact)
        ];
    }

    /**
     * Determine relationship stage based on contact data
     */
    private function determineRelationshipStage(BusinessContact $contact): string
    {
        // Check conversation count
        $conversationCount = Conversation::where('business_contact_id', $contact->id)->count();

        // Check if they've made a purchase (you can extend this based on your schema)
        $hasPurchased = false; // TODO: Implement based on your business logic

        // Determine stage
        if ($conversationCount === 0) {
            return MessageQueue::STAGE_NEW;
        } elseif ($conversationCount < 3) {
            return MessageQueue::STAGE_ENGAGED;
        } elseif ($conversationCount >= 3 && !$hasPurchased) {
            return MessageQueue::STAGE_CONVERTING;
        } elseif ($hasPurchased) {
            return MessageQueue::STAGE_CUSTOMER;
        }

        // Check for inactivity (no conversation in 30+ days)
        $lastConversation = Conversation::where('business_contact_id', $contact->id)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastConversation && $lastConversation->created_at->diffInDays(now()) > 30) {
            return MessageQueue::STAGE_INACTIVE;
        }

        return MessageQueue::STAGE_ENGAGED;
    }

    /**
     * Calculate optimal send time based on contact's reply patterns
     */
    private function calculateOptimalSendTime(BusinessContact $contact, array $conversationHistory): ?string
    {
        // Use contact's average reply hour if available
        if ($contact->avg_reply_hour !== null) {
            $optimalTime = now()->setHour($contact->avg_reply_hour)->setMinute(0);
            
            // If that time has passed today, schedule for tomorrow
            if ($optimalTime->isPast()) {
                $optimalTime->addDay();
            }
            
            return $optimalTime->toDateTimeString();
        }

        // Analyze conversation history to find patterns
        if (!empty($conversationHistory)) {
            $replyHours = [];
            
            foreach ($conversationHistory as $msg) {
                if ($msg['is_incoming']) {
                    $hour = \Carbon\Carbon::parse($msg['timestamp'])->hour;
                    $replyHours[] = $hour;
                }
            }

            if (!empty($replyHours)) {
                $avgHour = round(array_sum($replyHours) / count($replyHours));
                
                // Update contact's avg_reply_hour for future use
                $contact->update(['avg_reply_hour' => $avgHour]);

                $optimalTime = now()->setHour($avgHour)->setMinute(0);
                
                if ($optimalTime->isPast()) {
                    $optimalTime->addDay();
                }
                
                return $optimalTime->toDateTimeString();
            }
        }

        // Default to business hours in Kenya (9 AM - 5 PM EAT)
        $currentHour = now()->hour;
        
        if ($currentHour < 9) {
            $optimalTime = now()->setHour(9)->setMinute(0);
        } elseif ($currentHour >= 17) {
            $optimalTime = now()->addDay()->setHour(9)->setMinute(0);
        } else {
            // Send within next hour during business hours
            $optimalTime = now()->addHour()->setMinute(0);
        }

        return $optimalTime->toDateTimeString();
    }

    /**
     * Update contact preferences based on AI analysis
     */
    private function updateContactPreferences(BusinessContact $contact, array $analysis): void
    {
        $updates = [];

        // Update language preference if detected
        if (isset($analysis['detected_language']) && empty($contact->preferred_language)) {
            $updates['preferred_language'] = $analysis['detected_language'];
        }

        // Update tone preference if detected
        if (isset($analysis['detected_tone']) && empty($contact->preferred_tone)) {
            $updates['preferred_tone'] = $analysis['detected_tone'];
        }

        // Update last message sentiment
        if (isset($analysis['sentiment_filter_result'])) {
            $updates['last_message_sentiment'] = $analysis['sentiment_filter_result'];
        }

        // Update engagement score based on AI confidence
        if (isset($analysis['ai_confidence_score'])) {
            $confidenceBoost = $analysis['ai_confidence_score'] * 10; // Max +10 points
            $newScore = min(100, ($contact->engagement_score ?? 50) + $confidenceBoost);
            $updates['engagement_score'] = round($newScore, 2);
        }

        if (!empty($updates)) {
            $contact->update($updates);
            Log::info("Updated preferences for contact {$contact->id}", $updates);
        }
    }

    /**
     * Batch personalize multiple messages for a campaign
     * 
     * @param Campaign $campaign
     * @param int $batchSize
     * @return array ['processed' => int, 'failed' => int]
     */
    public function batchPersonalizeCampaign(Campaign $campaign, int $batchSize = 50): array
    {
        $processed = 0;
        $failed = 0;

        $messages = MessageQueue::where('campaign_id', $campaign->id)
            ->where('status', MessageQueue::STATUS_STAGED)
            ->limit($batchSize)
            ->get();

        foreach ($messages as $message) {
            try {
                $message->update(['status' => MessageQueue::STATUS_ANALYZING]);
                $campaign->incrementCounter('analyzing_count');
                $campaign->decrementCounter('queued_count');

                $result = $this->personalizeMessage($message);

                if ($result['refined_message']) {
                    $message->update([
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

                    $campaign->incrementCounter('refined_count');
                    $campaign->decrementCounter('analyzing_count');
                    $processed++;
                } else {
                    // Message needs human review or was opted out
                    $failed++;
                }

            } catch (\Exception $e) {
                Log::error("Batch personalization failed for message {$message->id}: " . $e->getMessage());
                $message->update(['status' => MessageQueue::STATUS_FAILED]);
                $campaign->incrementCounter('failed_count');
                $campaign->decrementCounter('analyzing_count');
                $failed++;
            }
        }

        return [
            'processed' => $processed,
            'failed' => $failed,
            'total' => $messages->count()
        ];
    }
}
