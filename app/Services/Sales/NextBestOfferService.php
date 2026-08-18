<?php

namespace App\Services\Sales;

use App\Models\Lead;
use App\Models\LeadOfferProgress;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Next-Best-Offer engine (angle rotation).
 *
 * Given a lead, decides which module/offer to pitch NEXT so the AI stops
 * repeating the same pitch. Reads three inputs:
 *   1. Catalog  — the business's active, pitchable `products` (= modules).
 *   2. Ledger   — lead_offer_progress rows (what was already tried + outcome).
 *   3. Policy   — config/sales_rotation.php (weights, max touches, upsell chain).
 *
 * Entirely inert when config('sales_rotation.enabled') is false: resolveForLead()
 * returns null and every caller keeps its original behavior.
 */
class NextBestOfferService
{
    public function isEnabled(): bool
    {
        return (bool) config('sales_rotation.enabled', false);
    }

    /**
     * Pick the next offer (Product) to pitch to this lead, or null when the
     * feature is off / no eligible offer exists.
     */
    public function resolveForLead(Lead $lead): ?Product
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $ledger = $this->ledgerFor($lead);           // keyed by product_id
            $maxTouches = (int) config('sales_rotation.max_touches_per_offer', 2);

            // 1. If the lead already engaged an offer, keep the conversation on
            //    that module rather than rotating away from a working angle.
            if (config('sales_rotation.pause_rotation_while_engaged', true)) {
                $engaged = $ledger->firstWhere('status', LeadOfferProgress::STATUS_ENGAGED);
                if ($engaged && $engaged->product && $this->isActive($engaged->product)) {
                    return $engaged->product;
                }
            }

            // 2. An offer that is mid-flight (pitched, still has touches left and
            //    no engagement) keeps getting the remainder of its touch budget.
            $inFlight = $ledger->first(function (LeadOfferProgress $row) use ($maxTouches) {
                return $row->status === LeadOfferProgress::STATUS_PITCHED
                    && (int) $row->touch_count < $maxTouches;
            });
            if ($inFlight && $inFlight->product && $this->isActive($inFlight->product)) {
                return $inFlight->product;
            }

            // 3. Retire any pitched-but-spent offers so they leave the rotation.
            $ledger->each(function (LeadOfferProgress $row) use ($maxTouches) {
                if ($row->status === LeadOfferProgress::STATUS_PITCHED
                    && (int) $row->touch_count >= $maxTouches) {
                    $row->markExhausted();
                }
            });

            // 4. Rank the untried offers and pick the best.
            $candidates = $this->pitchableCatalog($lead);
            $triedProductIds = $ledger
                ->filter(fn (LeadOfferProgress $r) => in_array($r->status, LeadOfferProgress::TERMINAL_STATUSES, true) || $r->status === LeadOfferProgress::STATUS_PITCHED)
                ->pluck('product_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $untried = $candidates->reject(fn (Product $p) => in_array((int) $p->id, $triedProductIds, true));

            if ($untried->isEmpty()) {
                return null; // cadence exhausted — caller decides what to do
            }

            $lastPitchedProduct = $this->lastPitchedProduct($lead, $ledger);
            $best = $this->rankAndPick($untried, $lead, $lastPitchedProduct);

            return $best;
        } catch (\Throwable $e) {
            // Rotation must NEVER break outreach — fail open to legacy behavior.
            Log::warning('NextBestOfferService::resolveForLead failed, falling back', [
                'lead_id' => $lead->id ?? null,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Record that an offer was pitched to a lead on a channel.
     * Upserts the ledger row and increments its touch counter.
     */
    public function registerPitch(Lead $lead, Product $offer, ?string $channel = null, ?string $outcome = null): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $row = LeadOfferProgress::firstOrNew([
                'lead_id'    => $lead->id,
                'product_id' => $offer->id,
            ]);
            $row->business_id = $row->business_id ?? $lead->business_id;
            $row->registerPitch($channel, $outcome);
        } catch (\Throwable $e) {
            Log::warning('NextBestOfferService::registerPitch failed', [
                'lead_id'    => $lead->id ?? null,
                'product_id' => $offer->id ?? null,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * The lead replied — mark the most-recently pitched in-flight offer as engaged
     * so the engine stops rotating and keeps the conversation on that module.
     */
    public function recordEngagement(Lead $lead): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $row = LeadOfferProgress::forLead($lead->id)
                ->inFlight()
                ->orderByDesc('last_pitched_at')
                ->first();

            if ($row) {
                $row->markEngaged();
            }
        } catch (\Throwable $e) {
            Log::warning('NextBestOfferService::recordEngagement failed', [
                'lead_id' => $lead->id ?? null,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function ledgerFor(Lead $lead): Collection
    {
        return LeadOfferProgress::forLead($lead->id)
            ->with('product')
            ->get();
    }

    /**
     * Active products for the lead's business/user, filtered to the ones that
     * look genuinely pitchable. Falls back to all active products when the
     * business hasn't authored any campaign copy yet.
     */
    private function pitchableCatalog(Lead $lead): Collection
    {
        $active = Product::query()
            ->where('status', 'active')
            ->where(function ($q) use ($lead) {
                $q->where('business_id', $lead->business_id)
                    ->orWhere('user_id', $lead->user_id);
            })
            ->get();

        if ($active->isEmpty()) {
            return collect();
        }

        $requireAnyOf = (array) config('sales_rotation.pitchable.require_any_of', []);
        $pitchable = $active->filter(fn (Product $p) => $this->isPitchable($p, $requireAnyOf))->values();

        if ($pitchable->isEmpty() && config('sales_rotation.pitchable.fallback_to_all_active', true)) {
            return $active->values();
        }

        return $pitchable;
    }

    private function isPitchable(Product $p, array $requireAnyOf): bool
    {
        foreach ($requireAnyOf as $criterion) {
            switch ($criterion) {
                case 'is_active_campaign':
                    if (!empty($p->is_active_campaign)) {
                        return true;
                    }
                    break;
                case 'campaign_hook_text':
                    if (!empty($p->campaign_hook_text)) {
                        return true;
                    }
                    break;
                case 'ai_description':
                    if (!empty($p->ai_description)) {
                        return true;
                    }
                    break;
                case 'is_service':
                    if (method_exists($p, 'isService') && $p->isService()) {
                        return true;
                    }
                    break;
            }
        }

        return false;
    }

    private function isActive(Product $p): bool
    {
        return ($p->status ?? null) === 'active';
    }

    private function lastPitchedProduct(Lead $lead, Collection $ledger): ?Product
    {
        $row = $ledger
            ->whereNotNull('last_pitched_at')
            ->sortByDesc('last_pitched_at')
            ->first();

        return $row?->product;
    }

    private function rankAndPick(Collection $untried, Lead $lead, ?Product $lastPitched): ?Product
    {
        $w = (array) config('sales_rotation.ranking', []);
        $upsellNextIds = $lastPitched && is_array($lastPitched->upsell_products)
            ? array_map('intval', $lastPitched->upsell_products)
            : [];

        $leadIndustry = strtolower(trim((string) ($lead->industry ?? '')));

        $scored = $untried->map(function (Product $p) use ($w, $upsellNextIds, $leadIndustry) {
            $score = 0.0;

            if (!empty($p->is_active_campaign)) {
                $score += (float) ($w['active_campaign'] ?? 0);
            }

            if (in_array((int) $p->id, $upsellNextIds, true)) {
                $score += (float) ($w['upsell_next'] ?? 0);
            }

            $target = strtolower(trim((string) ($p->target_industry ?? '')));
            if ($leadIndustry !== '' && $target !== '' && str_contains($target, $leadIndustry)) {
                $score += (float) ($w['industry_match'] ?? 0);
            }

            // Historical conversion (0..100) scaled into the configured weight.
            $conversion = 0.0;
            try {
                $conversion = (float) $p->getConversionRate();
            } catch (\Throwable $e) {
                $conversion = 0.0;
            }
            $score += (float) ($w['conversion_rate'] ?? 0) * ($conversion / 100);

            if (method_exists($p, 'hasCompleteCampaignData') && $p->hasCompleteCampaignData()) {
                $score += (float) ($w['has_campaign_data'] ?? 0);
            }

            if ($p->created_at && $p->created_at->gt(now()->subDays(30))) {
                $score += (float) ($w['freshness'] ?? 0);
            }

            return ['product' => $p, 'score' => $score];
        });

        $sorted = $scored->sort(function ($a, $b) {
            if ($a['score'] === $b['score']) {
                return ((int) $a['product']->id) <=> ((int) $b['product']->id);
            }
            return $a['score'] < $b['score'] ? 1 : -1;
        })->values();

        return $sorted->first()['product'] ?? null;
    }
}
