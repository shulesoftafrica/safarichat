<?php

namespace App\Services;

use App\Models\NurtureLibrary;
use App\Models\BusinessContact;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NurtureMessageService
{
    /**
     * Reframe a pushy follow-up into a value-first nurture message
     *
     * @param string $originalMessage
     * @param array $ghostingAnalysis
     * @param BusinessContact $contact
     * @return array
     */
    public static function reframeMessage($originalMessage, $ghostingAnalysis, $contact)
    {
        // Extract product context from ghosting analysis
        $productId = $ghostingAnalysis['product_context'] ?? null;

        // Fetch matching value nuggets (product-specific first, then fallback)
        $valueNuggets = self::fetchValueNuggets($contact, $productId);

        if ($valueNuggets->isEmpty()) {
            // No matching value nuggets, use generic AI reframing
            return self::genericNurtureReframe($originalMessage, $ghostingAnalysis, $contact);
        }

        // Use AI to select best nugget and personalize
        return self::aiReframeWithNuggets($originalMessage, $ghostingAnalysis, $contact, $valueNuggets);
    }

    /**
     * Fetch matching value nuggets from library for a contact
     * Prioritizes product-specific nuggets, falls back to business-level
     *
     * @param BusinessContact $contact
     * @param int|null $productId Product context from conversation
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private static function fetchValueNuggets($contact, $productId = null)
    {
        // PRIMARY: Try product-specific nuggets first
        if ($productId) {
            $productNuggets = NurtureLibrary::where('business_id', $contact->business_id)
                ->where('product_id', $productId)
                ->where('is_business_level', false)
                ->matchingForContact($contact)
                ->limit(3)
                ->get();

            if ($productNuggets->isNotEmpty()) {
                Log::info("Using product-specific nuggets for product_id: {$productId}");
                return $productNuggets;
            }
        }

        // FALLBACK 1: Try business-level nuggets
        $businessNuggets = NurtureLibrary::where('business_id', $contact->business_id)
            ->where('is_business_level', true)
            ->whereNull('product_id')
            ->matchingForContact($contact)
            ->limit(3)
            ->get();

        if ($businessNuggets->isNotEmpty()) {
            Log::info("Using business-level nuggets (no product context or product nuggets unavailable)");
            return $businessNuggets;
        }

        // FALLBACK 2: Use any available nuggets for this business
        Log::info("Using any available nuggets for business_id: {$contact->business_id}");
        return NurtureLibrary::where('business_id', $contact->business_id)
            ->matchingForContact($contact)
            ->limit(3)
            ->get();
    }

    /**
     * AI reframe with value nuggets from library
     *
     * @param string $originalMessage
     * @param array $ghostingAnalysis
     * @param BusinessContact $contact
     * @param \Illuminate\Database\Eloquent\Collection $valueNuggets
     * @return array
     */
    private static function aiReframeWithNuggets($originalMessage, $ghostingAnalysis, $contact, $valueNuggets)
    {
        $apiKey = env('OPENAI_API_KEY');
        
        if (!$apiKey) {
            Log::error('OpenAI API key not configured for nurture message reframing');
            return [
                'success' => false,
                'error' => 'AI service not configured',
            ];
        }

        // Build conversation history string
        $historyString = '';
        foreach ($ghostingAnalysis['conversation_history'] as $msg) {
            $direction = $msg['direction'] === 'incoming' ? 'Contact' : 'You';
            $historyString .= "{$direction}: {$msg['message']}\n";
        }

        // Build value nuggets string
        $nuggetsString = '';
        foreach ($valueNuggets as $index => $nugget) {
            $nuggetsString .= ($index + 1) . ". [{$nugget->content_type}] {$nugget->title}\n";
            $nuggetsString .= "   Content: {$nugget->content_body}\n";
            if ($nugget->content_url) {
                $nuggetsString .= "   Link: {$nugget->content_url}\n";
            }
            $nuggetsString .= "\n";
        }

        // Build AI prompt
        $prompt = self::buildAIPrompt(
            $originalMessage,
            $ghostingAnalysis,
            $contact,
            $historyString,
            $nuggetsString
        );

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an expert Sales Psychologist specializing in re-engaging cold leads through value-based nurturing. You transform pushy follow-ups into helpful gifts that provide immediate value without asking for anything in return.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.7,
                'max_tokens' => 800,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $aiResponse = $result['choices'][0]['message']['content'] ?? null;

                if ($aiResponse) {
                    // Try to parse JSON response
                    $parsed = self::parseAIResponse($aiResponse);
                    
                    if ($parsed) {
                        return [
                            'success' => true,
                            'refined_message' => $parsed['refined_message'],
                            'value_type' => $parsed['value_type'] ?? 'insight',
                            'primary_benefit' => $parsed['primary_benefit'] ?? null,
                            'confidence_score' => $parsed['confidence_score'] ?? 0.75,
                            'reasoning' => $parsed['reasoning'] ?? null,
                            'nugget_id' => self::selectNuggetFromResponse($parsed, $valueNuggets),
                            'tokens_used' => $result['usage']['total_tokens'] ?? 0,
                        ];
                    }
                }
            }

            Log::error('OpenAI API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'AI reframing failed',
            ];

        } catch (\Exception $e) {
            Log::error('Exception during AI reframing', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build comprehensive AI prompt
     *
     * @param string $originalMessage
     * @param array $ghostingAnalysis
     * @param BusinessContact $contact
     * @param string $historyString
     * @param string $nuggetsString
     * @return string
     */
    private static function buildAIPrompt($originalMessage, $ghostingAnalysis, $contact, $historyString, $nuggetsString)
    {
        $currentMonth = now()->format('F');
        
        return <<<PROMPT
# CONTEXT
**Contact Name:** {$contact->name}
**Job Title:** {$contact->job_title}
**Industry:** {$contact->industry}
**Lead Status:** {$contact->lead_status}
**Current Month:** {$currentMonth}

**Conversation History (Last 5 Messages):**
{$historyString}

**Ghosting Analysis:**
- Last {$ghostingAnalysis['unanswered_count']} messages from salesperson: NO REPLY
- Last outgoing message sent: {$ghostingAnalysis['days_since_last_contact']} days ago
- Contact's preferred language: {$ghostingAnalysis['detected_language']}
- Contact's tone preference: {$ghostingAnalysis['detected_tone']}

**Original Message (What salesperson typed):**
{$originalMessage}

**Available Value Nuggets from Knowledge Base:**
{$nuggetsString}

# CRITICAL RULES - MUST FOLLOW

1. **ABSOLUTE PROHIBITION - NEVER USE THESE PHRASES:**
   - ❌ "I hope this message finds you well"
   - ❌ "I wanted to follow up"
   - ❌ "Just checking in"
   - ❌ "Please let me know how you'd like to proceed"
   - ❌ "Do you have time for a quick call?"
   - ❌ Any question that asks for a decision/meeting/next step

2. **GHOSTING DETECTION - Contact is NOT responding:**
   - DO NOT ask for anything (no meetings, no decisions, no calls)
   - DO NOT reference the fact they haven't replied
   - PROVIDE VALUE FIRST using one of the value nuggets above

3. **LANGUAGE MATCHING:**
   - Use {$ghostingAnalysis['detected_language']} language
   - NEVER switch languages mid-conversation

4. **VALUE-FIRST STRUCTURE:**
   [Warm Greeting] + [Immediate Value from Nugget] + [No-Pressure Close]

5. **THE "NO-PRESSURE" CLOSE (MANDATORY):**
   End with: "No pressure, just thought you'd find this helpful!" or similar

# YOUR TASK

Transform the "Original Message" into a value-first nurture message using ONE of the value nuggets above.

**Output Format (JSON):**
```json
{
  "refined_message": "[Your rewritten message here]",
  "value_type": "[case_study|tip|insight|video|article]",
  "primary_benefit": "[What value was provided]",
  "confidence_score": 0.85,
  "reasoning": "[Brief explanation]"
}
```

**REMINDER:** This contact is ghosting. DO NOT ASK FOR ANYTHING. PROVIDE VALUE ONLY.
PROMPT;
    }

    /**
     * Parse AI JSON response
     *
     * @param string $response
     * @return array|null
     */
    private static function parseAIResponse($response)
    {
        // Try to extract JSON from response
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $json = $matches[0];
            $decoded = json_decode($json, true);
            
            if ($decoded && isset($decoded['refined_message'])) {
                return $decoded;
            }
        }

        // If JSON parsing fails, try to extract message directly
        if (strpos($response, 'refined_message') !== false) {
            return [
                'refined_message' => $response,
                'value_type' => 'insight',
                'confidence_score' => 0.70,
            ];
        }

        return null;
    }

    /**
     * Select which nugget was used based on AI response
     *
     * @param array $parsed
     * @param \Illuminate\Database\Eloquent\Collection $valueNuggets
     * @return int|null
     */
    private static function selectNuggetFromResponse($parsed, $valueNuggets)
    {
        $refinedMessage = strtolower($parsed['refined_message']);
        
        // Try to match nugget by checking if content appears in refined message
        foreach ($valueNuggets as $nugget) {
            $nuggetWords = explode(' ', strtolower($nugget->title));
            $matchCount = 0;
            
            foreach ($nuggetWords as $word) {
                if (strlen($word) > 4 && strpos($refinedMessage, $word) !== false) {
                    $matchCount++;
                }
            }
            
            // If at least 2 significant words match, assume this nugget was used
            if ($matchCount >= 2) {
                return $nugget->id;
            }
        }

        // Default to first nugget
        return $valueNuggets->first()->id ?? null;
    }

    /**
     * Generic nurture reframe without value nuggets (fallback)
     *
     * @param string $originalMessage
     * @param array $ghostingAnalysis
     * @param BusinessContact $contact
     * @return array
     */
    private static function genericNurtureReframe($originalMessage, $ghostingAnalysis, $contact)
    {
        $language = $ghostingAnalysis['detected_language'];
        
        // Simple reframe: remove pushy language, add no-pressure close
        $refined = $originalMessage;
        
        // Remove common pushy phrases
        $pushyPhrases = [
            'I hope this message finds you well',
            'I wanted to follow up',
            'Just checking in',
            'Please let me know',
            'Do you have time',
            'How would you like to proceed',
        ];
        
        foreach ($pushyPhrases as $phrase) {
            $refined = str_ireplace($phrase, '', $refined);
        }
        
        // Add friendly no-pressure close
        if ($language === 'sw') {
            $refined = trim($refined) . "\n\nHakuna haraka, nilikuwa tu nataka kushare! 😊";
        } else {
            $refined = trim($refined) . "\n\nNo pressure, just wanted to share this with you! 😊";
        }
        
        return [
            'success' => true,
            'refined_message' => $refined,
            'value_type' => 'generic',
            'confidence_score' => 0.50,
            'nugget_id' => null,
            'reasoning' => 'No matching value nuggets found, used generic reframe',
        ];
    }
}
