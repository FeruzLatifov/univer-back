<?php

namespace App\Services\Permission;

use App\Models\EAdmin;
use App\Models\EStudent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Permission Cache Service
 *
 * Manages user permissions with Redis caching for optimal performance.
 * Implements 10-year senior developer best practices:
 * - Minimal JWT payload (NO permissions in token)
 * - Server-side permission storage with cache
 * - Wildcard permission matching (admin.* matches admin.view, etc.)
 * - 10-minute TTL for security and freshness
 *
 * @package App\Services\Permission
 */
class PermissionCacheService
{
    /**
     * Cache TTL in seconds (10 minutes)
     * Balance between performance and security
     */
    private int $cacheTTL = 600;

    /**
     * Cache key prefix for permissions
     */
    private string $cachePrefix = 'user_permissions';

    /**
     * Get all permissions for a user (with caching)
     *
     * @param int $userId User ID (e_admin.id or e_student.id)
     * @param string $userType User type: 'employee' or 'student'
     * @return array Array of permission strings
     */
    public function getUserPermissions(int $userId, string $userType = 'employee'): array
    {
        $cacheKey = $this->getCacheKey($userId, $userType);

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($userId, $userType) {
            return $this->loadPermissionsFromDatabase($userId, $userType);
        });
    }

    /**
     * Check if user has a specific permission
     *
     * Supports:
     * - Exact match: 'teacher.dashboard.view'
     * - Wildcard match: 'teacher.*' matches 'teacher.dashboard.view'
     * - Super admin check: 'admin' or 'techadmin' users have all permissions
     *
     * @param int $userId
     * @param string $userType
     * @param string $permission Permission to check
     * @return bool
     */
    public function hasPermission(int $userId, string $userType, string $permission): bool
    {
        // Get user to check for super admin status
        $user = $this->getUserModel($userId, $userType);

        if (!$user) {
            return false;
        }

        // Super admins have all permissions
        if ($userType === 'employee' && in_array($user->login ?? '', ['admin', 'techadmin'])) {
            Log::info('[PermissionCache] Super admin access granted', [
                'user_id' => $userId,
                'login' => $user->login,
                'permission' => $permission,
            ]);
            return true;
        }

        // Get cached permissions
        $permissions = $this->getUserPermissions($userId, $userType);

        // Exact match
        if (in_array($permission, $permissions, true)) {
            return true;
        }

        // Wildcard match
        foreach ($permissions as $userPermission) {
            if ($this->matchesWildcard($userPermission, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has ANY of the given permissions
     *
     * @param int $userId
     * @param string $userType
     * @param array $permissions Array of permissions to check
     * @return bool True if user has at least one permission
     */
    public function hasAnyPermission(int $userId, string $userType, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($userId, $userType, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has ALL of the given permissions
     *
     * @param int $userId
     * @param string $userType
     * @param array $permissions Array of permissions to check
     * @return bool True if user has all permissions
     */
    public function hasAllPermissions(int $userId, string $userType, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($userId, $userType, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear cached permissions for a user
     * Call this when user's role or permissions change
     *
     * @param int $userId
     * @param string $userType
     * @return void
     */
    public function clearUserCache(int $userId, string $userType = 'employee'): void
    {
        $cacheKey = $this->getCacheKey($userId, $userType);
        Cache::forget($cacheKey);

        Log::info('[PermissionCache] Cleared cache for user', [
            'user_id' => $userId,
            'user_type' => $userType,
        ]);
    }

    /**
     * Load permissions from database (without cache)
     *
     * @param int $userId
     * @param string $userType
     * @return array
     */
    private function loadPermissionsFromDatabase(int $userId, string $userType): array
    {
        try {
            $user = $this->getUserModel($userId, $userType);

            if (!$user) {
                Log::warning('[PermissionCache] User not found', [
                    'user_id' => $userId,
                    'user_type' => $userType,
                ]);
                return [];
            }

            // Get permissions based on user type
            if ($userType === 'employee' && method_exists($user, 'getAllPermissions')) {
                $permissions = $user->getAllPermissions();
            } elseif ($userType === 'student' && method_exists($user, 'getPermissions')) {
                $permissions = $user->getPermissions();
            } else {
                $permissions = [];
            }

            Log::info('[PermissionCache] Loaded permissions from DB', [
                'user_id' => $userId,
                'user_type' => $userType,
                'permission_count' => count($permissions),
            ]);

            return $permissions;
        } catch (\Throwable $e) {
            Log::error('[PermissionCache] Failed to load permissions', [
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Get user model instance
     *
     * @param int $userId
     * @param string $userType
     * @return EAdmin|EStudent|null
     */
    private function getUserModel(int $userId, string $userType)
    {
        try {
            if ($userType === 'employee') {
                return EAdmin::with('role')->find($userId);
            } elseif ($userType === 'student') {
                return EStudent::find($userId);
            }

            return null;
        } catch (\Throwable $e) {
            Log::error('[PermissionCache] Failed to get user model', [
                'user_id' => $userId,
                'user_type' => $userType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if a wildcard permission matches a specific permission
     *
     * Examples:
     * - 'admin.*' matches 'admin.view'
     * - 'teacher.*' matches 'teacher.dashboard.view'
     * - 'teacher.dashboard.*' matches 'teacher.dashboard.view'
     *
     * @param string $wildcardPermission Permission with wildcard (e.g., 'admin.*')
     * @param string $specificPermission Permission to check (e.g., 'admin.view')
     * @return bool
     */
    private function matchesWildcard(string $wildcardPermission, string $specificPermission): bool
    {
        // If no wildcard, no match
        if (strpos($wildcardPermission, '*') === false) {
            return false;
        }

        // Convert wildcard pattern to regex
        // 'admin.*' → '/^admin\..+$/'
        $pattern = '/^' . str_replace(['.', '*'], ['\.', '.+'], $wildcardPermission) . '$/';

        return (bool) preg_match($pattern, $specificPermission);
    }

    /**
     * Generate cache key for user permissions
     *
     * @param int $userId
     * @param string $userType
     * @return string
     */
    private function getCacheKey(int $userId, string $userType): string
    {
        return "{$this->cachePrefix}:{$userType}:{$userId}";
    }

    /**
     * Get cache statistics for monitoring
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        // This would require Redis commands to get stats
        // For now, return basic info
        return [
            'ttl_seconds' => $this->cacheTTL,
            'ttl_minutes' => $this->cacheTTL / 60,
            'cache_driver' => config('cache.default'),
        ];
    }

    /**
     * Warm up cache for a user
     * Useful after role changes or login
     *
     * @param int $userId
     * @param string $userType
     * @return void
     */
    public function warmUpCache(int $userId, string $userType = 'employee'): void
    {
        $this->clearUserCache($userId, $userType);
        $this->getUserPermissions($userId, $userType);

        Log::info('[PermissionCache] Cache warmed up', [
            'user_id' => $userId,
            'user_type' => $userType,
        ]);
    }
}
