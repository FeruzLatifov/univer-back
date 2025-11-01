<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Cache Service
 *
 * Centralized caching service for API responses and database queries
 *
 * Usage:
 *   $departments = CacheService::remember('departments', function() {
 *       return EDepartment::with('children')->active()->get();
 *   });
 */
class CacheService
{
    /**
     * Cache TTL (seconds) for different data types
     */
    protected static array $ttl = [
        'languages' => 86400,        // 24 hours
        'departments' => 3600,       // 1 hour
        'faculties' => 3600,         // 1 hour
        'subjects' => 1800,          // 30 minutes
        'groups' => 1800,            // 30 minutes
        'students' => 600,           // 10 minutes
        'employees' => 1800,         // 30 minutes
        'schedules' => 900,          // 15 minutes
        'api_response' => 300,       // 5 minutes
    ];

    /**
     * Remember (Get or Set) cache
     *
     * @param string $key Cache key
     * @param callable $callback Function to execute if cache miss
     * @param string|null $type Data type for TTL (languages, departments, etc.)
     * @param int|null $ttl Custom TTL in seconds
     * @return mixed
     */
    public static function remember(
        string $key,
        callable $callback,
        ?string $type = 'api_response',
        ?int $ttl = null
    ) {
        $ttl = $ttl ?? self::$ttl[$type] ?? self::$ttl['api_response'];

        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Exception $e) {
            Log::error('Cache error', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // Fallback: execute callback without caching
            return $callback();
        }
    }

    /**
     * Get from cache
     */
    public static function get(string $key, $default = null)
    {
        try {
            return Cache::get($key, $default);
        } catch (\Exception $e) {
            Log::error('Cache get error', ['key' => $key, 'error' => $e->getMessage()]);
            return $default;
        }
    }

    /**
     * Put to cache
     */
    public static function put(string $key, $value, ?string $type = 'api_response', ?int $ttl = null): bool
    {
        $ttl = $ttl ?? self::$ttl[$type] ?? self::$ttl['api_response'];

        try {
            return Cache::put($key, $value, $ttl);
        } catch (\Exception $e) {
            Log::error('Cache put error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Forget (delete) cache
     */
    public static function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (\Exception $e) {
            Log::error('Cache forget error', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Flush cache by pattern/tag
     */
    public static function forgetByPattern(string $pattern): void
    {
        try {
            $keys = Cache::getRedis()->keys($pattern);
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    Cache::forget($key);
                }
            }
        } catch (\Exception $e) {
            Log::error('Cache flush pattern error', ['pattern' => $pattern, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Clear all cache
     */
    public static function flush(): bool
    {
        try {
            return Cache::flush();
        } catch (\Exception $e) {
            Log::error('Cache flush error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Generate cache key
     */
    public static function key(string $prefix, ...$params): string
    {
        $parts = array_map(function ($param) {
            if (is_array($param)) {
                return md5(json_encode($param));
            }
            return (string) $param;
        }, $params);

        return implode(':', array_merge([$prefix], $parts));
    }

    /**
     * Cache department list
     */
    public static function departments(array $filters = [])
    {
        $key = self::key('departments', $filters);
        return self::remember($key, function () use ($filters) {
            return \App\Models\EDepartment::query()
                ->when(isset($filters['active']), fn($q) => $q->where('active', $filters['active']))
                ->when(isset($filters['parent']), fn($q) => $q->where('_parent', $filters['parent']))
                ->get();
        }, 'departments');
    }

    /**
     * Cache subject list
     */
    public static function subjects(array $filters = [])
    {
        $key = self::key('subjects', $filters);
        return self::remember($key, function () use ($filters) {
            return \App\Models\ESubject::query()
                ->when(isset($filters['active']), fn($q) => $q->where('active', $filters['active']))
                ->when(isset($filters['department']), fn($q) => $q->where('_department', $filters['department']))
                ->get();
        }, 'subjects');
    }

    /**
     * Cache languages
     */
    public static function languages()
    {
        return self::remember('languages:all', function () {
            return \App\Models\HLanguage::active()->ordered()->get();
        }, 'languages');
    }

    /**
     * Invalidate (clear) cache for a specific type
     */
    public static function invalidate(string $type): void
    {
        self::forgetByPattern("*{$type}*");
    }
}
