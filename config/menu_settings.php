<?php

return [
    // Cache TTL in seconds (default 15 minutes)
    'cache_ttl' => (int) env('MENU_CACHE_TTL', 900),

    // Preserve legacy Yii2 path-based URLs in API response (no mapping)
    'preserve_legacy_paths' => (bool) env('MENU_PRESERVE_LEGACY_PATHS', true),

    // Supported locales for menu caching (falls back to APP_LOCALES)
    'locales' => array_filter(array_map('trim', explode(',', env('MENU_LOCALES', env('APP_LOCALES', 'uz,oz,ru,en'))))),
];

