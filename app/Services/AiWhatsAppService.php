<?php

namespace App\Services;

use App\Models\AiSalesAgent;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Conversation;
use App\Models\EventsGuest;
use App\Models\IncomingMessage;
use App\Models\OutgoingMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AiWhatsAppService
{
    private $openAiService;

    public function __construct(OpenAiService $openAiService)
    {
        $this->openAiService = $openAiService;
    }

    /**
     * Process incoming WhatsApp message with AI
     */
    public function processIncomingMessage(IncomingMessage $message): array
    {
        try {
            DB::beginTransaction();

            // Find or create lead from the message
            $lead = $this->findOrCreateLead($message);
            
            // Find appropriate AI sales agent for this lead
            $agent = $this->findBestAgent($message, $lead);
            
            if (!$agent) {
                DB::rollback();
                return [
                    'success' => false,
                    'response' => 'No available sales agent found.',
                    'requires_human' => true
                ];
            }

            // Check business hours and agent availability
            if (!$agent->isAvailableNow()) {
                DB::rollback();
                return [
                    'success' => true,
                    'response' => $agent->getOutOfHoursResponse(),
                    'schedule_followup' => true
                ];
            }

            // Get conversation history
            $conversationHistory = $this->getConversationHistory($lead, 10);

            // Analyze message sentiment
            $sentiment = $this->openAiService->analyzeSentiment($message->message_body);

            // Determine if this is product-specific conversation
            $product = $this->identifyProduct($message, $lead);

            // Enhanced: Use RAG-augmented AI response
            $aiResult = $this->openAiService->generateSalesResponseWithRAG(
                $message->message_body,
                $agent,
                $lead,
                $conversationHistory,
                $product
            );

            if (!$aiResult['success']) {
                DB::rollback();
                return $aiResult;
            }

            // Process any actions from the AI response
            $actionResults = $this->processAiActions($aiResult['actions'], $agent, $lead, $product);

            // Enhanced conversation save with RAG sources
            $conversation = $this->saveConversation(
                $lead,
                $message,
                $aiResult,
                $sentiment,
                $product,
                $aiResult['rag_sources'] ?? []
            );

            // Update lead engagement
            $this->updateLeadEngagement($lead, $sentiment, $aiResult);

            DB::commit();

            return [
                'success' => true,
                'response' => $aiResult['response'],
                'conversation_id' => $conversation->id,
                'actions_taken' => $actionResults,
                'rag_enhanced' => $aiResult['rag_used'] ?? false,
                'sources_used' => count($aiResult['rag_sources'] ?? []),
                'confidence' => $aiResult['confidence'],
                'sentiment' => $sentiment['sentiment'],
                'requires_human' => isset($aiResult['actions']['needs_escalation']),
                'agent_name' => $agent->assistant_name,
            ];

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('AI WhatsApp Processing Error: ' . $e->getMessage(), [
                'message_id' => $message->id,
                'phone_number' => $message->phone_number,
                'error' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'response' => 'I apologize, but I encountered a technical issue. A human agent will assist you shortly.',
                'requires_human' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Find or create lead from incoming message
     */
    private function findOrCreateLead(IncomingMessage $message): Lead
    {
        // First check if there's an existing EventsGuest
        $eventsGuest = EventsGuest::where('phone_number', $message->phone_number)->first();

        // Look for existing lead
        $lead = Lead::where('phone_number', $message->phone_number)->first();

        if (!$lead) {
            $lead = Lead::create([
                'phone_number' => $message->phone_number,
                'name' => $message->sender_name,
                'events_guest_id' => $eventsGuest?->id,
                'source' => 'whatsapp',
                'status' => Lead::STATUS_NEW,
                'first_contact_at' => now(),
                'last_activity_at' => now(),
            ]);
        } else {
            // Update last activity and name if available
            $lead->update([
                'last_activity_at' => now(),
                'name' => $lead->name ?: $message->sender_name,
            ]);
        }

        return $lead;
    }

    /**
     * Find the best AI sales agent for this lead
     */
    private function findBestAgent(IncomingMessage $message, Lead $lead): ?AiSalesAgent
    {
        // Start with active agents for this user
        $agentsQuery = AiSalesAgent::where('user_id', $message->user_id)
            ->active();

        // If lead has existing agent preference, try that first
        if ($lead->ai_sales_agent_id) {
            $preferredAgent = $agentsQuery->find($lead->ai_sales_agent_id);
            if ($preferredAgent && $preferredAgent->isAvailableNow()) {
                return $preferredAgent;
            }
        }

        // Find agents based on target audience
        $availableAgents = $agentsQuery->get();
        
        foreach ($availableAgents as $agent) {
            if ($this->isAgentSuitableForLead($agent, $lead)) {
                // Assign this agent to the lead for future conversations
                $lead->update(['ai_sales_agent_id' => $agent->id]);
                return $agent;
            }
        }

        // Fallback to any available agent
        $fallbackAgent = $availableAgents->first(function ($agent) {
            return $agent->isAvailableNow();
        });

        if ($fallbackAgent) {
            $lead->update(['ai_sales_agent_id' => $fallbackAgent->id]);
        }

        return $fallbackAgent;
    }

    /**
     * Check if agent is suitable for this lead
     */
    private function isAgentSuitableForLead(AiSalesAgent $agent, Lead $lead): bool
    {
        // Check target user types if lead has events_guest
        if ($lead->eventsGuest && $agent->target_user_types) {
            $targetTypes = json_decode($agent->target_user_types, true);
            if (!empty($targetTypes) && !in_array($lead->eventsGuest->user_type_id, $targetTypes)) {
                return false;
            }
        }

        // Check target audience criteria
        if ($agent->target_audience && $lead->lead_score) {
            $leadScore = $lead->calculateLeadScore();
            
            switch ($agent->target_audience) {
                case 'high_value':
                    return $leadScore >= 80;
                case 'medium_value':
                    return $leadScore >= 50 && $leadScore < 80;
                case 'new_leads':
                    return $lead->status === Lead::STATUS_NEW;
                case 'returning_customers':
                    return $lead->leadProducts()->exists();
            }
        }

        return true;
    }

    /**
     * Get conversation history for context
     */
    private function getConversationHistory(Lead $lead, int $limit = 10): array
    {
        $conversations = $lead->conversations()
            ->latest()
            ->limit($limit)
            ->get();

        $history = [];
        foreach ($conversations as $conversation) {
            $history[] = [
                'from_customer' => true,
                'content' => $conversation->customer_message,
                'timestamp' => $conversation->created_at,
            ];
            
            if ($conversation->ai_response) {
                $history[] = [
                    'from_customer' => false,
                    'content' => $conversation->ai_response,
                    'timestamp' => $conversation->created_at,
                ];
            }
        }

        // Return in chronological order (oldest first for context)
        return array_reverse($history);
    }

    /**
     * Identify product from message context
     */
    private function identifyProduct(IncomingMessage $message, Lead $lead): ?Product
    {
        $messageBody = strtolower($message->message_body);

        // First, check lead's interested products
        $leadProducts = $lead->leadProducts()->with('product')->get();
        
        foreach ($leadProducts as $leadProduct) {
            $productName = strtolower($leadProduct->product->name);
            $productSku = strtolower($leadProduct->product->sku);
            
            if (strpos($messageBody, $productName) !== false || 
                strpos($messageBody, $productSku) !== false) {
                return $leadProduct->product;
            }
        }

        // Then search in all available products
        $products = Product::active()->get();
        
        foreach ($products as $product) {
            $productName = strtolower($product->name);
            $productSku = strtolower($product->sku);
            
            // Check for product name or SKU mentions
            if (strpos($messageBody, $productName) !== false || 
                strpos($messageBody, $productSku) !== false) {
                
                // Create lead-product relationship if not exists
                $lead->leadProducts()->firstOrCreate(
                    ['product_id' => $product->id],
                    [
                        'status' => 'interested',
                        'interest_level' => 'medium',
                        'source' => 'message_mention',
                    ]
                );
                
                return $product;
            }

            // Check tags for more matches
            if ($product->tags) {
                $tags = json_decode($product->tags, true);
                foreach ($tags as $tag) {
                    if (strpos($messageBody, strtolower($tag)) !== false) {
                        $lead->leadProducts()->firstOrCreate(
                            ['product_id' => $product->id],
                            [
                                'status' => 'interested',
                                'interest_level' => 'low',
                                'source' => 'tag_mention',
                            ]
                        );
                        
                        return $product;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Process actions from AI response
     */
    private function processAiActions(array $actions, AiSalesAgent $agent, Lead $lead, ?Product $product): array
    {
        $results = [];

        foreach ($actions as $action => $data) {
            switch ($action) {
                case 'discount_adjusted':
                    $results['discount'] = [
                        'applied' => true,
                        'percentage' => $data['approved'],
                        'original_request' => $data['requested'],
                    ];
                    
                    // Update product interest with negotiated price
                    if ($product) {
                        $discountedPrice = $product->retail_price * (1 - ($data['approved'] / 100));
                        
                        $lead->leadProducts()->updateOrCreate(
                            ['product_id' => $product->id],
                            [
                                'negotiated_price' => $discountedPrice,
                                'discount_applied' => $data['approved'],
                                'status' => 'negotiating',
                            ]
                        );
                    }
                    break;

                case 'needs_escalation':
                    $results['escalation'] = $this->createEscalation($agent, $lead, 'AI detected escalation need');
                    break;

                case 'large_order':
                    $results['large_order'] = [
                        'flagged' => true,
                        'value' => $data['value'],
                        'requires_approval' => true,
                    ];
                    
                    // Auto-escalate large orders
                    $results['escalation'] = $this->createEscalation(
                        $agent, 
                        $lead, 
                        "Large order detected: \${$data['value']} (Quantity: {$data['quantity']})"
                    );
                    break;

                case 'schedule_followup':
                    $results['followup'] = $this->scheduleFollowup($agent, $lead, $product);
                    break;
            }
        }

        return $results;
    }

    /**
     * Create escalation/handoff
     */
    private function createEscalation(AiSalesAgent $agent, Lead $lead, string $reason): array
    {
        $handoff = $lead->handoffs()->create([
            'ai_sales_agent_id' => $agent->id,
            'reason' => $reason,
            'priority' => 'medium',
            'status' => 'pending',
            'escalation_type' => 'ai_triggered',
            'context' => [
                'agent_name' => $agent->assistant_name,
                'lead_score' => $lead->calculateLeadScore(),
                'interested_products' => $lead->leadProducts()->with('product')->get()->pluck('product.name'),
            ],
        ]);

        return [
            'created' => true,
            'handoff_id' => $handoff->id,
            'fallback_person' => $agent->fallback_person,
        ];
    }

    /**
     * Schedule followup for lead
     */
    private function scheduleFollowup(AiSalesAgent $agent, Lead $lead, ?Product $product): array
    {
        if (!$agent->auto_followup) {
            return ['scheduled' => false, 'reason' => 'Auto-followup disabled'];
        }

        $followupTime = now()->addHours($agent->followup_delay ?? 24);

        $conversation = $lead->conversations()->latest()->first();
        if ($conversation) {
            $conversation->scheduleFollowup($followupTime, $agent->followup_message);
        }

        return [
            'scheduled' => true,
            'followup_at' => $followupTime,
            'message' => $agent->followup_message,
        ];
    }

    /**
     * Save conversation record (enhanced with RAG sources)
     */
    private function saveConversation(
        Lead $lead,
        IncomingMessage $message,
        array $aiResult,
        array $sentiment,
        ?Product $product,
        array $ragSources = []
    ): Conversation {
        return $lead->conversations()->create([
            'product_id' => $product?->id,
            'customer_message' => $message->message_body,
            'ai_response' => $aiResult['response'],
            'sentiment' => $sentiment['sentiment'],
            'confidence_score' => $aiResult['confidence'],
            'tokens_used' => $aiResult['tokens_used'] ?? 0,
            'state' => 'active',
            'summary' => $this->generateConversationSummary($message->message_body, $aiResult['response']),
            'ai_actions' => $aiResult['actions'] ?? [],
            'rag_sources' => $ragSources, // Store RAG sources
            'rag_enhanced' => $aiResult['rag_used'] ?? false,
            'conversation_context' => [
                'phone_number' => $message->phone_number,
                'message_type' => $message->message_type ?? 'text',
                'sources_count' => count($ragSources),
                'processing_method' => 'rag_enhanced'
            ]
        ]);
    }

    /**
     * Generate brief conversation summary
     */
    private function generateConversationSummary(string $customerMessage, string $aiResponse): string
    {
        $customerWords = str_word_count($customerMessage);
        $customerSummary = $customerWords > 10 ? 
            implode(' ', array_slice(str_word_count($customerMessage, 1), 0, 8)) . '...' :
            $customerMessage;

        return "Customer: {$customerSummary}";
    }

    /**
     * Update lead engagement metrics
     */
    private function updateLeadEngagement(Lead $lead, array $sentiment, array $aiResult): void
    {
        $lead->increment('interaction_count');
        $lead->update(['last_activity_at' => now()]);

        // Update sentiment tracking
        if ($sentiment['sentiment'] === 'negative') {
            $lead->increment('negative_sentiment_count');
        }

        // Update lead status based on conversation
        if (isset($aiResult['actions']['needs_escalation'])) {
            $lead->update(['status' => Lead::STATUS_NEEDS_ATTENTION]);
        } elseif ($sentiment['sentiment'] === 'positive' && $lead->status === Lead::STATUS_NEW) {
            $lead->update(['status' => Lead::STATUS_ENGAGED]);
        }
    }

    /**
     * Send AI response via WhatsApp
     */
    public function sendResponse(string $response, IncomingMessage $originalMessage): bool
    {
        try {
            // This would integrate with your existing WhatsApp sending logic
            $outgoingMessage = OutgoingMessage::create([
                'user_id' => $originalMessage->user_id,
                'instance_id' => $originalMessage->instance_id,
                'chat_id' => $originalMessage->chat_id,
                'phone_number' => $originalMessage->phone_number,
                'message_body' => $response,
                'message_type' => 'text',
                'status' => 'pending',
                'is_ai_generated' => true,
            ]);

            // Here you would call your existing WhatsApp API service
            // For now, just mark as sent
            $outgoingMessage->update(['status' => 'sent']);

            return true;

        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error: ' . $e->getMessage(), [
                'original_message_id' => $originalMessage->id,
                'response' => substr($response, 0, 100),
            ]);

            return false;
        }
    }
}