<?php

/*
|--------------------------------------------------------------------------
| Sales Offer Rotation (Next-Best-Offer engine)
|--------------------------------------------------------------------------
|
| Controls the angle-rotation layer that lets the AI pitch DIFFERENT modules /
| offers to a prospect over successive touches instead of repeating the same
| pitch. Every active `products` row for a business is treated as a pitchable
| "offer" (a ShuleSoft module — Exam, Fee Collection, UCN, Payroll, HR, etc.).
|
| The whole feature is OFF by default. When disabled, all outreach services
| behave exactly as before (single primary product, template ladder).
|
| See: resources/documentation/SALES_EFFICIENCY_ANALYSIS_AND_PLAN.md (Part C1)
|
*/

return [
    // Master switch. When false the NextBestOfferService returns null and every
    // caller falls back to its original behavior. Flip per-env to pilot safely.
    'enabled' => env('SALES_OFFER_ROTATION', false),

    // How many times a single offer/angle may be pitched to a lead before the
    // engine rotates to the next offer (unless the lead engaged with it).
    'max_touches_per_offer' => (int) env('SALES_ROTATION_MAX_TOUCHES_PER_OFFER', 2),

    // Only rotate among products that look genuinely "pitchable". A product is
    // considered pitchable when it satisfies ANY of these. If a business has no
    // pitchable products, the engine falls back to all active products so it
    // never returns empty for a tenant that hasn't filled campaign fields.
    'pitchable' => [
        'require_any_of' => [
            'is_active_campaign',   // flagged as the current launch/campaign
            'campaign_hook_text',   // has an authored hook
            'ai_description',       // has AI sales copy
            'is_service',           // services are pitched, not stocked
        ],
        'fallback_to_all_active' => true,
    ],

    // Relative weights used to rank the next untried offer. Higher wins.
    // These are deliberately simple (explainable) — a learning/bandit ranker
    // can replace this later (Plan Part C4) without changing callers.
    'ranking' => [
        'active_campaign'   => 100,  // the "launch of the day" always leads
        'upsell_next'       => 60,   // next link in the previous offer's upsell chain
        'industry_match'    => 40,   // target_industry matches the lead's industry
        'conversion_rate'   => 30,   // scaled by the offer's historical conversion %
        'has_campaign_data' => 15,   // authored hook + pain point present
        'freshness'         => 10,   // newer offers get a small exploration bonus
    ],

    // When every offer has been exhausted (pitched max touches, no engagement),
    // what should the engine signal? 'null' = caller keeps its own fallback.
    // 'stop' = caller should stop pitching and let nurture/win-back take over.
    'on_exhausted' => env('SALES_ROTATION_ON_EXHAUSTED', 'null'),

    // Guardrail: never rotate/pitch when the lead already engaged an offer and
    // is actively in conversation. Engagement is recorded on inbound replies.
    'pause_rotation_while_engaged' => true,
];
