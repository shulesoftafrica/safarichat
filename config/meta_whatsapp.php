<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Business API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for Meta's Official WhatsApp Business API.
    | It is used alongside WaSender for reliable message delivery.
    |
    | Priority: Meta WhatsApp is used first for critical messages (OTP, payments).
    | Fallback: WaSender is used if Meta fails or for bulk/marketing messages.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | API Credentials
    |--------------------------------------------------------------------------
    */

    'phone_number_id' => env('META_WHATSAPP_PHONE_NUMBER_ID', '1083367458184137'),
    
    'business_account_id' => env('META_WHATSAPP_BUSINESS_ACCOUNT_ID', '981178058418111'),
    
    'access_token' => env('META_WHATSAPP_ACCESS_TOKEN', ''),
    
    'api_version' => env('META_WHATSAPP_API_VERSION', 'v24.0'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoint
    |--------------------------------------------------------------------------
    */

    'base_url' => env('META_WHATSAPP_BASE_URL', 'https://graph.facebook.com'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Verify token is used to validate webhook subscription requests from Meta.
    | Set a random secure string here and use the same in Meta Business Manager.
    |
    */

    'webhook' => [
        'verify_token' => env('META_WHATSAPP_VERIFY_TOKEN', ''),
        'endpoint' => '/api/webhooks/meta-whatsapp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Settings
    |--------------------------------------------------------------------------
    */

    'settings' => [
        // Maximum retry attempts for failed messages
        'max_retries' => 3,
        
        // Timeout for API requests (seconds)
        'timeout' => 30,
        
        // Enable automatic fallback to WaSender on failure
        'enable_fallback' => true,
        
        // Log all API requests and responses
        'log_requests' => true,
        
        // Log file location (relative to storage/logs)
        'log_file' => 'meta_whatsapp.log',
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Types Priority
    |--------------------------------------------------------------------------
    |
    | Define which message types should use Meta WhatsApp as primary channel.
    | Types not listed here will default to WaSender.
    |
    */

    'message_type_priority' => [
        'otp_verification' => 'meta',      // Always use Meta for OTP
        'payment_reminder' => 'meta',       // Payment reminders via Meta
        'welcome_message' => 'meta',        // Welcome messages via Meta
        'system_notification' => 'meta',    // System notifications via Meta
        'password_reset' => 'meta',         // Password reset via Meta
        'subscription_alert' => 'meta',     // Subscription alerts via Meta
        
        // These can use either based on availability
        'marketing' => 'wasender',          // Marketing messages via WaSender
        'bulk_message' => 'wasender',       // Bulk messages via WaSender
        'ai_agent_reply' => 'wasender',     // AI agent replies via WaSender
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Names
    |--------------------------------------------------------------------------
    |
    | Pre-approved template names in Meta Business Manager.
    | These must be created and approved before use.
    |
    */

    'templates' => [
        'otp' => [
            'name' => 'otp',
            'language' => 'en',
            'parameters' => ['otp_code'],
        ],
        
        'payment_reminder' => [
            'name' => 'payment_reminder',
            'language' => 'en',
            'parameters' => ['customer_name', 'amount', 'due_date'],
        ],
        
        'welcome' => [
            'name' => 'welcome_message',
            'language' => 'en',
            'parameters' => ['customer_name'],
        ],
        
        'password_reset' => [
            'name' => 'password_reset',
            'language' => 'en',
            'parameters' => ['reset_link', 'expires_at'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Meta WhatsApp has rate limits. Configure them here to avoid hitting limits.
    | Default limits (may vary based on your account tier):
    | - 1000 messages per second (Business tier)
    | - 80 messages per second (Standard tier)
    |
    */

    'rate_limits' => [
        'messages_per_second' => env('META_WHATSAPP_RATE_LIMIT', 80),
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Define error codes that should trigger fallback to WaSender
    |
    */

    'fallback_error_codes' => [
        100,  // Invalid parameter
        131000, // Account has been locked
        131016, // Access token has expired
        131026, // Message undeliverable
        131047, // Re-engagement message
        131051, // Unsupported message type
        133016, // Rate limit hit
        // Add more error codes as needed
    ],

    /*
    |--------------------------------------------------------------------------
    | Testing
    |--------------------------------------------------------------------------
    |
    | Test mode configuration. When enabled, uses test phone numbers.
    |
    */

    'testing' => [
        'enabled' => env('META_WHATSAPP_TEST_MODE', false),
        'test_phone_numbers' => [
            '+15550100',
            '+15550101',
            '+15550102',
        ],
    ],

];
