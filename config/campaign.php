<?php

/*
|--------------------------------------------------------------------------
| Campaign sending safety (WaSender / unofficial WhatsApp)
|--------------------------------------------------------------------------
|
| Unofficial WhatsApp (WaSender) restricts/bans accounts that send bulk or
| cold automated messages. These settings space sends out and restrict them
| to warm contacts to reduce that risk.
|
*/

return [
    // Minimum seconds between two sends of the same campaign. Sends are staggered
    // via scheduled_send_at so WhatsApp sees a human-like drip, not a burst.
    'send_interval_seconds' => (int) env('CAMPAIGN_SEND_INTERVAL_SECONDS', 10),

    // Safety: only send a campaign to contacts who have replied to us before
    // (an existing conversation). Cold-messaging brand-new numbers is what most
    // often triggers a WhatsApp restriction. Set false to allow cold outreach.
    'reply_required' => filter_var(env('CAMPAIGN_REPLY_REQUIRED', true), FILTER_VALIDATE_BOOLEAN),
];
