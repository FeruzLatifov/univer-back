<?php

namespace App\Contracts\Repositories;

use App\Models\EAdminRole;
use Illuminate\Support\Collection;

/**
 * Menu Repository Interface
 *
 * Defines contract for menu data access
 * Allows switching between different data sources (DB, API, Cache)
 */
interface MenuRepositoryInterface
{
    /**
     * Get menu configuration
     *
     * @return array Menu structure from config
     */
    public function getMenuConfig(): array;

    /**
     * Get accessible resources for a role
     *
     * @param EAdminRole $role
     * @return Collection
     */
    public function getAccessibleResources(EAdminRole $role): Collection;

    /**
     * Check if path is accessible by role
     *
     * @param string $path
     * @param EAdminRole $role
     * @return bool
     */
    public function isPathAccessible(string $path, EAdminRole $role): bool;

    /**
     * Get cached menu for user
     *
     * @param int $userId
     * @param string $locale
     * @param int|null $roleId Role ID (include to prevent wrong cache after role switch)
     * @return array|null
     */
    public function getCachedMenu(int $userId, string $locale, ?int $roleId = null): ?array;

    /**
     * Cache menu for user
     *
     * @param int $userId
     * @param string $locale
     * @param array $menu
     * @param int $ttl Time to live in seconds
     * @param int|null $roleId Role ID (include to prevent wrong cache after role switch)
     * @return bool
     */
    public function cacheMenu(int $userId, string $locale, array $menu, int $ttl = 900, ?int $roleId = null): bool;

    /**
     * Invalidate menu cache for user
     *
     * @param int $userId
     * @return bool
     */
    public function invalidateMenuCache(int $userId): bool;
}
