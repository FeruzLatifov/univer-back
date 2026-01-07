<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Production'da faqat belgilangan domenlar
    'allowed_origins' => env('APP_ENV') === 'production'
        ? array_filter([
            env('FRONTEND_URL'),
            env('PRODUCTION_URL'),
        ])
        : array_filter([
            'http://localhost:5173',
            'http://localhost:3000',
            'http://localhost:8080',
            env('FRONTEND_URL'),
            env('PRODUCTION_URL'),
        ]),

    // Production'da pattern'larni o'chirish
    'allowed_origins_patterns' => env('APP_ENV') === 'production'
        ? []
        : [
            '/^http:\/\/localhost:\d+$/',
            '/^http:\/\/127\.0\.0\.1:\d+$/',
        ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-Token',
        'X-Locale',
        'X-Language',
        'X-App-Key',           // App identifikatori
        'X-App-Signature',     // So'rov imzosi
        'X-App-Timestamp',     // Vaqt belgisi
        'X-Device-Id',         // Qurilma identifikatori
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
