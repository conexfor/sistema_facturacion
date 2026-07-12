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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Consulta de RUC (SUNAT) y DNI (RENIEC). Compatible con decolecta.com y apis.net.pe.
    'peru_api' => [
        'base_url' => env('PERU_API_URL', 'https://api.decolecta.com/v1'),
        'token' => env('PERU_API_TOKEN'),
        'timeout' => env('PERU_API_TIMEOUT', 12),
        // Segundos que se cachea cada consulta RUC/DNI (86400 = 24 h; 0 = sin cache).
        'cache_ttl' => env('PERU_API_CACHE_TTL', 86400),
    ],

];
