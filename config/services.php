<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'api_key' => env('GOOGLE_API_KEY'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o'),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
    ],

    'waapi' => [
        'base_url' => env('WAAPI_BASE_URL', 'https://www.wasenderapi.com/api'),
        'api_key' => env('WAAPI_API_KEY'),
        'webhook_secret' => env('WAAPI_WEBHOOK_SECRET'),
        'timeout' => env('WAAPI_TIMEOUT', 30),
        'access_token' => env('WAAPI_ACCESS_TOKEN'),
    ],

    'wasender' => [
        'access_token' => env('WASENDER_ACCESS_TOKEN'),
        'base_url' => env('WASENDER_BASE_URL', 'https://api.wasenderapi.com'),
        'timeout' => env('WASENDER_TIMEOUT', 30),
        'default' => true,
    ],

    'unified_notification' => [
        'base_url' => env('NOTIFICATION_BASE_URL', 'https://notifications.shulesoft.africa/api'),
        'token' => env('NOTIFICATION_API_TOKEN'),
        'timeout' => env('NOTIFICATION_TIMEOUT', 30),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET_KEY' ),
        'public' => env('STRIPE_PUBLIC_KEY'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'usd'),
    ],

    'lipa_number' => [
        'api_url' => env('LIPA_NUMBER_API_URL', 'https://api.shulesoft.africa'),
        'instance_id' => env('LIPA_NUMBER_INSTANCE_ID', 'safarichat'),
        'webhook_secret' => env('LIPA_NUMBER_WEBHOOK_SECRET'),
    ],

    'billing' => [
        'api_url' => env('BILLING_API_URL', 'http://127.0.0.1:8000/api'),
        'api_key' => env('BILLING_API_KEY','21|jGYrq6GOU7w5lARAnu9ckTeMpCVOIu6qJgoCpU57843389ae'),
        'webhook_secret' => env('BILLING_WEBHOOK_SECRET'),
        'timeout' => env('BILLING_API_TIMEOUT', 30),
    ],

];
