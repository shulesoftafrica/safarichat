<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sentry DSN
    |--------------------------------------------------------------------------
    |
    | The DSN tells the Sentry SDK where to send the events to. If this value
    | is not provided, the SDK will try to read it from the SENTRY_LARAVEL_DSN
    | environment variable. If that variable also does not exist, the SDK will
    | just not send any events.
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Sentry Release
    |--------------------------------------------------------------------------
    |
    | Here you may define the release for your application. This will be sent
    | with every error event to Sentry. If you don't provide a release, it
    | will be automatically detected by the SDK.
    |
    */

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Sentry Environment
    |--------------------------------------------------------------------------
    |
    | Here you may define the environment for your application. This will be
    | sent with every error event to Sentry.
    |
    */

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Sentry Sample Rate
    |--------------------------------------------------------------------------
    |
    | This value controls the sample rate for error events. The value must be
    | between 0.0 and 1.0. A value of 0.25 means 25% of error events are sent
    | to Sentry. A value of 1.0 means 100% of error events are sent.
    |
    */

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Sentry Traces Sample Rate
    |--------------------------------------------------------------------------
    |
    | This value controls the sample rate for performance monitoring. The value
    | must be between 0.0 and 1.0. A value of 0.25 means 25% of transactions
    | are sent to Sentry for performance monitoring.
    |
    */

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    /*
    |--------------------------------------------------------------------------
    | Send Default PII
    |--------------------------------------------------------------------------
    |
    | If this option is set to true, certain personally identifiable information
    | is added by active integrations. Without this flag, they are not sent by
    | default, due to privacy concerns.
    |
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    /*
    |--------------------------------------------------------------------------
    | Sentry Breadcrumbs
    |--------------------------------------------------------------------------
    |
    | These options control what types of breadcrumbs are automatically captured
    | by Sentry. Breadcrumbs help reconstruct the events leading up to an error.
    |
    */

    'breadcrumbs' => [
        // Capture SQL queries as breadcrumbs
        'sql_queries' => env('SENTRY_BREADCRUMBS_SQL_QUERIES', true),

        // Capture SQL bindings (parameters) in queries  
        'sql_bindings' => env('SENTRY_BREADCRUMBS_SQL_BINDINGS', false),

        // Capture queue job information
        'queue_info' => env('SENTRY_BREADCRUMBS_QUEUE_INFO', true),

        // Capture command information
        'command_info' => env('SENTRY_BREADCRUMBS_COMMAND_INFO', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sentry Integrations
    |--------------------------------------------------------------------------
    |
    | Here you can configure which integrations should be loaded. All available
    | integrations are enabled by default.
    |
    */

    'integrations' => [
        // Uncomment and configure integrations as needed
        // Sentry\Integration\RequestIntegration::class => [
        //     'max_request_body_size' => 'always',
        // ],
        // Sentry\Integration\FrameContextifierIntegration::class => [
        //     'max_file_size' => 1024 * 1024,
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Before Send Callback
    |--------------------------------------------------------------------------
    |
    | This callback is called before an event is sent to Sentry. You can use
    | it to filter out events that you don't want to send, or to modify the
    | event data before it's sent.
    |
    */

    'before_send' => function (\Sentry\Event $event, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        // Filter out queue connection errors in development
        if (app()->environment('local')) {
            $exceptions = $event->getExceptions();
            foreach ($exceptions as $exception) {
                if (str_contains($exception->getValue(), 'Connection refused') && 
                    str_contains($exception->getValue(), 'redis')) {
                    return null; // Don't send Redis connection errors in local development
                }
            }
        }

        return $event;
    },

    /*
    |--------------------------------------------------------------------------
    | Before Send Transaction Callback
    |--------------------------------------------------------------------------
    |
    | This callback is called before a transaction is sent to Sentry. You can
    | use it to filter out transactions that you don't want to send, or to
    | modify the transaction data before it's sent.
    |
    */

    'before_send_transaction' => function (\Sentry\Event $transaction, ?\Sentry\EventHint $hint): ?\Sentry\Event {
        return $transaction;
    },

    /*
    |--------------------------------------------------------------------------
    | Context Lines
    |--------------------------------------------------------------------------
    |
    | The number of lines of code context to capture around each stack frame
    | when an error occurs.
    |
    */

    'context_lines' => 5,

    /*
    |--------------------------------------------------------------------------
    | Error Types
    |--------------------------------------------------------------------------
    |
    | Set which PHP error types should be captured by Sentry.
    |
    */

    'error_types' => E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED,

    /*
    |--------------------------------------------------------------------------
    | Tags
    |--------------------------------------------------------------------------
    |
    | Tags to add to every event sent to Sentry.
    |
    */

    'tags' => [
        'component' => 'safarichat',
        'version' => env('APP_VERSION', '1.0.0'),
    ],

];
