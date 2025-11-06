<?php

namespace App\Repositories;

use App\Contracts\Repositories\MenuRepositoryInterface;
use App\Models\EAdmin;
use App\Models\EAdminResource;
use App\Models\EAdminRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

/**
 * Menu Repository Implementation
 *
 * Handles all menu data access
 * Uses caching for performance optimization
 *
 * @microservice-ready Can be extracted to separate service
 */
class MenuRepository implements MenuRepositoryInterface
{
    protected const CACHE_PREFIX = 'menu:';
    protected const CACHE_TTL = 3200; // 53 minutes (matches Yii2 cache duration)

    /**
     * Get menu configuration from file
     */
    public function getMenuConfig(): array
    {
        return Config::get('menu.backend', []);
    }

    /**
     * Get accessible resources for a role
     */
    public function getAccessibleResources(EAdminRole $role): Collection
    {
        $cacheKey = self::CACHE_PREFIX . "resources:role:{$role->id}";

        return Cache::remember($cacheKey, $this->getCacheTTL(), function () use ($role) {
            return EAdminResource::getAccessibleByRole($role);
        });
    }

    /**
     * Check if path is accessible by role
     */
    public function isPathAccessible(string $path, EAdminRole $role): bool
    {
        $path = trim($path, '/');

        // Super admin check
        if ($role->code === 'super_admin') {
            return true;
        }

        // Ajax requests
        if (str_starts_with($path, 'ajax')) {
            return true;
        }

        // Get resource from cache or DB
        $cacheKey = self::CACHE_PREFIX . "resource:path:" . md5($path);

        $resource = Cache::remember($cacheKey, $this->getCacheTTL(), function () use ($path) {
            return EAdminResource::findByPath($path);
        });

        if (!$resource) {
            return false;
        }

        // Check if public resource
        if ($resource->skip) {
            return true;
        }

        // Check role access
        return $resource->isAccessibleByRole($role);
    }

    /**
     * Get cached menu for user
     *
     * @param int $userId User ID
     * @param string $locale Locale code
     * @param int|null $roleId Role ID (CRITICAL: must be included to prevent wrong cache)
     */
    public function getCachedMenu(int $userId, string $locale, ?int $roleId = null): ?array
    {
        // ✅ FIX: Include roleId in cache key to prevent wrong menu after role switch
        $cacheKey = $roleId
            ? self::CACHE_PREFIX . "user:{$userId}:role:{$roleId}:locale:{$locale}"
            : self::CACHE_PREFIX . "user:{$userId}:locale:{$locale}"; // Fallback for backward compatibility

        return Cache::get($cacheKey);
    }

    /**
     * Cache menu for user
     *
     * @param int $userId User ID
     * @param string $locale Locale code
     * @param array $menu Menu data
     * @param int $ttl Cache TTL in seconds
     * @param int|null $roleId Role ID (CRITICAL: must be included to prevent wrong cache)
     */
    public function cacheMenu(int $userId, string $locale, array $menu, int $ttl = self::CACHE_TTL, ?int $roleId = null): bool
    {
        // ✅ FIX: Include roleId in cache key to prevent wrong menu after role switch
        $cacheKey = $roleId
            ? self::CACHE_PREFIX . "user:{$userId}:role:{$roleId}:locale:{$locale}"
            : self::CACHE_PREFIX . "user:{$userId}:locale:{$locale}"; // Fallback for backward compatibility

        return Cache::put($cacheKey, $menu, $ttl);
    }

    /**
     * Invalidate menu cache for user
     */
    public function invalidateMenuCache(int $userId): bool
    {
        $locales = $this->getSupportedLocales();

        foreach ($locales as $locale) {
            Cache::forget(self::CACHE_PREFIX . "user:{$userId}:locale:{$locale}");
        }

        $admin = EAdmin::with('roles')->find($userId);
        if ($admin) {
            $roleIds = $admin->roles->pluck('id')->filter()->all();
            if ($admin->_role && !in_array($admin->_role, $roleIds, true)) {
                $roleIds[] = $admin->_role;
            }

            foreach ($roleIds as $roleId) {
                foreach ($locales as $locale) {
                    Cache::forget(self::CACHE_PREFIX . "user:{$userId}:role:{$roleId}:locale:{$locale}");
                }
            }
        }

        return true;
    }

    /**
     * Invalidate all menu caches (on permission change)
     */
    public function invalidateAllMenuCaches(): bool
    {
        // Clear all menu-related caches
        Cache::flush(); // Or use tags if available

        return true;
    }

    /**
     * Get menu cache TTL
     */
    public function getCacheTTL(): int
    {
        // Read from config/env with sensible fallback
        $configured = config('menu_settings.cache_ttl');
        if (is_int($configured) && $configured > 0) {
            return $configured;
        }
        $envTtl = (int) env('MENU_CACHE_TTL', self::CACHE_TTL);
        return $envTtl > 0 ? $envTtl : self::CACHE_TTL;
    }

    /**
     * Determine supported locales for caching
     */
    protected function getSupportedLocales(): array
    {
        $configured = Config::get('menu_settings.locales');
        if (is_array($configured) && !empty($configured)) {
            return array_values(array_filter(array_map('trim', $configured)));
        }

        $envLocales = env('APP_LOCALES');
        if ($envLocales) {
            return array_values(array_filter(array_map('trim', explode(',', $envLocales))));
        }

        return ['uz', 'oz', 'ru', 'en'];
    }
}
