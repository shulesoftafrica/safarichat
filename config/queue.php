<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    | For development: Use 'database' for easy testing
    | For production: Use 'redis' for better performance
    |
    */

    'default' => env('QUEUE_CONNECTION', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 300,
            'block_for' => null,
            'after_commit' => false,
        ],

        // High priority queue for urgent messages (incoming messages, single sends)
        'high_priority' => [
            'driver' => env('QUEUE_CONNECTION', 'database') === 'redis' ? 'redis' : 'database',
            'connection' => env('QUEUE_CONNECTION', 'database') === 'redis' ? 'default' : null,
            'table' => env('QUEUE_CONNECTION', 'database') === 'database' ? 'jobs' : null,
            'queue' => 'high_priority',
            'retry_after' => 300,
            'block_for' => null,
            'after_commit' => false,
        ],

        // Regular messages queue
        'messages' => [
            'driver' => env('QUEUE_CONNECTION', 'database') === 'redis' ? 'redis' : 'database',
            'connection' => env('QUEUE_CONNECTION', 'database') === 'redis' ? 'default' : null,
            'table' => env('QUEUE_CONNECTION', 'database') === 'database' ? 'jobs' : null,
            'queue' => 'messages',
            'retry_after' => 300,
            'block_for' => null,
            'after_commit' => false,
        ],

        // Bulk messages queue for large batches
        'bulk_messages' => [
            'driver' => env('QUEUE_CONNECTION', 'database') === 'redis' ? 'redis' : 'database',
            'connection' => env('QUEUE_CONNECTION', 'database') === 'redis' ? 'default' : null,
            'table' => env('QUEUE_CONNECTION', 'database') === 'database' ? 'jobs' : null,
            'queue' => 'bulk_messages',
            'retry_after' => 600,
            'block_for' => null,
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
