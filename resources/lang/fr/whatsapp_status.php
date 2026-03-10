<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Status Monitoring Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used in the WhatsApp instance status
    | monitoring page for tracking WaSender integration and connection health.
    |
    */

    // Page Header
    'page_title' => 'WhatsApp Instance Status',
    'page_subtitle' => 'Monitor your WhatsApp connections and WaSender integration',

    // Actions
    'actions' => [
        'refresh' => 'Refresh',
        'test_wasender' => 'Test WaSender',
        'send' => 'Send',
        'try_again' => 'Try Again',
    ],

    // Stats
    'stats' => [
        'total_instances' => 'Total Instances',
        'connected' => 'Connected',
        'connecting' => 'Connecting',
        'errors' => 'Errors',
    ],

    // Status Labels
    'status' => [
        'connected' => 'CONNECTED',
        'connecting' => 'CONNECTING',
        'disconnected' => 'DISCONNECTED',
        'error' => 'ERROR',
    ],

    // Loading States
    'loading' => [
        'default' => 'Loading...',
        'instances' => 'Loading instances...',
    ],

    // Test Section
    'test' => [
        'title' => 'Send Test Message',
        'select_instance' => 'Select Instance',
        'chat_id_placeholder' => 'Chat ID (e.g., 255700000000@c.us)',
        'message_placeholder' => 'Test message',
        'fill_all_fields' => 'Please fill all fields',
        'success' => 'Test message sent successfully!',
        'failed' => 'Failed to send message: :message',
        'error' => 'Error sending message: :error',
    ],

    // Empty State
    'empty' => [
        'title' => 'No WhatsApp instances found',
        'description' => 'Go to <a href=":url">Setup Page</a> to connect your WhatsApp',
    ],

    // Instance Details
    'instance' => [
        'created' => 'Created:',
        'last_seen' => 'Last seen:',
        'never' => 'Never',
        'id' => 'ID:',
        'webhook_configured' => 'Webhook configured',
    ],

    // Alerts & Messages
    'alerts' => [
        'load_failed' => 'Failed to load instances: :message',
        'load_error' => 'Error loading instances: :error',
        'wasender_success' => 'WaSender connection successful!',
        'wasender_failed' => 'WaSender connection failed: :message',
        'wasender_error' => 'WaSender connection error: :error',
    ],

    // Error Page
    'error' => [
        'title' => 'Error',
    ],
];
