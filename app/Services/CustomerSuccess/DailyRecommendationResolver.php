<?php

namespace App\Services\CustomerSuccess;

use App\Models\CsDailySnapshot;
use App\Models\Lead;

/**
 * Picks the single most relevant daily recommendation for a business.
 *
 * Rules run in priority order — the first matching condition wins.
 * Conditions and copy are sourced from csdesign.md §3.3.1.
 */
class DailyRecommendationResolver
{
    /**
     * @param  CsDailySnapshot      $today    Today's freshly-built snapshot
     * @param  CsDailySnapshot|null $yesterday Yesterday's snapshot (null = first run)
     * @param  string               $locale   User locale for English / Swahili output
     * @return string                The recommendation paragraph (plain text, may include WhatsApp markup)
     */
    public function resolve(CsDailySnapshot $today, ?CsDailySnapshot $yesterday, string $locale = 'en'): string
    {
        return $locale === 'sw'
            ? $this->resolveSw($today, $yesterday)
            : $this->resolveEn($today, $yesterday);
    }

    // ---------------------------------------------------------------------------
    // English recommendations
    // ---------------------------------------------------------------------------

    private function resolveEn(CsDailySnapshot $today, ?CsDailySnapshot $yesterday): string
    {
        $ai = $today->total_conversations;

        // Priority 1: Zero new conversations
        if ($today->new_prospects === 0) {
            return "No new conversations today. Share your WhatsApp number on one social post tonight — even a single story can generate 5–10 new leads.";
        }

        // Priority 2: High churn today
        if ($today->churned > 2) {
            return "You had *{$today->churned}* leads go cold today. Consider having your AI follow up with them — go to Leads → Churned and trigger a re-engagement sequence.";
        }

        // Priority 3: Conversions today — celebrate and nudge referrals
        if ($today->converted > 0) {
            $n = $today->converted;
            return "Great — *{$n}* sale" . ($n === 1 ? '' : 's') . " closed today! Ask your buyers for a quick referral: one happy customer can bring 3 more.";
        }

        // Priority 4: Many warm leads, no action
        if ($today->active_leads > 50) {
            return "You have *{$today->active_leads}* warm leads waiting. Your AI will continue following up — make sure your product info is up to date so responses are accurate.";
        }

        // Default
        return "Your AI worked *{$ai}* conversation" . ($ai === 1 ? '' : 's') . " today. Keep your WhatsApp session connected overnight so no leads are missed.";
    }

    // ---------------------------------------------------------------------------
    // Swahili recommendations
    // ---------------------------------------------------------------------------

    private function resolveSw(CsDailySnapshot $today, ?CsDailySnapshot $yesterday): string
    {
        $ai = $today->total_conversations;

        if ($today->new_prospects === 0) {
            return "Hakuna mazungumzo mapya leo. Shiriki nambari yako ya WhatsApp kwenye chapisho moja la kijamii usiku huu — hata hadithi moja inaweza kuleta wateja 5–10 wapya.";
        }

        if ($today->churned > 2) {
            return "Wateja *{$today->churned}* waliondoka leo. Fikiria kuwa na AI yako ikifuatilia — nenda Wateja → Walioondoka na uanzishe mfuatano wa kuvutia tena.";
        }

        if ($today->converted > 0) {
            $n = $today->converted;
            return "Vizuri — mauzo *{$n}* yamefungwa leo! Uliza wanunuzi wako kwa rufaa ya haraka: mteja mmoja mwenye furaha anaweza kuleta watatu zaidi.";
        }

        if ($today->active_leads > 50) {
            return "Una wateja wanaongoja *{$today->active_leads}* wa joto. AI yako itaendelea kufuatilia — hakikisha taarifa zako za bidhaa zimesasishwa ili majibu yawe sahihi.";
        }

        return "AI yako ilifanya mazungumzo *{$ai}* leo. Weka kipindi chako cha WhatsApp kikiwa kimeunganishwa usiku kucha ili hakuna wateja wanaokosekana.";
    }
}
