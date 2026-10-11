<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Customer-success reports
    |--------------------------------------------------------------------------
    |
    | Daily reports are sent from each business's OWN connected WhatsApp number to the manager number set on
    | its AI agent (see App\Services\OwnerReportDeliveryService). The shared platform number only sends
    | low-volume alerts such as "your WhatsApp is disconnected".
    |
    */

    // Hours between "your WhatsApp is disconnected" alerts for the same business (an alert per episode, not per day).
    'disconnected_alert_cooldown_hours' => (int) env('CS_DISCONNECTED_ALERT_COOLDOWN_HOURS', 72),

    // The evening report jobs are spread over this many minutes when a real queue worker is used.
    'daily_summary_spread_minutes' => (int) env('CS_DAILY_SUMMARY_SPREAD_MINUTES', 60),
];
