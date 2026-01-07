<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Security Configuration
    |--------------------------------------------------------------------------
    |
    | Bu yerda API xavfsizlik sozlamalari saqlanadi.
    | Faqat ruxsat berilgan ilovalar API'ga murojaat qila oladi.
    |
    */

    // API xavfsizlikni yoqish/o'chirish
    'enabled' => env('API_SECURITY_ENABLED', true),

    // App kalitlari - har bir ilova uchun alohida
    'app_keys' => [
        'web' => [
            'key' => env('API_APP_KEY_WEB'),
            'secret' => env('API_APP_SECRET_WEB'),
            'name' => 'Web Frontend',
            'allowed_origins' => [
                env('FRONTEND_URL', 'http://localhost:3000'),
            ],
        ],
        'mobile_android' => [
            'key' => env('API_APP_KEY_ANDROID'),
            'secret' => env('API_APP_SECRET_ANDROID'),
            'name' => 'Android App',
            'package_name' => env('ANDROID_PACKAGE_NAME', 'com.univer.app'),
        ],
        'mobile_ios' => [
            'key' => env('API_APP_KEY_IOS'),
            'secret' => env('API_APP_SECRET_IOS'),
            'name' => 'iOS App',
            'bundle_id' => env('IOS_BUNDLE_ID', 'com.univer.app'),
        ],
        'telegram_bot' => [
            'key' => env('API_APP_KEY_TELEGRAM'),
            'secret' => env('API_APP_SECRET_TELEGRAM'),
            'name' => 'Telegram Bot',
            'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        ],
    ],

    // So'rov imzosi sozlamalari
    'signature' => [
        // Imzo algoritmi
        'algorithm' => 'sha256',

        // Timestamp farqi (soniyalarda) - 5 daqiqa
        'timestamp_tolerance' => 300,

        // Imzoga qo'shiladigan maydonlar
        'signed_headers' => [
            'X-App-Key',
            'X-App-Timestamp',
            'X-Device-Id',
        ],
    ],

    // Device fingerprint sozlamalari
    'device' => [
        // Bitta qurilmadan maksimal sessiyalar
        'max_sessions_per_device' => 3,

        // Shubhali qurilmalarni bloklash
        'block_suspicious' => true,

        // Yangi qurilmani tasdiqlash talab qilish
        'require_verification' => env('REQUIRE_DEVICE_VERIFICATION', false),
    ],

    // Himoyadan mustasno qilinadigan routelar
    'excluded_routes' => [
        'api/health',
        'api/v1/auth/*',
        'api/v1/employee/auth/login',
        'api/v1/employee/auth/forgot-password',
        'api/v1/auth/student-login',
        'api/docs*',
    ],

    // IP whitelist (bo'sh bo'lsa tekshirilmaydi)
    'ip_whitelist' => array_filter(explode(',', env('API_IP_WHITELIST', ''))),

    // Shubhali faoliyatni bloklash
    'security' => [
        // Bir soatda maksimal muvaffaqiyatsiz urinishlar
        'max_failed_attempts' => 10,

        // Bloklash vaqti (soniyalarda) - 1 soat
        'block_duration' => 3600,

        // Logga yozish
        'log_requests' => env('API_LOG_REQUESTS', false),
    ],

];
