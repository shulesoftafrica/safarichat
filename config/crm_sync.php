<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CRM Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for external CRM synchronization
    |
    */

    'enabled' => env('CRM_SYNC_ENABLED', false),
    
    'api_key' => env('CRM_API_KEY'),
    
    'base_url' => env('CRM_BASE_URL', 'https://api.yourcrm.com'),
    
    'endpoints' => [
        'contacts' => '/api/contacts',
        'leads' => '/api/leads', 
        'conversations' => '/api/conversations',
        'webhooks' => '/api/webhooks'
    ],
    
    'webhook_secret' => env('CRM_WEBHOOK_SECRET'),
    
    'sync_settings' => [
        'batch_size' => 100,
        'retry_attempts' => 3,
        'timeout' => 30,
        'auto_sync' => env('CRM_AUTO_SYNC', false),
        'sync_interval_minutes' => env('CRM_SYNC_INTERVAL', 60)
    ],
    
    'field_mapping' => [
        'contact' => [
            'name' => 'guest_name',
            'email' => 'guest_email', 
            'phone' => 'guest_phone',
            'external_id' => 'external_crm_id'
        ],
        'lead' => [
            'status' => 'status',
            'score' => 'lead_score',
            'company' => 'company_name',
            'industry' => 'industry',
            'source' => 'source'
        ]
    ],

    'real_time' => [
        'enabled' => env('CRM_REALTIME_ENABLED', true),
        'broadcast_driver' => env('BROADCAST_DRIVER', 'pusher'),
        'channels' => [
            'lead_updates' => 'lead.{id}',
            'conversation_updates' => 'conversation.{lead_id}',
            'user_updates' => 'user.{user_id}'
        ]
    ]

];