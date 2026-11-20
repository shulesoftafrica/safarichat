<?php

/*
|--------------------------------------------------------------------------
| Automated AI outreach / follow-up safety
|--------------------------------------------------------------------------
|
| Controls the automated, AI-initiated outreach and follow-up jobs (smart
| follow-up, convert-unengaged, no-reply chase, win-back, daily outreach).
|
| These cold-message contacts who never replied, which reads as spam and is the
| main trigger for WhatsApp/WaSender account restrictions.
|
*/

return [
    // Master switch. When false, ALL automated outreach/follow-up jobs no-op.
    // (Inbound replies are still answered — this only stops AI-INITIATED messages.)
    'enabled' => filter_var(env('AI_OUTREACH_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    // Only send automated messages to leads that have replied to us at least once.
    // This disables cold first-touch and repeated "checking in" to silent contacts.
    'reply_required' => filter_var(env('AI_OUTREACH_REPLY_REQUIRED', true), FILTER_VALIDATE_BOOLEAN),

    // Daily cap on smart follow-ups PER ACCOUNT (leads.user_id), counted from leads stamped follow_up_sent_at today.
    // 0 = no cap. Protects a WhatsApp number from a burst when a large account (e.g. the ShuleSoft number with
    // 1,000+ eligible leads) is switched on. Env: AI_FOLLOWUP_DAILY_CAP.
    'smart_followup_daily_cap' => (int) env('AI_FOLLOWUP_DAILY_CAP', 0),

    // Smart follow-up targeting (see App\Services\FollowupPriorityService).
    // A contact is "fresh" when it replied within this many days; older contacts are only followed up when their
    // effective lead score reaches smart_followup_old_min_score (asked for a demo / was close to buying and went quiet).
    'smart_followup_freshness_days' => (int) env('AI_FOLLOWUP_FRESHNESS_DAYS', 30),
    'smart_followup_old_min_score' => (int) env('AI_FOLLOWUP_OLD_MIN_SCORE', 60),
];
