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

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:8080',
        env('FRONTEND_URL'),
        env('PRODUCTION_URL'),
    ]),

    'allowed_origins_patterns' => [
        // Allow localhost with any port (development)
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-Token',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
