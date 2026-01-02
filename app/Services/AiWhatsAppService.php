<?php

namespace App\Services;

use App\Models\AiSalesAgent;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Conversation;
use App\Models\BusinessContact;
use App\Models\IncomingMessage;
use App\Models\OutgoingMessage;
use App\Models\Handoff;
use App\Services\WaSenderService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AiWhatsAppService
{
    private $openAiService;
    private $waSenderService;

    public function __construct(OpenAiService $openAiService, WaSenderService $waSenderService)
    {
        $this->openAiService = $openAiService;
        $this->waSenderService = $waSenderService;
    }

    /**
     * Process incoming WhatsApp message with AI sales agent
     */
    public function processIncomingWhatsAppMessageWithAI(IncomingMessage $message, ?\App\Models\WhatsappInstance $instance = null): array
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

            // Enhanced: Use RAG-augmented AI response with instance context
            $aiResult = $this->openAiService->generateSalesResponseWithRAG(
                $message->message_body,
                $agent,
                $lead,
                $conversationHistory,
                $product,
                $instance // Pass instance for context
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
        // First check if there's an existing BusinessContact
        $businessContact = BusinessContact::where('guest_phone', $message->phone_number)->first();

        // Create BusinessContact if it doesn't exist
        if (!$businessContact) {
            $businessContact = UserResolutionService::resolveOrCreateContact([
                'phone' => $message->phone_number,
                'name' => $message->sender_name ?? 'WhatsApp Contact',
                'user_id' => $message->user_id
            ]);
        }

        // Look for existing lead
        $lead = Lead::where('business_contact_id', $businessContact->id)->first();

        if (!$lead) {
            $lead = Lead::create([
            'business_contact_id' => $businessContact->id,
            'ai_sales_agent_id' => AiSalesAgent::where('user_id', $message->user_id)->first()?->id,
            'source' => 'whatsapp',
            'status' => Lead::STATUS_NEW,
            'last_interaction_at' => now(),
            'conversion_probability' => 0,
            'lead_score' => 0,
            'is_churned' => false,
            'win_back_attempts' => 0,
            ]);
        } else {
            // Update last activity
            $lead->update([
                'last_activity_at' => now(),
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
        // Check target user types if lead has business_contact
        if ($lead->businessContact && $agent->target_user_types) {
            $targetTypes = $agent->target_user_types;
            if (!empty($targetTypes) && !in_array($lead->businessContact->user_type_id ?? 1, $targetTypes)) {
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

        // Then search in all available products for this user
        $products = Product::active()->forUser($message->user_id)->get();
        
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
                        'status' => 'INTERESTED',
                        'interest_level' => 'medium',
                        'source' => 'message_mention',
                    ]
                );
                
                return $product;
            }

            // Check tags for more matches
            if ($product->tags) {
                $tags = $product->tags;
                foreach ($tags as $tag) {
                    if (strpos($messageBody, strtolower($tag)) !== false) {
                        $lead->leadProducts()->firstOrCreate(
                            ['product_id' => $product->id],
                            [
                                'status' => 'INTERESTED',
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
        // Map old reason strings to proper reason codes
        $reasonCode = $this->mapReasonToCode($reason);
        
        $handoff = $lead->handoffs()->create([
            'reason_code' => $reasonCode,
            'priority_level' => Handoff::PRIORITY_MEDIUM,
            'status' => Handoff::STATUS_PENDING,
            'ai_summary' => "Customer escalation requested via {$agent->assistant_name}. Reason: {$reason}",
            'context_data' => [
                'agent_name' => $agent->assistant_name,
                'lead_score' => $lead->calculateLeadScore(),
                'interested_products' => $lead->leadProducts()->with('product')->get()->pluck('product.name'),
                'escalation_trigger' => $reason,
                'original_reason' => $reason,
            ],
        ]);

        return [
            'created' => true,
            'handoff_id' => $handoff->id,
            'fallback_person' => $agent->fallback_person,
        ];
    }

    /**
     * Map legacy reason strings to proper reason codes
     */
    private function mapReasonToCode(string $reason): string
    {
        $reason = strtolower(trim($reason));
        
        if (str_contains($reason, 'complaint') || str_contains($reason, 'dissatisfied')) {
            return Handoff::REASON_COMPLAINT;
        }
        
        if (str_contains($reason, 'angry') || str_contains($reason, 'frustrated')) {
            return Handoff::REASON_ANGRY_CUSTOMER;
        }
        
        if (str_contains($reason, 'large order') || str_contains($reason, 'bulk')) {
            return Handoff::REASON_LARGE_ORDER;
        }
        
        if (str_contains($reason, 'payment') || str_contains($reason, 'billing')) {
            return Handoff::REASON_PAYMENT_ISSUE;
        }
        
        if (str_contains($reason, 'technical') || str_contains($reason, 'complex')) {
            return Handoff::REASON_COMPLEX_QUESTION;
        }
        
        if (str_contains($reason, 'ai error') || str_contains($reason, 'system')) {
            return Handoff::REASON_AI_ERROR;
        }
        
        if (str_contains($reason, 'stock') || str_contains($reason, 'inventory')) {
            return Handoff::REASON_LOW_STOCK;
        }
        
        if (str_contains($reason, 'human') || str_contains($reason, 'agent')) {
            return Handoff::REASON_CUSTOMER_REQUEST;
        }
        
        // Default for any unmatched reasons
        return Handoff::REASON_GENERAL_ESCALATION;
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
        // Ensure array fields are properly formatted
        $aiActions = $aiResult['actions'] ?? [];
        if (!is_array($aiActions)) {
            $aiActions = [];
        }
        
        $ragSourcesArray = $ragSources;
        if (!is_array($ragSourcesArray)) {
            $ragSourcesArray = [];
        }
        
        $ragEnhanced = $aiResult['rag_used'] ?? false;
        if (!is_bool($ragEnhanced)) {
            $ragEnhanced = (bool) $ragEnhanced;
        }
        
        // Ensure conversation_context is properly formatted as an array
        $conversationContext = [
            'phone_number' => $message->phone_number,
            'message_type' => $message->message_type ?? 'text',
            'sources_count' => count($ragSourcesArray),
            'processing_method' => 'rag_enhanced'
        ];
        
        // Debug logging to identify the array literal issue
        $conversationData = [
            'lead_id' => $lead->id, // Explicitly set lead_id first to avoid auto-assignment issues
            'product_id' => $product?->id,
            'message_content' => $message->message_body, // Required field - customer's message
            'sender_type' => 'customer', // Required field - who sent the message
            'message_type' => Conversation::TYPE_CUSTOMER, // Use constant instead of 'text'
            'customer_message' => $message->message_body,
            'ai_response' => $aiResult['response'],
            'sentiment' => $sentiment['sentiment'],
            'confidence_score' => $aiResult['confidence'],
            'tokens_used' => $aiResult['tokens_used'] ?? 0,
            'state' => 'active',
            'summary' => $this->generateConversationSummary($message->message_body, $aiResult['response']),
            'ai_actions' => $aiActions,
            'rag_sources' => $ragSourcesArray,
            'rag_enhanced' => $ragEnhanced ? 1 : 0, // Convert boolean to integer for PostgreSQL
            'conversation_context' => $conversationContext
        ];
        
        // Log the data types before database insertion
        Log::info('Conversation data types before insertion', [
            'ai_actions_type' => gettype($conversationData['ai_actions']),
            'ai_actions_is_array' => is_array($conversationData['ai_actions']),
            'rag_sources_type' => gettype($conversationData['rag_sources']),
            'rag_sources_is_array' => is_array($conversationData['rag_sources']),
            'rag_enhanced_type' => gettype($conversationData['rag_enhanced']),
            'rag_enhanced_value' => $conversationData['rag_enhanced'],
            'conversation_context_type' => gettype($conversationData['conversation_context']),
            'conversation_context_is_array' => is_array($conversationData['conversation_context']),
        ]);
        
        return $lead->conversations()->create($conversationData);
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
        } elseif ($sentiment['sentiment'] === 'positive') {
            $lead->increment('positive_sentiment_count');
        }
        
        // Update overall sentiment score (running average based on total interactions)
        $totalPositive = $lead->positive_sentiment_count + ($sentiment['sentiment'] === 'positive' ? 1 : 0);
        $totalNegative = $lead->negative_sentiment_count + ($sentiment['sentiment'] === 'negative' ? 1 : 0);
        $totalInteractions = $lead->interaction_count;
        
        if ($totalInteractions > 0) {
            $overallScore = ($totalPositive - $totalNegative) / $totalInteractions;
            $lead->update(['overall_sentiment_score' => round($overallScore, 2)]);
        }

        // Update lead status based on conversation
        if (isset($aiResult['actions']['needs_escalation'])) {
            $lead->update(['status' => Lead::STATUS_NEEDS_ATTENTION]);
        } elseif ($sentiment['sentiment'] === 'positive' && $lead->status === Lead::STATUS_NEW) {
            $lead->update(['status' => Lead::STATUS_ENGAGED]);
        }
    }

    /**
     * Send AI response via WhatsApp with instance support
     */
    public function sendResponse(string $response, IncomingMessage $originalMessage, ?\App\Models\WhatsappInstance $instance = null): bool
    {
        try {
            // Create outgoing message record first with instance tracking
            $outgoingMessage = OutgoingMessage::create([
                'user_id' => $originalMessage->user_id,
                'instance_id' => $originalMessage->instance_id,
                'whatsapp_instance_id' => $instance?->id ?? $originalMessage->whatsapp_instance_id, // New field
                'chat_id' => $originalMessage->chat_id,
                'phone_number' => $originalMessage->phone_number,
                'message_body' => $response,
                'message_type' => 'text',
                'status' => 'pending',
                'is_ai_generated' => true,
            ]);

            // Send via WaSender API using instance object
            if ($instance) {
                // Use new instance-aware method
                $result = $this->waSenderService->sendMessage(
                    $originalMessage->phone_number,
                    $response,
                    [],
                    $instance,
                    $originalMessage->user_id
                );
            } else {
                // Fallback to legacy method
                $result = $this->waSenderService->sendTextMessage(
                    $originalMessage->phone_number,
                    $response,
                    $originalMessage->instance_id,
                    $originalMessage->user_id
                );
            }

            if ($result['success']) {
                // Update outgoing message as sent
                $outgoingMessage->update([
                    'status' => 'sent',
                    'message_id' => $result['message_id'] ?? null
                ]);

                Log::info('AI response sent successfully via WhatsApp', [
                    'outgoing_message_id' => $outgoingMessage->id,
                    'phone_number' => $originalMessage->phone_number,
                    'message_id' => $result['message_id'] ?? null
                ]);

                return true;
            } else {
                // Update outgoing message as failed
                $outgoingMessage->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error'
                ]);

                Log::error('Failed to send AI response via WhatsApp', [
                    'outgoing_message_id' => $outgoingMessage->id,
                    'phone_number' => $originalMessage->phone_number,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);

                return false;
            }

        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error: ' . $e->getMessage(), [
                'original_message_id' => $originalMessage->id,
                'response' => substr($response, 0, 100),
                'error_trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Process conversation response from ConversationEngineCommand
     */
    public function processConversationResponse(Conversation $conversation, array $response, int $priority = 1): array
    {
        try {
            DB::beginTransaction();

            $lead = $conversation->lead;
            $messageText = $response['message_text'] ?? '';
            
            if (empty($messageText)) {
                throw new \Exception('Empty response message');
            }

            // Try to get phone number from lead
            $phoneNumber = $lead->phone_number ?? $lead->getContactPhone();
            if (empty($phoneNumber)) {
                throw new \Exception('Lead phone number is empty');
            }

            // Create outgoing message record
            $outgoingMessage = OutgoingMessage::create([
                'phone_number' => $phoneNumber,
                'message' => $messageText,
                'status' => 'pending',
                'message_type' => 'text',
                'priority' => $priority
            ]);

            // Send via WhatsApp using WaSenderService
            $sendResult = $this->waSenderService->sendMessage(
                $phoneNumber,
                $messageText,
                [], // options
                $conversation->whatsapp_instance // instance
            );

            if ($sendResult['success']) {
                // Update outgoing message status
                $outgoingMessage->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'whatsapp_message_id' => $sendResult['message_id'] ?? null
                ]);

                // Update conversation
                $conversation->update([
                    'status' => Conversation::STATUS_COMPLETED,
                    'ai_response' => $messageText,
                    'last_ai_response' => $messageText,
                    'completed_at' => now(),
                    'conversation_state' => $response['new_conversation_state'] ?? $conversation->conversation_state
                ]);

                // Update lead status if needed
                if (isset($response['new_lead_status'])) {
                    $lead->update(['status' => $response['new_lead_status']]);
                }

                DB::commit();

                Log::info('Conversation response processed successfully', [
                    'conversation_id' => $conversation->id,
                    'lead_id' => $lead->id,
                    'outgoing_message_id' => $outgoingMessage->id
                ]);

                return [
                    'success' => true,
                    'outgoing_message_id' => $outgoingMessage->id,
                    'conversation_updated' => true
                ];

            } else {
                // Mark message as failed
                $outgoingMessage->update([
                    'status' => 'failed',
                    'error_message' => $sendResult['error'] ?? 'Unknown sending error'
                ]);

                // Update conversation with retry info
                $conversation->update([
                    'retry_count' => $conversation->retry_count + 1,
                    'status' => $conversation->retry_count >= 3 ? Conversation::STATUS_FAILED : Conversation::STATUS_PENDING
                ]);

                DB::rollback();

                return [
                    'success' => false,
                    'error' => $sendResult['error'] ?? 'Failed to send message via WhatsApp'
                ];
            }

        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('Error processing conversation response', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send outreach message to lead (used by daily outreach command)
     */
    public function sendOutreachMessage(Lead $lead, string $message, AiSalesAgent $agent): array
    {
        try {
            // Get lead's contact information
            $contact = $lead->contact;
            if (!$contact || !$contact->guest_phone) {
                return [
                    'success' => false,
                    'error' => 'No phone number found for lead'
                ];
            }

            // Get user's WhatsApp instance
            $instance = \App\Models\WhatsappInstance::where('user_id', $agent->user_id)
                                                   ->where('status', 'connected')
                                                   ->first();

            if (!$instance) {
                return [
                    'success' => false,
                    'error' => 'No active WhatsApp instance found'
                ];
            }

            // Create outgoing message record
            $outgoingMessage = OutgoingMessage::create([
                'user_id' => $agent->user_id,
                'phone_number' => $contact->guest_phone,
                'message_body' => $message,
                'message_type' => 'text',
                'status' => 'pending',
                'metadata' => [
                    'lead_id' => $lead->id,
                    'agent_id' => $agent->id,
                    'campaign' => 'daily_outreach'
                ]
            ]);

            // Send via WaSender
            $result = $this->waSenderService->sendMessage(
                $contact->guest_phone,
                $message,
                ['type' => 'text'],
                $instance,
                $agent->user_id
            );

            if ($result['success']) {
                $outgoingMessage->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'external_message_id' => $result['message_id'] ?? null
                ]);

                // Create conversation record
                Conversation::create([
                    'lead_id' => $lead->id,
                    'ai_sales_agent_id' => $agent->id,
                    'message_type' => Conversation::TYPE_AI_AGENT,
                    'message_content' => $message,
                    'conversation_state' => 'OUTREACH',
                    'sender_type' => 'ai_outreach',
                    'is_active' => true,
                    'metadata' => [
                        'campaign' => 'daily_outreach',
                        'outgoing_message_id' => $outgoingMessage->id
                    ]
                ]);

                Log::info('Outreach message sent successfully', [
                    'lead_id' => $lead->id,
                    'agent_id' => $agent->id,
                    'phone' => $contact->guest_phone
                ]);

                return [
                    'success' => true,
                    'message_id' => $result['message_id'] ?? null,
                    'outgoing_message_id' => $outgoingMessage->id
                ];
            } else {
                $outgoingMessage->update([
                    'status' => 'failed',
                    'error_message' => $result['error'] ?? 'Unknown error'
                ]);

                return [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to send message'
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error sending outreach message', [
                'lead_id' => $lead->id,
                'agent_id' => $agent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}