<?php

return [
    'product_code' => 'safarichat',
    'plans' => [
        'trial' => [
            'price' => 0,
            'duration_days' => 3,
            'limits' => [
                'max_contacts' => 10,
                'max_products' => 1,
                'max_outgoing_messages' => 50,
                'whatsapp_channels' => 1,
                'customer_followups' => false,
                'customer_categorization' => false,
                'booking_calendars' => false,
                'sales_reports' => false,
                'ai_credits' => 1000
            ],
            'credits_rollover' => false
        ],
        'starter' => [
            'price' => 69000,
            'currency' => 'TZS',
            'billing_cycle' => 'monthly',
            'limits' => [
                'max_contacts' => 50,
                'max_products' => 5,
                'whatsapp_channels' => 1,
                'customer_followups' => false,
                'customer_categorization' => false,
                'booking_calendars' => false,
                'sales_reports' => false,
                'ai_credits' => 69000,
                'unlimited_messages' => true
            ],
            'credits_rollover' => true
        ],
        'pro' => [
            'price' => 149000,
            'currency' => 'TZS',
            'billing_cycle' => 'monthly',
            'limits' => [
                'max_contacts' => 150,
                'max_products' => 50,
                'whatsapp_channels' => 3,
                'customer_followups' => true,
                'customer_categorization' => true,
                'booking_calendars' => false,
                'sales_reports' => true,
                'ai_credits' => 149000,
                'unlimited_messages' => true
            ],
            'credits_rollover' => true
        ],
        'premium' => [
            'price' => 299000,
            'currency' => 'TZS',
            'billing_cycle' => 'monthly',
            'limits' => [
                'max_contacts' => 400,
                'max_products' => 200,
                'whatsapp_channels' => 7,
                'customer_followups' => true,
                'customer_categorization' => true,
                'booking_calendars' => true,
                'sales_reports' => true,
                'ai_credits' => 299000,
                'unlimited_messages' => true
            ],
            'credits_rollover' => true
        ]
    ],
    'token_pricing' => [
        'tokens_per_credit' => 3.846,
        'cost_per_token_input' => 0.0015,
        'cost_per_token_output' => 0.002
    ]
];