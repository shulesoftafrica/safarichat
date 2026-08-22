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
];
