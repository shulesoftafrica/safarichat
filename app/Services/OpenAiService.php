<?php

namespace App\Services;

use OpenAI;
use App\Models\AiSalesAgent;
use App\Models\Product;
use App\Models\Lead;
use App\Models\Conversation;
use App\Services\BillingService;
use App\Services\LocalBillingValidator;
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
     * Clean and sanitize text to ensure valid UTF-8 encoding
     */
    private function sanitizeText(string $text): string
    {
        // Remove null bytes and other control characters
        $text = str_replace("\0", '', $text);
        
        // Convert to UTF-8 if needed and remove invalid sequences
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove or replace problematic characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text);
        
        // Ensure the text is valid UTF-8
        if (!mb_check_encoding($text, 'UTF-8')) {
            // If still invalid, use a more aggressive approach
            $text = mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text));
            
            // Final fallback: remove non-UTF-8 characters
            $text = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xC0-\xFD]/', '', $text);
        }
        
        return trim($text);
    }

    /**
     * Generate AI response for sales conversation
     */
    public function generateSalesResponse(
        string $customerMessage,
        AiSalesAgent $agent,
        Lead $lead,
        array $conversationHistory = [],
        ?Product $product = null,
        ?\App\Models\WhatsappInstance $instance = null // New parameter
    ): array {
        try {
            // Sanitize the customer message to prevent UTF-8 encoding errors
            $customerMessage = $this->sanitizeText($customerMessage);
            
            // Log cleaned message for debugging
            Log::debug('Message sanitized for OpenAI', [
                'agent_id' => $agent->id,
                'lead_id' => $lead->id,
                'message_length' => strlen($customerMessage),
                'encoding_valid' => mb_check_encoding($customerMessage, 'UTF-8')
            ]);
            
            // Pre-check if message is business-related
            if (!$this->isBusinessRelated($customerMessage, $agent)) {
                return [
                    'success' => true,
                    'response' => "I'm here to help with questions about our products and services. How can I assist you with that?",
                    'actions' => ['note' => 'Redirected off-topic question'],
                    'conversation_state' => 'INTRO',
                    'confidence' => 1.0
                ];
            }

            $prompt = $this->buildPromptWithAgent($customerMessage, $agent, $lead, $conversationHistory, $product, $instance);
            
            $response = $this->client->chat()->create([
                'model' => $this->defaultModel,
                'messages' => $prompt,
                'max_tokens' => 1000,
                'temperature' => 0.7,
                'presence_penalty' => 0.1,
                'frequency_penalty' => 0.1,
            ]);

            $aiResponse = $response->choices[0]->message->content;
            $constraints = $this->applyAgentConstraints($aiResponse, $agent, $product, $customerMessage);

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
                'customer_message' => substr($this->sanitizeText($customerMessage), 0, 100),
                'encoding_error' => !mb_check_encoding($customerMessage, 'UTF-8'),
                'original_length' => strlen($customerMessage)
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
    
    /**
     * Generate response for conversation engine (compatible with ConversationEngineCommand)
     */
    public function generateResponse(Lead $lead, ?string $customerMessage, array $context, string $currentState = 'INTRO'): array
    {
        try {
            // FEATURE GATE: Check AI credits before making API call
            $userId = $lead->user_id ?? auth()->id();
            $customerId = \App\Models\User::find($userId)?->customer_id ?? $userId;
            $billingStatus = BillingService::getCachedStatus($customerId);
            
            // Estimate credits needed (rough calculation)
            $estimatedTokens = strlen($customerMessage ?? '') * 1.3 + 500; // Base + message length
            $estimatedCredits = max(1, ceil($estimatedTokens / 3.846));
            
            if (!$billingStatus['permissions']['use_ai'] || ($billingStatus['limits']['ai_credits']['balance'] ?? 0) < $estimatedCredits) {
                Log::info('AI usage blocked due to insufficient credits', [
                    'user_id' => $userId,
                    'estimated_credits' => $estimatedCredits,
                    'available_credits' => $billingStatus['limits']['ai_credits']['balance'] ?? 0,
                    'subscription_plan' => $billingStatus['subscription']['plan'] ?? 'unknown'
                ]);
                
                return [
                    'success' => false,
                    'error' => 'insufficient_ai_credits',
                    'available_credits' => $billingStatus['limits']['ai_credits']['balance'] ?? 0,
                    'needed_credits' => $estimatedCredits
                ];
            }
            
            // Handle null or empty customer message
            if (empty($customerMessage)) {
                $customerMessage = 'Generate follow-up message based on conversation context';
            }
            
            // Get the AI sales agent for this lead
            $agent = $lead->aiSalesAgent;
            if (!$agent) {
                throw new \Exception('No AI sales agent found for this lead');
            }
            
            // Convert context to conversation history format expected by buildPromptWithAgent
            $recentMessages = $context['recent_messages'] ?? [];
            $conversationHistory = [];
            
            // Convert simple string messages to the expected format
            foreach ($recentMessages as $index => $messageContent) {
                if (is_string($messageContent)) {
                    // Sanitize string content and ensure it's not null
                    $content = $this->sanitizeText($messageContent);
                    if (!empty(trim($content))) {
                        $conversationHistory[] = [
                            'from_customer' => ($index % 2 === 0), // Alternate between customer and agent
                            'content' => $content
                        ];
                    }
                } elseif (is_array($messageContent) && isset($messageContent['content'])) {
                    // If already in expected format, sanitize content
                    $content = $messageContent['content'] ?? '';
                    $content = $this->sanitizeText($content);
                    if (!empty(trim($content))) {
                        $conversationHistory[] = [
                            'from_customer' => $messageContent['from_customer'] ?? false,
                            'content' => $content
                        ];
                    }
                }
            }
            
            // Get the primary product for this conversation
            $product = $lead->leadProducts()->where('is_primary_product', true)->first()?->product;
            
            // Use the existing generateSalesResponse method
            $response = $this->generateSalesResponse($customerMessage, $agent, $lead, $conversationHistory, $product);
            
            if ($response['success']) {
                // REVENUE PROTECTION: Deduct actual credits used
                $tokensUsed = $response['tokens_used'] ?? $estimatedTokens;
                $actualCredits = max(1, ceil($tokensUsed / 3.846));
                
                // Deduct credits from billing account (single source of truth)
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $deducted = \App\Services\BillingService::deductCredits(
                        $user, 
                        $actualCredits, 
                        "AI response generation for lead {$lead->id}"
                    );
                    
                    if ($deducted) {
                        Log::info('AI credits deducted from billing account', [
                            'user_id' => $userId,
                            'credits_deducted' => $actualCredits,
                            'tokens_used' => $tokensUsed,
                            'remaining_credits' => \App\Services\BillingService::getRemainingCredits($user)
                        ]);
                    } else {
                        Log::warning('Failed to deduct credits from billing account', [
                            'user_id' => $userId,
                            'credits_needed' => $actualCredits
                        ]);
                    }
                }
                
                return [
                    'success' => true,
                    'message_text' => $response['response'],
                    'new_conversation_state' => $currentState, // Keep current state for now
                    'actions' => $response['actions'] ?? [],
                    'confidence' => $response['confidence'] ?? 0.8,
                    'tokens_used' => $tokensUsed,
                    'credits_used' => $actualCredits
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $response['error'] ?? 'Failed to generate response'
                ];
            }
            
        } catch (\Exception $e) {
            Log::error('Generate Response Error: ' . $e->getMessage(), [
                'lead_id' => $lead->id,
                'message' => $customerMessage,
                'context' => $context
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
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
        ?\App\Models\Product $product = null,
        ?\App\Models\WhatsappInstance $instance = null // New parameter
    ): array {
        try {
            // Step 1: Search for relevant document content
            $ragService = app(\App\Services\RagSearchService::class);
            $productIds = $product ? [$product->id] : $lead->leadProducts()->pluck('product_id')->toArray();
            $relevantDocs = $ragService->searchDocuments($customerMessage, $productIds, 3);
            
            // Step 2: Build enhanced prompt with document context
            $prompt = $this->buildRAGPrompt($customerMessage, $agent, $lead, $conversationHistory, $product, $relevantDocs, $instance);
            
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
            $constraints = $this->applyAgentConstraints($aiResponse, $agent, $product, $customerMessage);

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
            return $this->generateSalesResponse($customerMessage, $agent, $lead, $conversationHistory, $product, $instance);
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
        array $relevantDocs,
        ?\App\Models\WhatsappInstance $instance = null // New parameter
    ): array {
        $systemPrompt = $this->buildSystemPrompt($agent, $lead, $product, $instance);
        
        // Enhanced context with RAG documents
        $contextPrompt = $this->buildContextPrompt($agent, $lead, $product);
        
        // Add relevant document context
        if (!empty($relevantDocs)) {
            $contextPrompt .= "\n\n=== RELEVANT DOCUMENTATION (LATEST & AUTHORITATIVE) ===\n";
            $contextPrompt .= "The following information from product documentation is the MOST CURRENT and should be your PRIMARY source for answers:\n\n";
            
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
            
            $contextPrompt .= "CRITICAL INSTRUCTIONS FOR USING DOCUMENTATION:\n";
            $contextPrompt .= "- ⚠️ ALWAYS prioritize information from these documents over any prior knowledge\n";
            $contextPrompt .= "- ✅ Use this documentation to provide accurate, detailed answers based on CURRENT data\n";
            $contextPrompt .= "- 📖 Reference specific sections when helpful (e.g., 'According to our latest documentation...')\n";
            $contextPrompt .= "- 🔗 If the customer needs more detailed information, mention you can provide the full documentation\n";
            $contextPrompt .= "- ❌ Do NOT use outdated or general knowledge - stick to these latest sources\n";
            $contextPrompt .= "- ⚡ This is the FRESHEST information available - last updated recently\n";
            $contextPrompt .= "===============================\n";
        } else {
            // No relevant docs found - still emphasize using latest FAQ and product data
            $contextPrompt .= "\n\n=== NO SPECIFIC DOCUMENTS FOUND ===\n";
            $contextPrompt .= "No relevant documentation was found for this query. Please rely on the LATEST FAQ and product information provided above.\n";
            $contextPrompt .= "Always mention that you're using the most current product information available.\n";
            $contextPrompt .= "===============================\n";
        }
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'assistant', 'content' => $contextPrompt],
        ];

        // Add conversation history
        foreach ($conversationHistory as $message) {
            // Ensure content is never null and is sanitized
            $content = $message['content'] ?? '';
            $content = $this->sanitizeText($content);
            
            // Only add message if content is not empty after sanitization
            if (!empty(trim($content))) {
                $messages[] = [
                    'role' => $message['from_customer'] ? 'user' : 'assistant',
                    'content' => $content
                ];
            }
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
        ?Product $product,
        ?\App\Models\WhatsappInstance $instance = null // New parameter
    ): array {
        $systemPrompt = $this->buildSystemPrompt($agent, $lead, $product, $instance);
        $contextPrompt = $this->buildContextPrompt($agent, $lead, $product);
        
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'assistant', 'content' => $contextPrompt],
        ];

        // Add conversation history
        foreach ($conversationHistory as $message) {
            // Ensure content is never null and is sanitized
            $content = $message['content'] ?? '';
            $content = $this->sanitizeText($content);
            
            // Only add message if content is not empty after sanitization
            if (!empty(trim($content))) {
                $messages[] = [
                    'role' => $message['from_customer'] ? 'user' : 'assistant',
                    'content' => $content
                ];
            }
        }

        // Add current message
        $messages[] = ['role' => 'user', 'content' => $customerMessage];

        return $messages;
    }

    /**
     * Build detailed system prompt
     */
  private function buildSystemPrompt(
    AiSalesAgent $agent,
    Lead $lead,
    ?Product $product,
    ?\App\Models\WhatsappInstance $instance = null
): string {

    $businessName = $agent->user?->business?->name ?? 'our company';

    // === CORE IDENTITY ===
    if ($instance && !empty($instance->instance_name)) {
        $displayName = $instance->instance_name;
    } else {
        $displayName = $agent->assistant_name;
    }
    $prompt = "You are {$displayName}, a senior sales consultant at {$businessName}. ";
    $prompt .= "You speak in first person, sound human, confident, and consultative. ";
    $prompt .= "Your responsibility is to guide prospects from interest to activation or purchase.";

    // === CONTEXT (INTERNAL, NOT CUSTOMER-LEAKING) ===
    if ($instance && $instance->purpose && $instance->purpose !== 'general') {
        $prompt .= " You primarily handle {$instance->purpose} inquiries.";
    }

    if ($agent->personality_description) {
        $prompt .= " Personality style: {$agent->personality_description}.";
    }

    if ($agent->communication_tone) {
        $prompt .= " Communication tone: {$agent->communication_tone}.";
    }

    // === SALES AUTHORITY ===
    $prompt .= "\n\nYOUR SALES AUTHORITY:";
    $prompt .= "\n- You can explain products, pricing, onboarding, and next steps";
    $prompt .= "\n- You can negotiate within defined limits";
    $prompt .= "\n- You are expected to confidently lead the conversation";

    if ($agent->allow_negotiation) {
        $prompt .= "\n- Maximum discount allowed: {$agent->max_discount_allowed}%";
        if ($agent->accept_installments) {
            $prompt .= "\n- Installments allowed: up to {$agent->max_installments} payments";
            $prompt .= "\n- Minimum down payment: {$agent->min_down_payment}%";
        }
    } else {
        $prompt .= "\n- Pricing is fixed and non-negotiable";
    }

    // === ESCALATION ===
    if ($agent->large_order_threshold) {
        $prompt .= "\n- Escalate orders above \${$agent->large_order_threshold} to {$agent->fallback_person}";
    }

    // === LANGUAGE ===
    $prompt .= "\n\nLANGUAGE:";
    $prompt .= "\n- Primary language: {$agent->primary_language}";
    if ($agent->auto_detect_language && $agent->additional_languages) {
        $prompt .= "\n- You may switch languages naturally when appropriate";
    }

    // === CRITICAL SALES PHASE CONTROL ===
    $prompt .= "\n\n🔥 SALES PHASE CONTROL (MANDATORY):";
    $prompt .= "\nYou MUST operate in one of three modes at all times:";

    $prompt .= "\n\n1️⃣ DISCOVERY MODE (Use sparingly):";
    $prompt .= "\n- Only when customer intent is unclear";
    $prompt .= "\n- Ask focused questions to identify pain";

    $prompt .= "\n\n2️⃣ SOLUTION MODE:";
    $prompt .= "\n- When the customer mentions a specific feature or problem";
    $prompt .= "\n- Explain how the solution removes their pain";
    $prompt .= "\n- Reduce questions, increase guidance";

    $prompt .= "\n\n3️⃣ CLOSING MODE (DEFAULT when intent is clear):";
    $prompt .= "\n- STOP asking exploratory questions";
    $prompt .= "\n- START giving clear next steps";
    $prompt .= "\n- Ask for confirmation to proceed";

    // === BUYING SIGNALS ===
    $prompt .= "\n\nCLOSING TRIGGERS (ANY ONE IS ENOUGH):";
    $prompt .= "\n- Customer asks about pricing, login, registration, errors, or setup";
    $prompt .= "\n- Customer names a feature (e.g. UCN, control number)";
    $prompt .= "\n- Customer states a clear pain (e.g. no cash, delays, security)";
    $prompt .= "\n- Customer provides business details (school name, phone, location)";

    // === FORBIDDEN BEHAVIOR ===
    $prompt .= "\n\n❌ FORBIDDEN:";
    $prompt .= "\n- Do NOT loop with repeated questions";
    $prompt .= "\n- Do NOT behave like documentation or FAQ";
    $prompt .= "\n- Do NOT delay next steps when intent is clear";

    // === SALES COMMUNICATION STYLE ===
    $prompt .= "\n\n✅ SALES COMMUNICATION STYLE:";
    $prompt .= "\n- Acknowledge briefly";
    $prompt .= "\n- Connect to pain or consequence";
    $prompt .= "\n- Lead with confidence";
    $prompt .= "\n- End with a clear next action or confirmation";

    // === CORE PRINCIPLE ===
    $prompt .= "\n\nCORE PRINCIPLE:";
    $prompt .= "\nYou are not here to educate endlessly.";
    $prompt .= "\nYou are here to REMOVE CONFUSION, CREATE MOMENTUM, AND CLOSE.";

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

        // Enhanced Product Context with fresh data loading
        if ($product) {
            // Force reload product with fresh FAQ data
            $freshProduct = Product::with('faqs')->find($product->id);
            $product = $freshProduct ?? $product;
            
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
            
            // Include FAQs for this product - ENHANCED with fresh loading
            if ($product->faqs && $product->faqs->count() > 0) {
                $context .= "\nFrequently Asked Questions (LATEST):\n";
                foreach ($product->faqs->sortByDesc('updated_at')->take(10) as $faq) { // Increased to 10 FAQs, sorted by latest
                    $context .= "Q: {$faq->question}\n";
                    $context .= "A: {$faq->answer}\n\n";
                }
                $context .= "IMPORTANT: Use ONLY these latest FAQs to answer customer questions. These are the most current answers available.\n";
            }
            
            // Include selling points if available
            if ($product->selling_points) {
                $sellingPoints = is_string($product->selling_points) 
                    ? json_decode($product->selling_points, true) 
                    : $product->selling_points;
                
                if (is_array($sellingPoints) && !empty($sellingPoints)) {
                    $context .= "\nKey Selling Points (LATEST):\n";
                    foreach ($sellingPoints as $point) {
                        $context .= "• {$point}\n";
                    }
                }
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
    private function applyAgentConstraints(string $aiResponse, AiSalesAgent $agent, ?Product $product, string $customerMessage = ''): array
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

        // Detect escalation needs - Enhanced detection
        $escalationPhrases = [
            // Direct human requests
            'transfer', 'manager', 'supervisor', 'human agent', 'speak to someone', 'talk to person',
            'human help', 'real person', 'live agent', 'customer service', 'support agent',
            'boss', 'owner', 'director', 'representative', 'speak to boss', 'talk to boss',
            // Complaints & dissatisfaction
            'not satisfied', 'complaint', 'escalate', 'unhappy', 'frustrated', 'angry',
            'not working', 'bad service', 'poor service', 'terrible', 'awful',
            // Complex requests
            'complex issue', 'technical problem', 'urgent matter', 'important issue',
            'serious problem', 'major issue', 'complicated', 'difficult',
            // Other triggers
            'cancel subscription', 'refund', 'legal action', 'lawyer', 'sue',
            'this is ridiculous', 'this is stupid', 'waste of time'
        ];
        
        $userMessage = strtolower($customerMessage);
        foreach ($escalationPhrases as $phrase) {
            if (stripos($userMessage, $phrase) !== false) {
                $actions['needs_escalation'] = [
                    'reason' => $phrase,
                    'priority' => $this->determineEscalationPriority($customerMessage),
                    'trigger_phrase' => $phrase
                ];
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
        
        // Detect appointment scheduling requests
        $appointmentKeywords = [
            'schedule', 'book', 'appointment', 'meeting', 'demo', 'consultation',
            'call', 'presentation', 'session', 'discuss', 'meet', 'available',
            'calendar', 'time slot', 'when can we', 'let\'s meet'
        ];
        
        $userMessage = strtolower($customerMessage);
        foreach ($appointmentKeywords as $keyword) {
            if (stripos($userMessage, $keyword) !== false) {
                $actions['schedule_appointment'] = [
                    'detected' => true,
                    'type' => 'demo',
                    'urgency' => 'normal'
                ];
                break;
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
     * Determine escalation priority based on user message content
     */
    private function determineEscalationPriority(string $userMessage): string
    {
        $urgentKeywords = [
            'urgent', 'emergency', 'asap', 'immediately', 'right now', 
            'angry', 'furious', 'terrible', 'awful', 'sue', 'lawyer',
            'cancel', 'refund', 'complaint'
        ];
        
        $highKeywords = [
            'frustrated', 'disappointed', 'unhappy', 'not working',
            'manager', 'supervisor', 'boss', 'director'
        ];
        
        $userMessage = strtolower($userMessage);
        
        foreach ($urgentKeywords as $keyword) {
            if (stripos($userMessage, $keyword) !== false) {
                return 'urgent';
            }
        }
        
        foreach ($highKeywords as $keyword) {
            if (stripos($userMessage, $keyword) !== false) {
                return 'high';
            }
        }
        
        return 'medium';
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

    /**
     * Check if customer message is business-related
     */
    private function isBusinessRelated(string $message, AiSalesAgent $agent): bool
    {
        $message = strtolower(trim($message));
        
        // Always allow common greetings and basic interactions
        $allowedBasics = [
            'hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening',
            'thanks', 'thank you', 'bye', 'goodbye', 'yes', 'no', 'ok', 'okay',
            'help', 'assist', 'support', 'info', 'information'
        ];
        
        foreach ($allowedBasics as $basic) {
            if (strpos($message, $basic) !== false) {
                return true;
            }
        }
        
        // Business and product-related keywords
        $businessKeywords = [
            'buy', 'purchase', 'price', 'cost', 'product', 'service', 'order',
            'delivery', 'shipping', 'payment', 'discount', 'offer', 'deal',
            'business', 'company', 'contact', 'email', 'phone', 'address',
            'available', 'stock', 'quantity', 'feature', 'specification',
            'warranty', 'guarantee', 'return', 'refund', 'exchange',
            'install', 'setup', 'how to', 'when', 'where', 'what is',
            'tell me about', 'show me', 'explain', 'demo', 'trial'
        ];
        
        // Check for business keywords
        foreach ($businessKeywords as $keyword) {
            if (strpos($message, $keyword) !== false) {
                return true;
            }
        }
        
        // Check if message mentions the business or products
        $businessName = $agent->user?->business?->name ?? '';
        if ($businessName && strpos($message, strtolower($businessName)) !== false) {
            return true;
        }
        
        // Check against common off-topic patterns
        $offTopicPatterns = [
            'capital city', 'capital of', 'country', 'geography', 'weather',
            'what time is it', 'current time', 'date today', 'calendar',
            'recipe', 'cooking', 'food recipe', 'how to cook',
            'news', 'politics', 'election', 'government', 'president',
            'sports', 'football', 'basketball', 'soccer', 'game score',
            'movie', 'film', 'entertainment', 'celebrity', 'actor',
            'mathematics', 'solve equation', 'calculate', 'math problem',
            'translate', 'translation', 'language learning', 'grammar',
            'history', 'historical', 'when was', 'who invented',
            'medical advice', 'health problem', 'symptoms', 'disease',
            'legal advice', 'lawyer', 'lawsuit', 'court'
        ];
        
        foreach ($offTopicPatterns as $pattern) {
            if (strpos($message, $pattern) !== false) {
                return false;
            }
        }
        
        // If message is very short (likely greeting/basic response), allow it
        if (strlen($message) < 10) {
            return true;
        }
        
        // Default: allow if unclear (better to be permissive for sales)
        return true;
    }

    /**
     * Generate AI-powered context summary for CRM migration
     */
    public function generateContextSummary(string $prompt): array
    {
        try {
            $sanitizedPrompt = $this->sanitizeText($prompt);
            
            $response = $this->client->chat()->create([
                'model' => $this->defaultModel,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert CRM analyst tasked with creating comprehensive client context summaries for AI sales agents. Generate clear, structured, actionable summaries that help AI agents understand client history and approach strategies.'],
                    ['role' => 'user', 'content' => $sanitizedPrompt]
                ],
                'max_tokens' => 1000,
                'temperature' => 0.3
            ]);

            $generatedSummary = $response->choices[0]->message->content ?? '';
            
            if (empty($generatedSummary)) {
                throw new \Exception('Empty response from OpenAI API');
            }

            return [
                'success' => true,
                'summary' => $this->sanitizeText($generatedSummary),
                'tokens_used' => $response->usage->totalTokens ?? 0
            ];

        } catch (\Exception $e) {
            Log::error('Context summary generation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'summary' => null
            ];
        }
    }
}