<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

/**
 * Cache Invalidation Service
 *
 * Manages automatic cache clearing when data changes
 *
 * Strategy:
 * 1. Tag-based invalidation (group related keys)
 * 2. Pattern-based invalidation (wildcard matching)
 * 3. Event-driven invalidation (model observers)
 */
class CacheInvalidationService
{
    /**
     * Cache tags for related data
     */
    protected static array $tags = [
        'departments' => [
            'departments:*',
            'faculties:*',      // Faculties depend on departments
            'structure:*',      // Structure tree cache
        ],
        'subjects' => [
            'subjects:*',
            'curriculum:*',     // Curriculum depends on subjects
            'schedules:*',      // Schedules use subjects
        ],
        'students' => [
            'students:*',
            'groups:*',         // Groups contain students
        ],
        'employees' => [
            'employees:*',
            'teachers:*',
            'schedules:*',
        ],
        'groups' => [
            'groups:*',
            'students:*',
        ],
        'languages' => [
            'languages:*',
        ],
    ];

    /**
     * Invalidate cache by type
     *
     * @param string $type Cache type (departments, subjects, etc.)
     * @param bool $cascade Also invalidate related caches
     */
    public static function invalidate(string $type, bool $cascade = true): void
    {
        try {
            if ($cascade && isset(self::$tags[$type])) {
                // Clear all related patterns
                foreach (self::$tags[$type] as $pattern) {
                    self::clearPattern($pattern);
                }
            } else {
                // Clear only specific type
                self::clearPattern("{$type}:*");
            }

            Log::info("Cache invalidated", [
                'type' => $type,
                'cascade' => $cascade,
                'patterns' => self::$tags[$type] ?? ["{$type}:*"],
            ]);
        } catch (\Exception $e) {
            Log::error("Cache invalidation failed", [
                'type' => $type,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate specific key
     */
    public static function invalidateKey(string $key): void
    {
        try {
            Cache::forget($key);
            Log::debug("Cache key invalidated: {$key}");
        } catch (\Exception $e) {
            Log::error("Cache key invalidation failed", [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear cache by pattern (wildcard)
     *
     * @param string $pattern Pattern with * wildcard (e.g., 'departments:*')
     */
    protected static function clearPattern(string $pattern): void
    {
        try {
            $prefix = config('cache.prefix');
            $fullPattern = $prefix . $pattern;

            // Get Redis instance
            $redis = Redis::connection('cache')->client();

            // Find all matching keys
            $keys = $redis->keys($fullPattern);

            if (!empty($keys)) {
                // Remove prefix for Laravel Cache
                $keysToDelete = array_map(function ($key) use ($prefix) {
                    return str_replace($prefix, '', $key);
                }, $keys);

                // Delete keys
                foreach ($keysToDelete as $key) {
                    Cache::forget($key);
                }

                Log::debug("Cleared {count} keys matching pattern: {$pattern}", [
                    'count' => count($keysToDelete),
                    'pattern' => $pattern,
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Pattern clear failed", [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate all cache
     */
    public static function invalidateAll(): void
    {
        try {
            Cache::flush();
            Log::warning("All cache flushed");
        } catch (\Exception $e) {
            Log::error("Cache flush failed", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get cache statistics
     */
    public static function getStats(): array
    {
        try {
            $redis = Redis::connection('cache')->client();
            $info = $redis->info('stats');

            return [
                'hits' => $info['keyspace_hits'] ?? 0,
                'misses' => $info['keyspace_misses'] ?? 0,
                'hit_rate' => self::calculateHitRate(
                    $info['keyspace_hits'] ?? 0,
                    $info['keyspace_misses'] ?? 0
                ),
            ];
        } catch (\Exception $e) {
            return [
                'hits' => 0,
                'misses' => 0,
                'hit_rate' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate cache hit rate
     */
    protected static function calculateHitRate(int $hits, int $misses): float
    {
        $total = $hits + $misses;
        if ($total === 0) {
            return 0;
        }

        return round(($hits / $total) * 100, 2);
    }
}
