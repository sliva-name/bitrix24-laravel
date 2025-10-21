<?php

declare(strict_types=1);

return [
    'default' => env('BITRIX24_DEFAULT_CONNECTION', 'main'),

    'connections' => [
        'main' => [
            'type' => env('BITRIX24_AUTH_TYPE', 'oauth'),
            'domain' => env('BITRIX24_DOMAIN'),
            'client_id' => env('BITRIX24_CLIENT_ID'),
            'client_secret' => env('BITRIX24_CLIENT_SECRET'),
            'redirect_uri' => env('BITRIX24_REDIRECT_URI', env('APP_URL') . '/api/bitrix24/callback'),
            'webhook_url' => env('BITRIX24_WEBHOOK_URL'),
        ],
    ],

    'token_storage' => env('BITRIX24_TOKEN_STORAGE', 'database'),

    'cache' => [
        'store' => env('BITRIX24_CACHE_STORE', 'database'),
        'prefix' => 'bitrix24_tokens',
        'ttl' => 3600,
    ],

    'webhook' => [
        'enabled' => env('BITRIX24_WEBHOOK_ENABLED', true),
        'secret' => env('BITRIX24_WEBHOOK_SECRET'),
    ],

    'logging' => [
        'enabled' => env('BITRIX24_LOGGING_ENABLED', false),
        'channel' => env('BITRIX24_LOG_CHANNEL', 'daily'),
    ],

    'api' => [
        'timeout' => env('BITRIX24_API_TIMEOUT', 30),
        'retry_attempts' => env('BITRIX24_API_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('BITRIX24_API_RETRY_DELAY', 1000),
    ],
];
