<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Cache Store
    |--------------------------------------------------------------------------
    */

    'default' => env('CACHE_DRIVER', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Cache Stores
    |--------------------------------------------------------------------------
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CACHE_CONNECTION'),
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
            'lock_table' => env('DB_CACHE_LOCK_TABLE'),
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_path' => storage_path('framework/cache/data'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Key Prefix
    |--------------------------------------------------------------------------
    */

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_cache_'),

    /*
    |--------------------------------------------------------------------------
    | Cache TTL (Time To Live) - Default settings
    |--------------------------------------------------------------------------
    */

    'ttl' => [
        'languages' => 86400,        // 24 hours - Rarely change
        'departments' => 3600,       // 1 hour - Occasionally change
        'faculties' => 3600,         // 1 hour
        'subjects' => 1800,          // 30 minutes
        'groups' => 1800,            // 30 minutes
        'students' => 600,           // 10 minutes - Frequently accessed
        'employees' => 1800,         // 30 minutes
        'schedules' => 900,          // 15 minutes - May change during day
        'api_responses' => 300,      // 5 minutes - General API responses
    ],

];
