<?php

return [
    // Feature flag for phased rollout.
    'enabled' => env('MULTI_CHANNEL_ROUTING', false),

    // Phase-5 rollout controls.
    // mode=global enables for all users when enabled=true.
    // mode=allow_list enables only listed users/businesses.
    'rollout' => [
        'mode' => env('MULTI_CHANNEL_ROLLOUT_MODE', 'global'),
        'enabled_user_ids' => array_values(array_filter(array_map('intval', array_map('trim', explode(',', (string) env('MULTI_CHANNEL_ENABLED_USERS', '')))))),
        'enabled_business_ids' => array_values(array_filter(array_map('intval', array_map('trim', explode(',', (string) env('MULTI_CHANNEL_ENABLED_BUSINESSES', '')))))),
    ],

    // Unified outbound transport endpoint.
    'transport' => [
        'base_url' => env('MULTI_CHANNEL_TRANSPORT_URL', 'https://notifications.shulesoft.africa/'),
        'timeout_seconds' => (int) env('MULTI_CHANNEL_TRANSPORT_TIMEOUT', 15),
    ],

    // Canonical channel keys (must match DB/UI/payload values).
    'channels' => [
        'whatsapp',
        'email',
        'phone_sms',
        'bulk_sms',
    ],

    // Shared payload envelope contract.
    'payload' => [
        'required_fields' => [
            'schema_name',
            'channel',
            'to',
            'message',
            'provider',
            'priority',
        ],
        'priorities' => ['low', 'normal', 'high'],
        'channel_specific_required' => [
            'email' => ['subject'],
            'whatsapp' => [],
            'phone_sms' => [],
            'bulk_sms' => [],
        ],
    ],

    // Default fallback policy baseline for phase 1 implementation.
    'fallback' => [
        'enabled' => true,
        'max_cross_channel_attempts' => 2,
        'cooldown_minutes' => [
            'default' => 10,
            'whatsapp' => 5,
            'email' => 15,
            'phone_sms' => 5,
            'bulk_sms' => 20,
        ],
        'respect_opt_out' => true,
        'respect_do_not_contact' => true,
    ],

    // Phase-3 scoring defaults used by ChannelSelectionService.
    'selection' => [
        'cost_weight' => [
            'whatsapp' => 3,
            'email' => 2,
            'phone_sms' => 4,
            'bulk_sms' => 6,
        ],
    ],
];
