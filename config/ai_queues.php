<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Queue Configuration for AI Sales Agents
    |--------------------------------------------------------------------------
    |
    | Custom queue configuration for AI-related jobs to ensure proper
    | processing priority and resource allocation.
    |
    */

    'ai_queues' => [
        // High priority queue for instant processing fallback
        'ai_priority' => [
            'connection' => env('AI_PRIORITY_QUEUE_CONNECTION', 'redis'),
            'queue' => 'ai_priority',
            'retry_after' => 30,
            'timeout' => 60,
            'max_tries' => 3,
        ],

        // Standard queue for regular AI processing
        'ai_standard' => [
            'connection' => env('AI_STANDARD_QUEUE_CONNECTION', 'redis'),
            'queue' => 'ai_standard',
            'retry_after' => 90,
            'timeout' => 120,
            'max_tries' => 3,
        ],

        // Low priority queue for maintenance tasks
        'ai_maintenance' => [
            'connection' => env('AI_MAINTENANCE_QUEUE_CONNECTION', 'database'),
            'queue' => 'ai_maintenance',
            'retry_after' => 300,
            'timeout' => 600,
            'max_tries' => 2,
        ],
    ],

    'job_routing' => [
        'ProcessAiMessage' => [
            'high_priority_phones' => array_filter(explode(',', env('AI_PRIORITY_PHONES', ''))),
            'returning_customer_priority' => env('AI_RETURNING_CUSTOMER_PRIORITY', true),
            'large_order_priority' => env('AI_LARGE_ORDER_PRIORITY', true),
            'priority_queue' => 'ai_priority',
            'standard_queue' => 'ai_standard',
        ],

        'GenerateProductDescriptions' => [
            'queue' => 'ai_maintenance',
            'batch_size' => 10,
            'delay_between_batches' => 60, // seconds
        ],

        'UpdateLeadScores' => [
            'queue' => 'ai_maintenance',
            'batch_size' => 100,
            'schedule' => 'daily',
        ],

        'CleanupOldConversations' => [
            'queue' => 'ai_maintenance',
            'schedule' => 'weekly',
        ],
    ],

    'worker_configuration' => [
        'ai_priority' => [
            'workers' => env('AI_PRIORITY_WORKERS', 3),
            'memory' => env('AI_PRIORITY_MEMORY', 512),
            'timeout' => env('AI_PRIORITY_TIMEOUT', 60),
            'sleep' => env('AI_PRIORITY_SLEEP', 1),
            'max_jobs' => env('AI_PRIORITY_MAX_JOBS', 1000),
            'max_time' => env('AI_PRIORITY_MAX_TIME', 3600),
        ],

        'ai_standard' => [
            'workers' => env('AI_STANDARD_WORKERS', 5),
            'memory' => env('AI_STANDARD_MEMORY', 512),
            'timeout' => env('AI_STANDARD_TIMEOUT', 120),
            'sleep' => env('AI_STANDARD_SLEEP', 3),
            'max_jobs' => env('AI_STANDARD_MAX_JOBS', 1000),
            'max_time' => env('AI_STANDARD_MAX_TIME', 3600),
        ],

        'ai_maintenance' => [
            'workers' => env('AI_MAINTENANCE_WORKERS', 1),
            'memory' => env('AI_MAINTENANCE_MEMORY', 256),
            'timeout' => env('AI_MAINTENANCE_TIMEOUT', 600),
            'sleep' => env('AI_MAINTENANCE_SLEEP', 10),
            'max_jobs' => env('AI_MAINTENANCE_MAX_JOBS', 50),
            'max_time' => env('AI_MAINTENANCE_MAX_TIME', 7200),
        ],
    ],

    'monitoring' => [
        'failed_job_alerts' => env('AI_QUEUE_FAILED_ALERTS', true),
        'queue_size_alerts' => env('AI_QUEUE_SIZE_ALERTS', true),
        'max_queue_size' => env('AI_MAX_QUEUE_SIZE', 1000),
        'processing_time_alerts' => env('AI_PROCESSING_TIME_ALERTS', true),
        'max_processing_time' => env('AI_MAX_PROCESSING_TIME', 300), // 5 minutes
        'alert_channels' => array_filter(explode(',', env('AI_ALERT_CHANNELS', 'log,email'))),
    ],

    'rate_limiting' => [
        'api_calls_per_minute' => env('AI_API_RATE_LIMIT', 60),
        'jobs_per_minute' => env('AI_JOBS_RATE_LIMIT', 100),
        'concurrent_api_calls' => env('AI_CONCURRENT_API_LIMIT', 10),
        'backoff_strategy' => env('AI_BACKOFF_STRATEGY', 'exponential'), // linear, exponential
        'backoff_base_delay' => env('AI_BACKOFF_BASE_DELAY', 60), // seconds
    ],

    'circuit_breaker' => [
        'enabled' => env('AI_CIRCUIT_BREAKER_ENABLED', true),
        'failure_threshold' => env('AI_CIRCUIT_BREAKER_THRESHOLD', 5),
        'recovery_timeout' => env('AI_CIRCUIT_BREAKER_TIMEOUT', 300), // 5 minutes
        'half_open_max_calls' => env('AI_CIRCUIT_BREAKER_HALF_OPEN', 3),
    ],
];