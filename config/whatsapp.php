<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Official WhatsApp Business API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for official WhatsApp Business API integration
    | using Meta's Embedded Signup flow and Business Solution Providers (BSP)
    |
    */

    'enabled' => env('WHATSAPP_OFFICIAL_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Meta App Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Meta for Developers app
    |
    */
    'meta' => [
        'app_id' => env('META_APP_ID', ''),
        'app_secret' => env('META_APP_SECRET', ''),
        'config_id' => env('META_CONFIG_ID', ''),
        'business_id' => env('META_BUSINESS_ID', ''),
        'version' => env('META_API_VERSION', 'v18.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Solution Providers (BSP) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for different BSP providers
    |
    */
    'providers' => [
        '360dialog' => [
            'name' => '360Dialog',
            'partner_id' => env('BSP_360DIALOG_PARTNER_ID', ''),
            'solution_id' => env('BSP_360DIALOG_SOLUTION_ID', ''),
            'api_base_url' => env('BSP_360DIALOG_API_URL', 'https://waba.360dialog.io'),
            'webhook_url' => env('BSP_360DIALOG_WEBHOOK_URL', ''),
            'token_endpoint' => '/v1/partners/{partner_id}/waba_accounts/{waba_id}/access_token',
            'send_message_endpoint' => '/v1/messages',
            'media_endpoint' => '/v1/media',
            'phone_numbers_endpoint' => '/v1/waba_accounts/{waba_id}/phone_numbers',
            'supported_features' => [
                'text_messages',
                'media_messages',
                'interactive_messages',
                'templates',
                'webhooks'
            ]
        ],

        'twilio' => [
            'name' => 'Twilio',
            'partner_id' => env('BSP_TWILIO_PARTNER_ID', ''),
            'solution_id' => env('BSP_TWILIO_SOLUTION_ID', ''),
            'api_base_url' => env('BSP_TWILIO_API_URL', 'https://api.twilio.com'),
            'webhook_url' => env('BSP_TWILIO_WEBHOOK_URL', ''),
            'token_endpoint' => '/2010-04-01/Accounts/{account_sid}/whatsapp/access_token',
            'send_message_endpoint' => '/2010-04-01/Accounts/{account_sid}/Messages.json',
            'media_endpoint' => '/2010-04-01/Accounts/{account_sid}/Media.json',
            'phone_numbers_endpoint' => '/2010-04-01/Accounts/{account_sid}/whatsapp/phone_numbers',
            'supported_features' => [
                'text_messages',
                'media_messages',
                'templates'
            ]
        ],

        'facebook' => [
            'name' => 'Facebook (Meta)',
            'partner_id' => env('BSP_FACEBOOK_PARTNER_ID', ''),
            'solution_id' => env('BSP_FACEBOOK_SOLUTION_ID', ''),
            'api_base_url' => 'https://graph.facebook.com',
            'webhook_url' => env('BSP_FACEBOOK_WEBHOOK_URL', ''),
            'token_endpoint' => '/v18.0/{phone_number_id}/access_token',
            'send_message_endpoint' => '/v18.0/{phone_number_id}/messages',
            'media_endpoint' => '/v18.0/{phone_number_id}/media',
            'phone_numbers_endpoint' => '/v18.0/{waba_id}/phone_numbers',
            'supported_features' => [
                'text_messages',
                'media_messages',
                'interactive_messages',
                'templates',
                'webhooks',
                'analytics'
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | The default BSP provider to use for new integrations
    |
    */
    'default_provider' => env('WHATSAPP_DEFAULT_PROVIDER', '360dialog'),

    /*
    |--------------------------------------------------------------------------
    | Embedded Signup Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Meta's Embedded Signup flow
    |
    */
    'embedded_signup' => [
        'redirect_url' => env('WHATSAPP_EMBEDDED_SIGNUP_REDIRECT_URL', env('APP_URL') . '/whatsapp/official/callback'),
        'success_url' => env('WHATSAPP_EMBEDDED_SIGNUP_SUCCESS_URL', env('APP_URL') . '/whatsapp/integration-success'),
        'error_url' => env('WHATSAPP_EMBEDDED_SIGNUP_ERROR_URL', env('APP_URL') . '/whatsapp/integration-error'),
        'permissions' => [
            'whatsapp_business_management',
            'whatsapp_business_messaging',
            'business_management'
        ],
        'extras' => [
            'setup' => [
                'phone_number' => true,
                'business_info' => true,
                'tos_acceptance' => true
            ]
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for receiving webhooks from Meta/BSP
    |
    */
    'webhooks' => [
        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'safarichat_webhook_' . env('APP_KEY', 'default')),
        'secret' => env('WHATSAPP_WEBHOOK_SECRET', ''),
        'endpoints' => [
            'messages' => '/api/whatsapp/official/webhook/messages',
            'status' => '/api/whatsapp/official/webhook/status',
            'errors' => '/api/whatsapp/official/webhook/errors'
        ],
        'retry_attempts' => 3,
        'retry_delay' => 5, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Management
    |--------------------------------------------------------------------------
    |
    | Configuration for managing access tokens
    |
    */
    'tokens' => [
        'refresh_buffer' => 60, // minutes before expiration to refresh
        'max_retry_attempts' => 3,
        'cache_duration' => 3600, // seconds to cache valid tokens
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting configuration for API calls
    |
    */
    'rate_limits' => [
        'messages_per_second' => 10,
        'messages_per_minute' => 600,
        'messages_per_hour' => 36000,
        'burst_limit' => 50
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Templates
    |--------------------------------------------------------------------------
    |
    | Configuration for message templates
    |
    */
    'templates' => [
        'approval_required' => true,
        'default_language' => 'en_US',
        'supported_languages' => [
            'en_US' => 'English (US)',
            'en_GB' => 'English (UK)',
            'sw' => 'Swahili',
            'es' => 'Spanish',
            'fr' => 'French'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configuration for error handling and logging
    |
    */
    'error_handling' => [
        'log_level' => env('WHATSAPP_LOG_LEVEL', 'info'),
        'max_error_logs' => 50,
        'alert_on_errors' => [
            'token_expired',
            'phone_number_suspended',
            'rate_limit_exceeded',
            'webhook_verification_failed'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook settings for receiving real-time notifications
    | from WhatsApp Business API about message status and incoming messages.
    |
    */
    'webhook' => [
        'enabled' => env('WHATSAPP_WEBHOOK_ENABLED', true),
        'url' => env('WHATSAPP_WEBHOOK_URL', env('APP_URL') . '/api/whatsapp/webhook'),
        'verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'whatsapp_webhook_token_' . env('APP_KEY', 'random_token')),
        'signature_verification' => env('WHATSAPP_WEBHOOK_VERIFY_SIGNATURE', true),
        'fields' => [
            'messages',
            'message_template_status_update',
            'phone_number_name_update',
            'phone_number_quality_update',
            'account_alerts'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Configure rate limiting for API requests to avoid hitting WhatsApp
    | Business API limits and ensure optimal performance.
    |
    */
    'rate_limiting' => [
        'enabled' => env('WHATSAPP_RATE_LIMITING_ENABLED', true),
        'messages_per_second' => env('WHATSAPP_MESSAGES_PER_SECOND', 20),
        'burst_limit' => env('WHATSAPP_BURST_LIMIT', 100),
        'cooldown_period' => env('WHATSAPP_COOLDOWN_PERIOD', 60), // seconds
        'retry_after' => env('WHATSAPP_RETRY_AFTER', 30) // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for testing and development
    |
    */
    'testing' => [
        'enabled' => env('WHATSAPP_TESTING_ENABLED', env('APP_ENV') !== 'production'),
        'test_phone_numbers' => [
            '+15550100001',  // Meta test number
            '+15550100002',  // Meta test number
        ],
        'sandbox_mode' => env('WHATSAPP_SANDBOX_MODE', false),
        'mock_responses' => env('WHATSAPP_MOCK_RESPONSES', false)
    ]
];
