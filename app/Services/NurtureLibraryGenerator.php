<?php

namespace App\Services;

use App\Models\Product;
use App\Models\NurtureLibrary;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class NurtureLibraryGenerator
{
    /**
     * Generate product-specific nurture messages
     * Called automatically when product is created/updated
     * 
     * @param Product $product
     * @return \Illuminate\Support\Collection
     */
    public function generateForProduct(Product $product)
    {
        $prompt = $this->buildProductPrompt($product);
        
        try {
            $response = OpenAI::chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert B2B content strategist specializing in value-first messaging.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.8,
            ]);

            $content = $response->choices[0]->message->content;
            
            // Extract JSON from response (handle markdown code blocks)
            $content = preg_replace('/```json\s*|\s*```/', '', $content);
            $nuggets = json_decode($content, true);

            if (!is_array($nuggets)) {
                Log::error("AI did not return valid JSON for product: {$product->name}");
                return collect([]);
            }

            $created = [];
            foreach ($nuggets as $nugget) {
                $created[] = NurtureLibrary::create([
                    'user_id' => $product->user_id,
                    'business_id' => $product->business_id,
                    'product_id' => $product->id,
                    'is_business_level' => false,
                    'title' => $nugget['title'] ?? 'Untitled',
                    'content_type' => $nugget['content_type'] ?? 'tip',
                    'content_body' => $nugget['content_body'] ?? '',
                    'target_industry' => $nugget['target_industry'] ?? null,
                    'target_job_title' => $nugget['target_job_title'] ?? null,
                    'target_pain_point' => $nugget['target_pain_point'] ?? null,
                    'language' => $nugget['language'] ?? 'en',
                    'tone' => $nugget['tone'] ?? 'casual',
                    'usage_count' => 0,
                    'success_rate' => 0,
                ]);
            }

            Log::info("Generated " . count($created) . " nurture messages for product: {$product->name}");
            
            return collect($created);
            
        } catch (\Exception $e) {
            Log::error("Failed to generate nurture messages: " . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Regenerate messages (keeps high performers, generates new ones)
     * 
     * @param Product $product
     * @return array
     */
    public function regenerateForProduct(Product $product)
    {
        // Keep messages with >20% success rate
        $keepMessages = NurtureLibrary::where('product_id', $product->id)
            ->where('success_rate', '>', 20)
            ->get();

        // Archive low performers (<5% and used at least 10 times)
        $deleted = NurtureLibrary::where('product_id', $product->id)
            ->where('success_rate', '<', 5)
            ->where('usage_count', '>', 10)
            ->delete();

        // Generate new messages
        $newMessages = $this->generateForProduct($product);

        return [
            'kept' => $keepMessages->count(),
            'deleted' => $deleted,
            'generated' => $newMessages->count(),
            'total' => $keepMessages->count() + $newMessages->count()
        ];
    }

    /**
     * Build AI prompt for product-specific nuggets
     * 
     * @param Product $product
     * @return string
     */
    private function buildProductPrompt(Product $product)
    {
        $businessName = $product->business->name ?? 'Our Company';
        $businessIndustry = $product->business->industry ?? 'Technology';
        
        // Get customer testimonials if available
        $testimonials = '';
        if ($product->business && method_exists($product->business, 'testimonials')) {
            $testimonials = $product->business->testimonials ?? '';
        }

        return "
Generate 8-10 VALUE-FIRST nurture messages SPECIFICALLY for this product:

PRODUCT DETAILS:
- Name: {$product->name}
- Description: {$product->description}
- Key Features: " . (is_array($product->key_features) ? implode(', ', $product->key_features) : $product->selling_points) . "
- Target Industry: {$product->target_industry}
- Price: {$product->retail_price} (use to position value)

BUSINESS CONTEXT:
- Company: {$businessName}
- Industry: {$businessIndustry}
- Customer Success Stories: {$testimonials}

TARGET AUDIENCE:
- Industry Focus: {$product->target_industry}
- Decision Makers: School Directors, Principals, Administrators

REQUIREMENTS:
1. Each message MUST mention the SPECIFIC product name: \"{$product->name}\"
2. Focus on REAL customer outcomes with THIS exact product
3. NO generic company messages - product-specific only
4. Content types distribution:
   - case_study: 30% (real customer success stories)
   - tip: 30% (quick actionable tips using product features)
   - insight: 20% (industry insights related to product)
   - testimonial: 20% (customer quotes about product)
5. Language distribution:
   - Swahili: 40% (casual, friendly tone)
   - English: 60% (professional but approachable)
6. Tone: Casual, helpful, value-first, NO sales pressure

ABSOLUTE PROHIBITIONS (NEVER USE THESE PHRASES):
- \"I hope this message finds you well\"
- \"Just checking in\"
- \"Following up\"
- \"Please let me know how to proceed\"
- \"Did you get my last message\"
- \"When can we schedule\"
- Any questions or asks

APPROVED STRUCTURE (2-3 sentences max):
[Warm Greeting] + [Specific Product Value/Outcome] + [Friendly No-Pressure Close]

EXAMPLES:

❌ BAD (Generic, pushy, no product mention):
\"Hope you're well! Following up on our school management solutions. Let me know if you're interested in a demo.\"

✅ GOOD (Product-specific, value-first, helpful):
\"Habari! ABC School reduced parent complaints by 40% using Parent Portal's real-time SMS notifications. Parents love the instant fee payment confirmations. Thought this might be helpful since you're heading into intake season! 😊\"

✅ GOOD (Swahili, product tip):
\"Hujambo! Tip: Walimu wa Upendo Primary wanapata attendance reports kwa SMS moja tu using our Parent Portal. Inasaidia kupunguza simu 20+ kila siku. Naweza share video ya 2 minutes? 📱\"

✅ GOOD (English case study):
\"Morning! St. Mary's cut registration time by 75% using Parent Portal's auto-confirmation feature. Each parent gets instant WhatsApp confirmation when they submit forms. Game changer for intake season!\"

OUTPUT FORMAT:
Return ONLY a valid JSON array. Each object must have these exact fields:

[
  {
    \"title\": \"Case Study: 40% Fewer Parent Complaints\",
    \"content_type\": \"case_study\",
    \"content_body\": \"ABC School reduced parent complaints by 40% using Parent Portal's real-time notifications. Parents love the instant fee payment confirmations. Thought helpful for intake season! 😊\",
    \"target_industry\": \"Education\",
    \"target_job_title\": \"School Director\",
    \"target_pain_point\": \"parent_communication\",
    \"language\": \"en\",
    \"tone\": \"casual\"
  },
  {
    \"title\": \"Tip: Punguza Simu za Wazazi\",
    \"content_type\": \"tip\",
    \"content_body\": \"Walimu wa Upendo Primary wanapata attendance reports kwa SMS moja using Parent Portal. Inasaidia kupunguza simu 20+ daily. Naweza share demo ya 2 min?\",
    \"target_industry\": \"Education\",
    \"target_job_title\": \"Mkurugenzi\",
    \"target_pain_point\": \"communication_overload\",
    \"language\": \"sw\",
    \"tone\": \"friendly\"
  }
]

Generate 8-10 messages following this exact format. Return ONLY the JSON array, no other text.
";
    }
}
