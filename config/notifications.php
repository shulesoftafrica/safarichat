<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | Unified Notification API Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file manages settings for the unified notification
    | service that handles WhatsApp message sending through the centralized API.
    |
    */

    'unified_api' => [
        'base_url' => env('UNIFIED_API_BASE_URL', 'https://notifications.shulesoft.africa/api'),
        'bearer_token' => env('UNIFIED_API_BEARER_TOKEN', 'LhpxNaEsEaaBW45SANVDlrsrorFRwOheKowfouKSHEAvWBibmowWYDNBqqDBBxn'),
        'timeout' => env('UNIFIED_API_TIMEOUT', 30), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Notification Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'provider' => 'unified_api',
        'channel' => 'whatsapp',
        'priority' => 'normal',
        'schema_name' => env('UNIFIED_API_DEFAULT_SCHEMA', 'safarichat_default'),
        'rate_limit' => env('NOTIFICATION_RATE_LIMIT', 60), // messages per minute
        'batch_size' => env('BULK_BATCH_SIZE', 50), // messages per batch
        'retry_attempts' => env('NOTIFICATION_RETRY_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Priority Levels
    |--------------------------------------------------------------------------
    */

    'priorities' => [
        'low' => [
            'queue_delay' => 60, // seconds
            'rate_limit' => 30,  // messages per minute
        ],
        'normal' => [
            'queue_delay' => 0,
            'rate_limit' => 60,
        ],
        'high' => [
            'queue_delay' => 0,
            'rate_limit' => 120,
        ],
        'urgent' => [
            'queue_delay' => 0,
            'rate_limit' => 200,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Types Configuration
    |--------------------------------------------------------------------------
    */

    'message_types' => [
        'text' => [
            'max_length' => 4096,
            'supports_formatting' => true,
        ],
        'image' => [
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_formats' => ['jpg', 'jpeg', 'png', 'gif'],
        ],
        'document' => [
            'max_size' => 10 * 1024 * 1024, // 10MB
            'allowed_formats' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'],
        ],
        'audio' => [
            'max_size' => 16 * 1024 * 1024, // 16MB
            'allowed_formats' => ['mp3', 'wav', 'm4a', 'ogg'],
        ],
        'video' => [
            'max_size' => 16 * 1024 * 1024, // 16MB
            'allowed_formats' => ['mp4', 'avi', 'mov', 'mkv'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */

    'webhooks' => [
        'enabled' => env('NOTIFICATION_WEBHOOKS_ENABLED', true),
        'verify_signature' => env('NOTIFICATION_WEBHOOK_VERIFY_SIGNATURE', true),
        'secret' => env('NOTIFICATION_WEBHOOK_SECRET'),
        'timeout' => env('NOTIFICATION_WEBHOOK_TIMEOUT', 10), // seconds
        'retry_attempts' => env('NOTIFICATION_WEBHOOK_RETRY_ATTEMPTS', 3),
        'events' => [
            'messages.received',
            'session.status',
            'messages.update',
            'delivery.status',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('NOTIFICATION_LOGGING_ENABLED', true),
        'level' => env('NOTIFICATION_LOG_LEVEL', 'info'),
        'channel' => env('NOTIFICATION_LOG_CHANNEL', 'daily'),
        'log_requests' => env('NOTIFICATION_LOG_REQUESTS', false),
        'log_responses' => env('NOTIFICATION_LOG_RESPONSES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */

    'rate_limiting' => [
        'enabled' => env('NOTIFICATION_RATE_LIMITING_ENABLED', true),
        'driver' => env('NOTIFICATION_RATE_LIMITER_DRIVER', 'redis'), // redis, database
        'key_prefix' => 'notification_rate_limit:',
        'per_user_limit' => env('NOTIFICATION_PER_USER_LIMIT', 1000), // per day
        'global_limit' => env('NOTIFICATION_GLOBAL_LIMIT', 10000), // per day
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'connection' => env('NOTIFICATION_QUEUE_CONNECTION', 'database'),
        'name' => env('NOTIFICATION_QUEUE_NAME', 'notifications'),
        'retry_after' => env('NOTIFICATION_QUEUE_RETRY_AFTER', 300), // seconds
        'max_exceptions' => env('NOTIFICATION_QUEUE_MAX_EXCEPTIONS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Name Resolution
    |--------------------------------------------------------------------------
    */

    'schema_resolution' => [
        'methods' => ['uuid', 'id', 'email'], // Order of methods to try
        'cache_duration' => env('SCHEMA_CACHE_DURATION', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | WaSender Session Management
    |--------------------------------------------------------------------------
    */

    'wasender' => [
        'session_timeout' => env('WASENDER_SESSION_TIMEOUT', 1800), // 30 minutes
        'qr_code_timeout' => env('WASENDER_QR_CODE_TIMEOUT', 300), // 5 minutes
        'connection_retry_attempts' => env('WASENDER_CONNECTION_RETRY_ATTEMPTS', 3),
        'default_webhook_events' => [
            'messages.received',
            'session.status',
            'messages.update'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'cache_enabled' => env('NOTIFICATION_CACHE_ENABLED', true),
        'cache_duration' => env('NOTIFICATION_CACHE_DURATION', 600), // 10 minutes
        'batch_processing' => env('NOTIFICATION_BATCH_PROCESSING', true),
        'async_processing' => env('NOTIFICATION_ASYNC_PROCESSING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    */

    'error_handling' => [
        'retry_failed_jobs' => env('NOTIFICATION_RETRY_FAILED_JOBS', true),
        'retry_delay' => env('NOTIFICATION_RETRY_DELAY', 60), // seconds
        'max_retry_attempts' => env('NOTIFICATION_MAX_RETRY_ATTEMPTS', 3),
        'alert_on_failure_rate' => env('NOTIFICATION_ALERT_FAILURE_RATE', 0.1), // 10%
    ],

];