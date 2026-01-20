<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Sales Agent Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for the AI Sales Agent system including
    | OpenAI integration, processing limits, and system behavior.
    |
    */

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'default_model' => env('OPENAI_MODEL', 'gpt-4o'),
        'fallback_model' => env('OPENAI_FALLBACK_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 1000),
        'temperature' => (float) env('OPENAI_TEMPERATURE', 0.7),
        'timeout' => (int) env('OPENAI_TIMEOUT', 30),
    ],

    'processing' => [
        'instant_timeout' => (int) env('AI_INSTANT_TIMEOUT', 8), // seconds
        'max_retries' => (int) env('AI_MAX_RETRIES', 3),
        'retry_delay_base' => (int) env('AI_RETRY_DELAY', 60), // seconds
        'queue_delay_new' => (int) env('AI_QUEUE_DELAY_NEW', 60), // seconds
        'queue_delay_existing' => (int) env('AI_QUEUE_DELAY_EXISTING', 30), // seconds
        'queue_delay_high_priority' => (int) env('AI_QUEUE_DELAY_PRIORITY', 0), // seconds
        'batch_size' => (int) env('AI_BATCH_SIZE', 50),
    ],

    'conversation' => [
        'max_history_messages' => (int) env('AI_MAX_HISTORY', 10),
        'context_window_hours' => (int) env('AI_CONTEXT_WINDOW', 24),
        'sentiment_confidence_threshold' => (float) env('AI_SENTIMENT_THRESHOLD', 0.7),
        'escalation_sentiment_threshold' => (float) env('AI_ESCALATION_SENTIMENT', 0.8),
    ],

    'leads' => [
        'auto_assign_agents' => env('AI_AUTO_ASSIGN_AGENTS', true),
        'lead_score_recalc_frequency' => env('AI_LEAD_SCORE_FREQUENCY', 'daily'), // daily, weekly
        'inactive_lead_days' => (int) env('AI_INACTIVE_LEAD_DAYS', 30),
        'churn_detection_days' => (int) env('AI_CHURN_DETECTION_DAYS', 14),
    ],

    'negotiation' => [
        'default_max_discount' => (int) env('AI_DEFAULT_MAX_DISCOUNT', 15), // percentage
        'price_adjustment_tolerance' => (float) env('AI_PRICE_TOLERANCE', 0.05), // 5%
        'auto_approve_discount_limit' => (int) env('AI_AUTO_APPROVE_DISCOUNT', 10), // percentage
        'large_order_escalation' => (float) env('AI_LARGE_ORDER_THRESHOLD', 1000), // currency
    ],

    'followup' => [
        'default_delay_hours' => (int) env('AI_FOLLOWUP_DELAY', 24),
        'max_followups' => (int) env('AI_MAX_FOLLOWUPS', 3),
        'followup_decay_factor' => (float) env('AI_FOLLOWUP_DECAY', 2.0), // multiply delay by this
        'business_hours_only' => env('AI_FOLLOWUP_BUSINESS_HOURS', true),
    ],

    'escalation' => [
        'auto_escalate_after_attempts' => (int) env('AI_ESCALATE_ATTEMPTS', 3),
        'escalate_negative_sentiment' => env('AI_ESCALATE_NEGATIVE', true),
        'escalation_sla_hours' => (int) env('AI_ESCALATION_SLA', 4),
        'priority_phone_numbers' => array_filter(explode(',', env('AI_PRIORITY_PHONES', ''))),
    ],

    'cleanup' => [
        'conversation_retention_days' => (int) env('AI_CONVERSATION_RETENTION', 90),
        'handoff_retention_days' => (int) env('AI_HANDOFF_RETENTION', 180),
        'failed_message_retention_days' => (int) env('AI_FAILED_MESSAGE_RETENTION', 7),
        'auto_cleanup_enabled' => env('AI_AUTO_CLEANUP', true),
    ],

    'monitoring' => [
        'log_ai_responses' => env('AI_LOG_RESPONSES', true),
        'log_processing_time' => env('AI_LOG_TIMING', true),
        'alert_on_high_failure_rate' => env('AI_ALERT_FAILURES', true),
        'failure_rate_threshold' => (float) env('AI_FAILURE_THRESHOLD', 0.1), // 10%
        'performance_monitoring' => env('AI_PERFORMANCE_MONITORING', true),
        'email_alerts_enabled' => env('AI_EMAIL_ALERTS_ENABLED', false), // Disabled by default to prevent SMTP errors
    ],

    'features' => [
        'sentiment_analysis' => env('AI_SENTIMENT_ANALYSIS', true),
        'product_recommendations' => env('AI_PRODUCT_RECOMMENDATIONS', true),
        'auto_product_descriptions' => env('AI_AUTO_DESCRIPTIONS', true),
        'conversation_summarization' => env('AI_CONVERSATION_SUMMARY', true),
        'lead_scoring' => env('AI_LEAD_SCORING', true),
    ],

    'limits' => [
        'daily_api_calls' => (int) env('AI_DAILY_API_LIMIT', 10000),
        'hourly_api_calls' => (int) env('AI_HOURLY_API_LIMIT', 1000),
        'concurrent_processing' => (int) env('AI_CONCURRENT_LIMIT', 10),
        'user_daily_messages' => (int) env('AI_USER_DAILY_LIMIT', 1000),
    ],

    'default_agent_settings' => [
        'personality' => env('AI_DEFAULT_PERSONALITY', 'Professional, friendly, and helpful'),
        'tone' => env('AI_DEFAULT_TONE', 'conversational'),
        'max_discount' => (int) env('AI_DEFAULT_AGENT_DISCOUNT', 15),
        'followup_enabled' => env('AI_DEFAULT_FOLLOWUP', true),
        'negotiation_enabled' => env('AI_DEFAULT_NEGOTIATION', true),
        'business_hours' => [
            'enabled' => env('AI_DEFAULT_BUSINESS_HOURS', false),
            'timezone' => env('AI_DEFAULT_TIMEZONE', 'Africa/Nairobi'),
            'start_time' => env('AI_DEFAULT_START_TIME', '09:00'),
            'end_time' => env('AI_DEFAULT_END_TIME', '17:00'),
            'days' => explode(',', env('AI_DEFAULT_BUSINESS_DAYS', 'monday,tuesday,wednesday,thursday,friday')),
        ],
    ],

    'webhooks' => [
        'timeout' => (int) env('AI_WEBHOOK_TIMEOUT', 10),
        'retry_failed_webhooks' => env('AI_RETRY_WEBHOOKS', true),
        'webhook_signature_verification' => env('AI_VERIFY_WEBHOOKS', false),
        'log_webhook_payloads' => env('AI_LOG_WEBHOOKS', false),
    ],

    'cache' => [
        'agent_config_ttl' => (int) env('AI_CACHE_AGENT_TTL', 3600), // 1 hour
        'product_data_ttl' => (int) env('AI_CACHE_PRODUCT_TTL', 1800), // 30 minutes
        'lead_score_ttl' => (int) env('AI_CACHE_LEAD_TTL', 900), // 15 minutes
        'conversation_context_ttl' => (int) env('AI_CACHE_CONTEXT_TTL', 7200), // 2 hours
    ],
];