<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Chooses which FAQs go into the AI prompt.
 *
 * The prompt used to take the ten most recently edited FAQs of a product, so a business with 26 FAQs had 16 of
 * them invisible to the AI, and the answer to a customer's question was often one of those. Now every FAQ is
 * sent when they fit in the budget, and when they do not, the ones that match the customer's message are sent
 * first (the customer asking about price gets the price FAQ, not the ten newest).
 */
class FaqSelector
{
    /** Short, common words in English and Swahili that say nothing about what a question is about. */
    private const STOP_WORDS = [
        'the', 'and', 'for', 'you', 'your', 'are', 'can', 'how', 'what', 'who', 'does', 'with', 'this', 'that', 'from',
        'have', 'will', 'was', 'any', 'get', 'its', 'our', 'not', 'but', 'all', 'about', 'there', 'their', 'when', 'which',
        'kwa', 'ya', 'wa', 'na', 'ni', 'za', 'la', 'hii', 'hiyo', 'yako', 'yangu', 'nini', 'gani', 'je', 'au', 'pia',
        'kama', 'ili', 'katika', 'huu', 'huo', 'sana', 'tafadhali', 'naomba',
    ];

    /**
     * @param Collection<int, object> $faqs   items with ->question and ->answer
     * @param int                     $maxChars budget for the question + answer text sent to the AI (12,000 characters holds about 40 typical FAQs)
     * @param int                     $maxItems hard cap on the number of FAQs
     * @return Collection<int, object>
     */
    public function select(Collection $faqs, ?string $customerMessage = null, int $maxChars = 12000, int $maxItems = 60): Collection
    {
        $faqs = $faqs->values();
        $ordered = $faqs->sortBy(fn ($faq) => [$faq->sort_order ?? 0, $faq->id ?? 0])->values();

        if ($this->size($ordered) <= $maxChars && $ordered->count() <= $maxItems) {
            return $ordered;
        }

        $queryTokens = $this->tokens((string) $customerMessage);

        $scored = $ordered->map(fn ($faq, $position) => [
            'faq'      => $faq,
            'score'    => $this->score($queryTokens, $faq),
            'position' => $position,
        ]);

        // Best match first; ties and unmatched FAQs keep the order the business gave them.
        $ranked = $scored->sort(fn ($a, $b) => [$b['score'], $a['position']] <=> [$a['score'], $b['position']])->values();

        $picked = collect();
        $used = 0;

        foreach ($ranked as $row) {
            $length = $this->length($row['faq']);

            if ($used + $length > $maxChars || $picked->count() >= $maxItems) {
                continue;
            }

            $picked->push($row);
            $used += $length;
        }

        // Present them in the business's own order so the prompt reads naturally.
        return $picked->sortBy('position')->pluck('faq')->values();
    }

    private function size(Collection $faqs): int
    {
        return (int) $faqs->sum(fn ($faq) => $this->length($faq));
    }

    private function length(object $faq): int
    {
        return mb_strlen((string) $faq->question) + mb_strlen((string) $faq->answer) + 8;
    }

    /**
     * @param list<string> $queryTokens
     */
    private function score(array $queryTokens, object $faq): int
    {
        if ($queryTokens === []) {
            return 0;
        }

        $question = $this->tokens((string) $faq->question);
        $answer = $this->tokens((string) $faq->answer);
        $score = 0;

        foreach ($queryTokens as $token) {
            if ($this->contains($question, $token)) {
                $score += 3;
            } elseif ($this->contains($answer, $token)) {
                $score += 1;
            }
        }

        return $score;
    }

    /**
     * A word matches itself, or a longer form of itself ("payments" / "payment", "kulipa" / "kulipia").
     *
     * @param list<string> $haystack
     */
    private function contains(array $haystack, string $token): bool
    {
        foreach ($haystack as $word) {
            if ($word === $token) {
                return true;
            }

            if (mb_strlen($token) >= 5 && mb_strlen($word) >= 5 && mb_substr($word, 0, 5) === mb_substr($token, 0, 5)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function tokens(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique(array_filter(
            $words,
            fn ($word) => mb_strlen($word) >= 3 && !in_array($word, self::STOP_WORDS, true)
        )));
    }
}
