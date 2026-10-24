<?php

namespace App\Services;

use App\Models\Product;

/**
 * Searches a business's own product catalog for items matching a customer's
 * question (or a product description extracted from an image). Used to ground AI
 * replies in REAL prices/stock instead of invented ones.
 */
class CatalogSearchService
{
    /**
     * @return array<int, array{name:string, price:float, quantity:int, category:string}>
     */
    public function search($agent, $lead, string $query, int $limit = 6): array
    {
        $ownerId    = $agent->user_id ?? ($lead->user_id ?? null);
        $businessId = $lead->business_id ?? null;
        if (! $ownerId && ! $businessId) {
            return [];
        }

        $tokens = $this->tokens($query);
        if (empty($tokens)) {
            return [];
        }

        $candidates = Product::query()
            ->where('status', 'active')
            ->where(function ($w) use ($ownerId, $businessId) {
                if ($ownerId)    { $w->orWhere('user_id', $ownerId); }
                if ($businessId) { $w->orWhere('business_id', $businessId); }
            })
            ->where(function ($w) use ($tokens) {
                foreach ($tokens as $t) {
                    $like = '%' . $t . '%';
                    $w->orWhere('name', 'ILIKE', $like)
                      ->orWhere('category', 'ILIKE', $like)
                      ->orWhere('sku', 'ILIKE', $like);
                }
            })
            ->limit(40)
            ->get(['name', 'category', 'sku', 'retail_price', 'quantity']);

        if ($candidates->isEmpty()) {
            return [];
        }

        // Rank by how many of the query tokens appear in name/category.
        return $candidates
            ->map(function ($p) use ($tokens) {
                $hay = mb_strtolower(($p->name ?? '') . ' ' . ($p->category ?? '') . ' ' . ($p->sku ?? ''));
                $score = 0;
                foreach ($tokens as $t) {
                    if (mb_strpos($hay, $t) !== false) { $score++; }
                }
                return ['p' => $p, 'score' => $score];
            })
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn ($x) => [
                'name'     => (string) $x['p']->name,
                'price'    => (float) $x['p']->retail_price,
                'quantity' => (int) $x['p']->quantity,
                'category' => (string) $x['p']->category,
            ])
            ->values()
            ->all();
    }

    /** Significant lowercase tokens from the query (drop stop-words/short words). */
    private function tokens(string $query): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = [
            'the','and','for','you','have','this','that','with','how','much','price','cost',
            'stock','available','product','products','want','need','can','get','your','does',
            'about','what','which','there','any','sell','selling','buy','looking','please',
        ];
        $out = [];
        foreach ($words as $w) {
            if (mb_strlen($w) >= 3 && ! in_array($w, $stop, true)) {
                $out[$w] = true;
            }
        }
        return array_slice(array_keys($out), 0, 8);
    }
}
