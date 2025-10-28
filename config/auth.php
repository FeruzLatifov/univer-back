<?php

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'admin-api'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'admins'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Alohida guard'lar: admin va student
    | - admin-api: Admin va xodimlar uchun
    | - student-api: Talabalar uchun
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],

        // Admin/Employee guard
        'admin-api' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ],

        // Student guard
        'student-api' => [
            'driver' => 'jwt',
            'provider' => 'students',
        ],

        // Legacy guard (backward compatibility)
        'api' => [
            'driver' => 'jwt',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\EAdmin::class,
        ],

        'students' => [
            'driver' => 'eloquent',
            'model' => App\Models\EStudent::class,
        ],

        // Legacy provider
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\EAdmin::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
