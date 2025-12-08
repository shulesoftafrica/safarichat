<?php

namespace App\Services;

use OpenAI;
use App\Models\AiSalesAgent;
use App\Models\Product;
use App\Models\Lead;
use App\Models\Conversation;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    private $client;
    private $defaultModel = 'gpt-4o';

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    /**
     * Generate AI response for sales conversation
     */
    public function generateSalesResponse(
        string $customerMessage,
        AiSalesAgent $agent,
        Lead $lead,
        array $conversationHistory = [],
        ?Product $product = null
    ): array {
        try {
            $prompt = $this->buildPromptWithAgent($customerMessage, $agent, $lead, $conversationHistory, $product);
            
            $response = $this->client->chat()->create([
                'model' => $this->defaultModel,
                'messages' => $prompt,
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'presence_penalty' => 0.1,
                'frequency_penalty' => 0.1,
            ]);

            $aiResponse = $response->choices[0]->message->content;
            $constraints = $this->applyAgentConstraints($aiResponse, $agent, $product);

            return [
                'success' => true,
                'response' => $constraints['response'],
                'actions' => $constraints['actions'],
                'confidence' => $this->calculateConfidence($response),
                'tokens_used' => $response->usage->totalTokens,
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage(), [
                'agent_id' => $agent->id,
                'lead_id' => $lead->id,
                'customer_message' => substr($customerMessage, 0, 100),
            ]);

            return [
                'success' => false,
                'response' => $agent->fallback_person ? 
                    "I'm having technical difficulties. Let me connect you with {$agent->fallback_person}." :
                    "I apologize, but I'm experiencing technical issues. Please try again in a moment.",
                'actions' => ['escalate' => true],
                'error' => $e->getMessage(),
            ];
        }
    }

    // === NEW RAG METHODS ===

    /**
     * Generate embedding vector for text
     */
    public function generateEmbedding(string $text): array
    {
        try {
            $response = $this->client->embeddings()->create([
                'model' => 'text-embedding-3-small',
                'input' => trim($text),
                'encoding_format' => 'float'
            ]);
            
            return $response->embeddings[0]->embedding;
        } catch (\Exception $e) {
            Log::error('OpenAI Embedding Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate summary for document chunk
     */
    public function generateChunkSummary(string $content, string $productName): string
    {
        try {
            $prompt = "Summarize this product documentation chunk for '{$productName}' in 1-2 sentences, focusing on key information for sales conversations:\n\n{$content}";
            
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini', // Cheaper model for summaries
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert at summarizing product documentation for sales teams. Focus on actionable information that helps answer customer questions.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 150,
                'temperature' => 0.3
            ]);
            
            return $response->choices[0]->message->content;
        } catch (\Exception $e) {
            Log::warning('Chunk summary generation failed: ' . $e->getMessage());
            return "Key information about {$productName} from documentation.";
        }
    }

    /**
     * Generate sales response with RAG enhancement
     */
    public function generateSalesResponseWithRAG(
        string $customerMessage,
        \App\Models\AiSalesAgent $agent,
        \App\Models\Lead $lead,
        array $conversationHistory = [],
        ?\App\Models\Product $product = null
    ): array {
        try {
            // Step 1: Search for relevant document content
            $ragService = app(\App\Services\RagSearchService::class);
            $productIds = $product ? [$product->id] : $lead->leadProducts()->pluck('product_id')->toArray();
            $relevantDocs = $ragService->searchDocuments($customerMessage, $productIds, 3);
            
            // Step 2: Build enhanced prompt with document context
            $prompt = $this->buildRAGPrompt($customerMessage, $agent, $lead, $conversationHistory, $product, $relevantDocs);
            
            // Step 3: Generate response
            $response = $this->client->chat()->create([
                'model' => $this->defaultModel,
                'messages' => $prompt,
                'max_tokens' => 1200, // Increased for more detailed responses
                'temperature' => 0.7,
                'presence_penalty' => 0.1,
                'frequency_penalty' => 0.1,
            ]);

            $aiResponse = $response->choices[0]->message->content;
            $constraints = $this->applyAgentConstraints($aiResponse, $agent, $product);

            return [
                'success' => true,
                'response' => $constraints['response'],
                'actions' => $constraints['actions'],
                'confidence' => $this->calculateConfidence($response),
                'tokens_used' => $response->usage->totalTokens,
                'rag_sources' => $relevantDocs, // Include source documents
                'rag_used' => count($relevantDocs) > 0
            ];

        } catch (\Exception $e) {
            Log::warning('RAG-enhanced response failed, falling back to standard: ' . $e->getMessage());
            // Fallback to regular response generation
            return $this->generateSalesResponse($customerMessage, $agent, $lead, $conversationHistory, $product);
        }
    }

    /**
     * Build RAG-enhanced prompt
     */
    private function buildRAGPrompt(
        string $customerMessage,
        \App\Models\AiSalesAgent $agent,
        \App\Models\Lead $lead,
        array $conversationHistory,
        ?\App\Models\Product $product,
        array $relevantDocs
    ): array {
        $systemPrompt = $this->buildSystemPrompt($agent, $lead, $product);
        
        // Enhanced context with RAG documents
        $contextPrompt = $this->buildContextPrompt($agent, $lead, $product);
        
        // Add relevant document context
        if (!empty($relevantDocs)) {
            $contextPrompt .= "\n\n=== RELEVANT DOCUMENTATION ===\n";
            $contextPrompt .= "The following information from product documentation may help answer the customer's question:\n\n";
            
            foreach ($relevantDocs as $doc) {
                $contextPrompt .= "**Source:** {$doc['document_title']} ({$doc['document_type']})";
                if ($doc['section_title']) {
                    $contextPrompt .= " - {$doc['section_title']}";
                }
                if ($doc['page_number']) {
                    $contextPrompt .= " (Page {$doc['page_number']})";
                }
                $contextPrompt .= "\n";
                $contextPrompt .= "**Content:** " . substr($doc['content'], 0, 800) . "\n";
                if ($doc['summary']) {
                    $contextPrompt .= "**Summary:** {$doc['summary']}\n";
                }
                $contextPrompt .= "**Relevance Score:** " . round($doc['similarity_score'], 2) . "\n\n";
            }
            
            $contextPrompt .= "INSTRUCTIONS FOR USING DOCUMENTATION:\n";
            $contextPrompt .= "- Use this documentation to provide accurate, detailed answers\n";
            $contextPrompt .= "- Reference specific sections when helpful (e.g., 'According to our technical specifications...')\n";
            $contextPrompt .= "- If the customer needs more detailed information, mention you can provide the full documentation\n";
            $contextPrompt .= "- Always prioritize accuracy over completeness - if unsure, say so\n";
            $contextPrompt .= "===============================\n";
        }
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'assistant', 'content' => $contextPrompt],
        ];

        // Add conversation history
        foreach ($conversationHistory as $message) {
            $messages[] = [
                'role' => $message['from_customer'] ? 'user' : 'assistant',
                'content' => $message['content']
            ];
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $customerMessage];

        return $messages;
    }

    /**
     * Build comprehensive prompt with agent configuration
     */
    private function buildPromptWithAgent(
        string $customerMessage,
        AiSalesAgent $agent,
        Lead $lead,
        array $conversationHistory,
        ?Product $product
    ): array {
        $systemPrompt = $this->buildSystemPrompt($agent, $lead, $product);
        $contextPrompt = $this->buildContextPrompt($agent, $lead, $product);
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'assistant', 'content' => $contextPrompt],
        ];

        // Add conversation history
        foreach ($conversationHistory as $message) {
            $messages[] = [
                'role' => $message['from_customer'] ? 'user' : 'assistant',
                'content' => $message['content']
            ];
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $customerMessage];

        return $messages;
    }

    /**
     * Build detailed system prompt
     */
    private function buildSystemPrompt(AiSalesAgent $agent, Lead $lead, ?Product $product): string
    {
        $businessName = $agent->user?->business?->name ?? 'our company';
        $prompt = "You are {$agent->assistant_name}, a sales agent for {$businessName}. ";
        
        // Personality and communication style
        if ($agent->personality_description) {
            $prompt .= "Your personality: {$agent->personality_description} ";
        }
        if ($agent->communication_tone) {
            $prompt .= "Communication tone: {$agent->communication_tone} ";
        }

        // Key responsibilities
        $prompt .= "\n\nYour key responsibilities:";
        $prompt .= "\n- Help customers discover and purchase products";
        $prompt .= "\n- Provide accurate product information and pricing";
        $prompt .= "\n- Handle negotiations within your authority limits";
        $prompt .= "\n- Escalate when appropriate";
        $prompt .= "\n- Maintain professional, helpful communication";

        // Negotiation rules
        if ($agent->allow_negotiation) {
            $prompt .= "\n\nNegotiation Guidelines:";
            $prompt .= "\n- Maximum discount allowed: {$agent->max_discount_allowed}%";
            $prompt .= "\n- You can offer discounts up to this limit";
            if ($agent->accept_installments) {
                $prompt .= "\n- Installment plans available: up to {$agent->max_installments} payments";
                $prompt .= "\n- Minimum down payment: {$agent->min_down_payment}%";
            }
            if ($agent->negotiation_script) {
                $prompt .= "\n- Negotiation approach: {$agent->negotiation_script}";
            }
        } else {
            $prompt .= "\n\nNegotiation: Fixed pricing only. No discounts available.";
        }

        // Escalation triggers
        $escalationTriggers = json_decode($agent->escalation_triggers ?? '[]', true);
        if (!empty($escalationTriggers)) {
            $prompt .= "\n\nEscalate to {$agent->fallback_person} when:";
            foreach ($escalationTriggers as $trigger) {
                $prompt .= "\n- {$trigger}";
            }
        }
        
        if ($agent->large_order_threshold) {
            $prompt .= "\n- Orders over \${$agent->large_order_threshold}";
        }

        // Language preferences
        $prompt .= "\n\nLanguage: Primarily communicate in {$agent->primary_language}.";
        if ($agent->auto_detect_language && $agent->additional_languages) {
            $additional = json_decode($agent->additional_languages, true);
            $prompt .= " Also available in: " . implode(', ', $additional) . ".";
        }

        // Business hours context
        if (!$agent->always_available) {
            $prompt .= "\n\nBusiness Hours: ";
            $businessDays = json_decode($agent->business_days ?? '[]', true);
            if (!empty($businessDays)) {
                $prompt .= implode(', ', $businessDays) . " ";
            }
            $prompt .= "from {$agent->start_time} to {$agent->end_time} ({$agent->timezone}).";
            
            if (!$agent->isAvailableNow()) {
                $prompt .= "\nCURRENT STATUS: Outside business hours. ";
                $prompt .= $agent->out_of_hours_message ?: "We'll respond during business hours.";
            }
        }

        $prompt .= "\n\nAlways be helpful, accurate, and focused on the customer's needs.";

        return $prompt;
    }

    /**
     * Build context prompt with lead and product information (enhanced for services and RAG)
     */
    private function buildContextPrompt(AiSalesAgent $agent, Lead $lead, ?Product $product): string
    {
        $context = "Customer Context:\n";
        $context .= "- Lead ID: {$lead->id}\n";
        $context .= "- Phone: {$lead->phone_number}\n";
        $context .= "- Status: {$lead->status}\n";
        
        if ($lead->name) {
            $context .= "- Name: {$lead->name}\n";
        }
        
        // Lead score and context
        $leadScore = $lead->calculateLeadScore();
        $context .= "- Lead Score: {$leadScore}/100 ";
        if ($leadScore >= 80) {
            $context .= "(High Priority)\n";
        } elseif ($leadScore >= 60) {
            $context .= "(Medium Priority)\n";
        } else {
            $context .= "(Low Priority)\n";
        }

        // Enhanced Product Context
        if ($product) {
            $context .= "\nProduct Information:\n";
            $context .= "- {$product->name} (SKU: {$product->sku})\n";
            $context .= "- Type: " . ($product->isService() ? 'SERVICE' : 'PRODUCT') . "\n";
            $context .= "- Category: {$product->category}\n";
            
            // Pricing context - different for services
            if ($product->isService() && $product->pricing_type) {
                $context .= "- Pricing: " . ucfirst($product->pricing_type);
                if ($product->hourly_rate) {
                    $context .= " (\${$product->hourly_rate}/hour)";
                } else {
                    $context .= " (\${$product->retail_price})";
                }
                $context .= "\n";
            } else {
                $context .= "- Price: \${$product->retail_price}";
                if ($product->wholesale_price && $product->wholesale_price < $product->retail_price) {
                    $context .= " (Wholesale: \${$product->wholesale_price})";
                }
                $context .= "\n";
            }
            
            if ($product->max_discount > 0) {
                $context .= "- Maximum discount: {$product->max_discount}%\n";
            }
            
            // Stock only for tangible products
            if ($product->isTangible() && $product->tracksInventory()) {
                $context .= "- Stock: {$product->quantity} units";
                if ($product->isLowStock()) {
                    $context .= " (LOW STOCK - Handle carefully)";
                }
                $context .= "\n";
            } elseif ($product->isService()) {
                $context .= "- Availability: Service available\n";
            }

            $context .= "- Description: {$product->description}\n";
            
            if ($product->ai_description) {
                $context .= "- AI Highlights: {$product->ai_description}\n";
            }
            
            // Service-specific context
            if ($product->isService()) {
                $serviceContext = $product->getAiServiceContext();
                if ($serviceContext) {
                    $context .= "\n" . $serviceContext;
                }
            }
            
            // Available attachments for sharing
            $publicAttachments = $product->attachments()->where('is_public', true)->processed()->get();
            if ($publicAttachments->count() > 0) {
                $context .= "\nAvailable Resources to Share:\n";
                foreach ($publicAttachments as $attachment) {
                    $context .= "- {$attachment->title} ({$attachment->attachment_type})\n";
                }
                $context .= "Note: You can reference these documents or offer to share them with the customer.\n";
            }
            
        } else {
            // Show lead's interested products
            $interestedProducts = $lead->leadProducts()->with('product')->get();
            if ($interestedProducts->count() > 0) {
                $context .= "\nCustomer's Product Interest:\n";
                foreach ($interestedProducts as $leadProduct) {
                    $context .= "- {$leadProduct->product->name} ({$leadProduct->product->product_type}): {$leadProduct->status}";
                    if ($leadProduct->negotiated_price) {
                        $context .= " (Negotiated: \${$leadProduct->negotiated_price})";
                    }
                    $context .= "\n";
                }
            }
        }

        // Recent interactions summary
        $recentConversations = $lead->conversations()
            ->latest()
            ->limit(3)
            ->get();
            
        if ($recentConversations->count() > 0) {
            $context .= "\nRecent Conversation Summary:\n";
            foreach ($recentConversations as $conversation) {
                $context .= "- " . substr($conversation->ai_response, 0, 100) . "...\n";
            }
        }

        return $context;
    }

    /**
     * Build detailed system prompt (existing method continues...)
    }

    /**
     * Apply agent constraints and extract actions
     */
    private function applyAgentConstraints(string $aiResponse, AiSalesAgent $agent, ?Product $product): array
    {
        $actions = [];
        $modifiedResponse = $aiResponse;

        // Check for discount mentions and validate against limits
        if (preg_match('/(\d+)%?\s*(?:discount|off)/i', $aiResponse, $matches)) {
            $mentionedDiscount = intval($matches[1]);
            $maxDiscount = $agent->max_discount_allowed;
            
            if ($product && $product->max_discount < $maxDiscount) {
                $maxDiscount = $product->max_discount;
            }

            if ($mentionedDiscount > $maxDiscount) {
                $modifiedResponse = str_replace(
                    $matches[0], 
                    "{$maxDiscount}% discount", 
                    $modifiedResponse
                );
                $actions['discount_adjusted'] = [
                    'requested' => $mentionedDiscount,
                    'approved' => $maxDiscount
                ];
            }
        }

        // Check for price mentions if product context available
        if ($product && preg_match('/\$(\d+(?:\.\d{2})?)/i', $aiResponse, $matches)) {
            $mentionedPrice = floatval($matches[1]);
            $minPrice = $product->min_negotiable_price ?? $product->wholesale_price;
            
            if ($mentionedPrice < $minPrice) {
                $modifiedResponse = str_replace(
                    $matches[0], 
                    '$' . number_format($minPrice, 2), 
                    $modifiedResponse
                );
                $actions['price_adjusted'] = [
                    'requested' => $mentionedPrice,
                    'approved' => $minPrice
                ];
            }
        }

        // Detect escalation needs
        $escalationPhrases = [
            'transfer', 'manager', 'supervisor', 'human agent', 'speak to someone',
            'not satisfied', 'complaint', 'escalate'
        ];
        
        foreach ($escalationPhrases as $phrase) {
            if (stripos($modifiedResponse, $phrase) !== false) {
                $actions['needs_escalation'] = true;
                break;
            }
        }

        // Check for large order threshold
        if ($agent->large_order_threshold && $product) {
            if (preg_match('/(\d+)\s*(?:units|pieces|items)/i', $aiResponse, $matches)) {
                $quantity = intval($matches[1]);
                $orderValue = $quantity * $product->retail_price;
                
                if ($orderValue >= $agent->large_order_threshold) {
                    $actions['large_order'] = [
                        'quantity' => $quantity,
                        'value' => $orderValue,
                        'threshold' => $agent->large_order_threshold
                    ];
                }
            }
        }

        // Schedule followup if mentioned
        if (stripos($aiResponse, 'follow up') || stripos($aiResponse, 'check back')) {
            $actions['schedule_followup'] = true;
        }

        return [
            'response' => $modifiedResponse,
            'actions' => $actions
        ];
    }

    /**
     * Calculate response confidence based on OpenAI response data
     */
    private function calculateConfidence($response): float
    {
        // Basic confidence calculation based on tokens and finish reason
        $baseConfidence = 0.8;
        
        if ($response->choices[0]->finishReason === 'stop') {
            $baseConfidence += 0.1;
        }
        
        // Adjust based on response length (too short or too long may indicate issues)
        $responseLength = strlen($response->choices[0]->message->content);
        if ($responseLength >= 50 && $responseLength <= 500) {
            $baseConfidence += 0.1;
        }

        return min(1.0, $baseConfidence);
    }

    /**
     * Generate product description using AI
     */
    public function generateProductDescription(Product $product): ?string
    {
        try {
            $prompt = [
                [
                    'role' => 'system',
                    'content' => 'You are a professional copywriter specializing in product descriptions for sales. Create engaging, accurate, and persuasive product descriptions.'
                ],
                [
                    'role' => 'user', 
                    'content' => "Create a compelling product description for:\n\nName: {$product->name}\nCategory: {$product->category}\nPrice: \${$product->retail_price}\nCurrent Description: {$product->description}\n\nMake it engaging for sales conversations, highlighting key benefits and value proposition."
                ]
            ];

            $response = $this->client->chat()->create([
                'model' => $this->defaultModel,
                'messages' => $prompt,
                'max_tokens' => 300,
                'temperature' => 0.7,
            ]);

            return $response->choices[0]->message->content;

        } catch (\Exception $e) {
            Log::error('OpenAI Product Description Error: ' . $e->getMessage(), [
                'product_id' => $product->id,
            ]);
            return null;
        }
    }

    /**
     * Analyze customer sentiment from message
     */
    public function analyzeSentiment(string $message): array
    {
        try {
            $prompt = [
                [
                    'role' => 'system',
                    'content' => 'Analyze the sentiment of customer messages. Respond with a JSON object containing: sentiment (positive/neutral/negative), confidence (0-1), and key_emotions (array of detected emotions).'
                ],
                [
                    'role' => 'user',
                    'content' => "Analyze this customer message: \"{$message}\""
                ]
            ];

            $response = $this->client->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => $prompt,
                'max_tokens' => 150,
                'temperature' => 0.3,
            ]);

            $result = json_decode($response->choices[0]->message->content, true);
            
            return [
                'sentiment' => $result['sentiment'] ?? 'neutral',
                'confidence' => $result['confidence'] ?? 0.5,
                'emotions' => $result['key_emotions'] ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('OpenAI Sentiment Analysis Error: ' . $e->getMessage());
            
            // Fallback basic sentiment analysis
            $positive = ['good', 'great', 'excellent', 'love', 'perfect', 'amazing', 'wonderful'];
            $negative = ['bad', 'terrible', 'hate', 'awful', 'horrible', 'worst', 'disappointed'];
            
            $message = strtolower($message);
            $positiveCount = 0;
            $negativeCount = 0;
            
            foreach ($positive as $word) {
                if (strpos($message, $word) !== false) $positiveCount++;
            }
            foreach ($negative as $word) {
                if (strpos($message, $word) !== false) $negativeCount++;
            }
            
            if ($positiveCount > $negativeCount) {
                return ['sentiment' => 'positive', 'confidence' => 0.6, 'emotions' => ['satisfied']];
            } elseif ($negativeCount > $positiveCount) {
                return ['sentiment' => 'negative', 'confidence' => 0.6, 'emotions' => ['frustrated']];
            }
            
            return ['sentiment' => 'neutral', 'confidence' => 0.5, 'emotions' => []];
        }
    }
}