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
        'client_id' =>'923342213823-8gfjlgc718rfthma8f5mq8p2v7jbrlmc.apps.googleusercontent.com',
        'client_secret' => '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',
        'api_key' => 'AIzaSyAkBHLJAbHlL8orlDs-t3BpY6grx4zG4H0',
        'redirect' => 'https://safarichat.africa/auth/google/callback',
    ],

];
