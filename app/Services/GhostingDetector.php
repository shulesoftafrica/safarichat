<?php

namespace App\Services;

use App\Models\BusinessContact;
use App\Models\IncomingMessage;
use App\Models\OutgoingMessage;
use Illuminate\Support\Facades\Log;

class GhostingDetector
{
    /**
     * Analyze whether a contact is ghosting (not responding to messages)
     *
     * @param int $contactId
     * @return array Analysis results including ghosting status
     */
    public static function analyze($contactId)
    {
        // Get last outgoing message
        $lastOutgoing = OutgoingMessage::where('business_contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->first();

        // Get last incoming message
        $lastIncoming = IncomingMessage::where('business_contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastOutgoing) {
            return [
                'is_ghosting' => false,
                'unanswered_count' => 0,
                'days_since_last_contact' => 0,
                'reason' => 'No outgoing messages found'
            ];
        }

        // Count unanswered messages (outgoing messages sent after last incoming)
        $unansweredCount = OutgoingMessage::where('business_contact_id', $contactId)
            ->where('created_at', '>', $lastIncoming->created_at ?? '1970-01-01')
            ->count();

        // Calculate days since last outgoing message
        $daysSinceLastContact = $lastOutgoing->created_at->diffInDays(now());

        // Ghosting criteria: 2+ unanswered messages AND 3+ days since last contact
        $isGhosting = $unansweredCount >= 2 && $daysSinceLastContact >= 3;

        // Get conversation history (last 5 messages)
        $conversationHistory = self::getConversationHistory($contactId, 5);

        // Detect language and tone
        $detectedLanguage = self::detectLanguage($contactId);
        $detectedTone = self::detectTone($contactId);

        // Get contact details
        $contact = BusinessContact::find($contactId);

        // Detect which product was being discussed
        $productContext = self::detectProductContext($contactId);

        return [
            'is_ghosting' => $isGhosting,
            'unanswered_count' => $unansweredCount,
            'days_since_last_contact' => $daysSinceLastContact,
            'conversation_history' => $conversationHistory,
            'detected_language' => $detectedLanguage,
            'detected_tone' => $detectedTone,
            'product_context' => $productContext,
            'contact_name' => $contact->name ?? 'Unknown',
            'job_title' => $contact->job_title ?? null,
            'industry' => $contact->industry ?? null,
            'last_incoming_at' => $lastIncoming->created_at ?? null,
        ];
    }

    /**
     * Get conversation history (mixed incoming and outgoing)
     *
     * @param int $contactId
     * @param int $limit
     * @return array
     */
    private static function getConversationHistory($contactId, $limit = 5)
    {
        $incoming = IncomingMessage::where('business_contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($msg) {
                return [
                    'message' => $msg->message_body,
                    'direction' => 'incoming',
                    'created_at' => $msg->created_at,
                ];
            });

        $outgoing = OutgoingMessage::where('business_contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($msg) {
                return [
                    'message' => $msg->message_body ?? $msg->message,
                    'direction' => 'outgoing',
                    'created_at' => $msg->created_at,
                ];
            });

        // Merge and sort by created_at
        $allMessages = $incoming->concat($outgoing)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->toArray();

        return $allMessages;
    }

    /**
     * Detect primary language from incoming messages
     *
     * @param int $contactId
     * @return string 'en', 'sw', or 'mixed'
     */
    private static function detectLanguage($contactId)
    {
        $languages = IncomingMessage::where('business_contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($msg) {
                // Simple language detection based on keywords
                $text = strtolower($msg->message_body);

                // Swahili keywords
                $swahiliKeywords = ['habari', 'asante', 'sawa', 'ndiyo', 'hapana', 'pole', 'tafadhali', 'samahani'];
                $swahiliCount = 0;
                foreach ($swahiliKeywords as $keyword) {
                    if (str_contains($text, $keyword)) {
                        $swahiliCount++;
                    }
                }

                return $swahiliCount > 0 ? 'sw' : 'en';
            });

        $swahiliCount = $languages->filter(fn($lang) => $lang === 'sw')->count();
        $englishCount = $languages->filter(fn($lang) => $lang === 'en')->count();

        if ($swahiliCount > $englishCount) {
            return 'sw';
        } elseif ($englishCount > $swahiliCount) {
            return 'en';
        } else {
            return 'mixed';
        }
    }

    /**
     * Detect tone/formality from incoming messages
     *
     * @param int $contactId
     * @return string 'formal', 'casual', or 'friendly'
     */
    private static function detectTone($contactId)
    {
        $incomingMessages = IncomingMessage::where('business_contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $formalityScore = 0;

        foreach ($incomingMessages as $msg) {
            $text = strtolower($msg->message_body);

            // Formal indicators
            if (preg_match('/\b(dear|sincerely|regards|kindly|respectfully)\b/', $text)) {
                $formalityScore += 2;
            }

            // Casual indicators
            if (preg_match('/\b(hi|hey|lol|haha|yeah|yup)\b/', $text)) {
                $formalityScore -= 1;
            }

            // Check for full sentences vs fragments
            if (str_contains($text,'. ') && str_word_count($text) > 10) {
                $formalityScore += 1;
            }

            // Check for emojis (casual)
            if (preg_match('/[\x{1F600}-\x{1F64F}]/u', $text)) {
                $formalityScore -= 1;
            }
        }

        if ($formalityScore > 2) {
            return 'formal';
        } elseif ($formalityScore < -1) {
            return 'casual';
        } else {
            return 'friendly';
        }
    }

    /**
     * Check if contact has opted out
     *
     * @param int $contactId
     * @return bool
     */
    public static function hasOptedOut($contactId)
    {
        $contact = BusinessContact::find($contactId);

        if (!$contact) {
            return false;
        }

        // Check contact's opt_out_status field
        if (isset($contact->opt_out_status) && $contact->opt_out_status) {
            return true;
        }

        // Check last 3 incoming messages for opt-out keywords
        $optOutKeywords = [
            'stop', 'unsubscribe', 'opt out', 'remove me',
            'acha kunifuatilia', 'sitaki tena', 'usiendelee', 'acha'
        ];

        $recentMessages = IncomingMessage::where('business_contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        foreach ($recentMessages as $msg) {
            $text = strtolower($msg->message_body);
            foreach ($optOutKeywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    // Auto-update contact opt-out status
                    $contact->update([
                        'opt_out_status' => true,
                        'opt_out_at' => now()
                    ]);
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Detect negative sentiment or complaints
     *
     * @param int $contactId
     * @return array
     */
    public static function detectNegativeSentiment($contactId)
    {
        $negativeKeywords = [
            'complaint', 'angry', 'frustrated', 'disappointed', 'terrible',
            'awful', 'horrible', 'worst', 'scam', 'fraud',
            // Swahili
            'mbaya', 'hasira', 'kuchukiza', 'vibaya', 'sina raha'
        ];

        $questionKeywords = [
            'when', 'why', 'problem', 'issue', 'error', 'not working',
            // Swahili
            'lini', 'kwa nini', 'tatizo', 'kasoro', 'haifanyi kazi'
        ];

        $matchedKeywords = [];
        $hasUnresolvedIssue = false;

        $lastIncoming = IncomingMessage::where('business_contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastIncoming) {
            $text = strtolower($lastIncoming->message_body);

            // Check for negative keywords
            foreach ($negativeKeywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $matchedKeywords[] = $keyword;
                }
            }

            // Check for unresolved questions
            foreach ($questionKeywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $hasUnresolvedIssue = true;
                    break;
                }
            }
        }

        $hasNegativeSentiment = count($matchedKeywords) > 0;
        $requiresHumanReview = $hasNegativeSentiment || $hasUnresolvedIssue;

        return [
            'has_negative_sentiment' => $hasNegativeSentiment,
            'has_unresolved_issue' => $hasUnresolvedIssue,
            'matched_keywords' => $matchedKeywords,
            'requires_human_review' => $requiresHumanReview,
        ];
    }

    /**
     * Detect which product was being discussed in the conversation
     *
     * @param int $contactId
     * @return int|null Product ID if detected, null otherwise
     */
    public static function detectProductContext($contactId)
    {
        $contact = BusinessContact::find($contactId);
        if (!$contact || !$contact->business_id) {
            return null;
        }

        // Get last 5 outgoing messages
        $recentMessages = OutgoingMessage::where('business_contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->pluck('message');

        if ($recentMessages->isEmpty()) {
            return null;
        }

        // Get all products for this business
        $products = \App\Models\Product::where('business_id', $contact->business_id)->get();

        if ($products->isEmpty()) {
            return null;
        }

        // Scan messages for product mentions
        foreach ($products as $product) {
            foreach ($recentMessages as $message) {
                $message = strtolower($message);
                $productName = strtolower($product->name);
                
                // Check for exact product name match
                if (str_contains($message, $productName)) {
                    Log::info("Product context detected: {$product->name} for contact: {$contactId}");
                    return $product->id;
                }
                
                // Check for product SKU if available
                if ($product->sku && str_contains($message, strtolower($product->sku))) {
                    return $product->id;
                }
                
                // Check for product tags/keywords
                if (!empty($product->tags) && is_array($product->tags)) {
                    foreach ($product->tags as $tag) {
                        if (str_contains($message, strtolower($tag))) {
                            return $product->id;
                        }
                    }
                }
            }
        }

        return null; // No specific product context detected
    }

    /**
     * Analyze multiple contacts for ghosting (batch processing)
     *
     * @param array $contactIds
     * @return array
     */
    public static function analyzeMultiple(array $contactIds)
    {
        $results = [];

        foreach ($contactIds as $contactId) {
            $results[$contactId] = self::analyze($contactId);
        }

        return $results;
    }
}
