<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EAdminRole extends Model
{
    protected $table = 'e_admin_role';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        '_translations',
        'status',
        'position',
        'active',
        'guard_name',      // Hybrid: Laravel guard system
        'spatie_enabled',  // Hybrid: gradual migration flag
    ];

    protected $casts = [
        'active' => 'boolean',
        '_translations' => 'array',
        'spatie_enabled' => 'boolean', // Hybrid: cast to boolean
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Model lifecycle events for automatic cache invalidation
     *
     * Automatically clears menu cache when role is updated or deleted
     * This ensures users always see the correct menu after permission changes
     */
    protected static function booted()
    {
        // When role is updated (name, status, permissions changed)
        static::updated(function ($role) {
            Log::info("[EAdminRole] Role updated, invalidating menu caches", [
                'role_id' => $role->id,
                'role_code' => $role->code,
            ]);

            self::invalidateMenuCaches($role);
        });

        // When role is deleted
        static::deleted(function ($role) {
            Log::info("[EAdminRole] Role deleted, invalidating menu caches", [
                'role_id' => $role->id,
                'role_code' => $role->code,
            ]);

            self::invalidateMenuCaches($role);
        });
    }

    /**
     * Invalidate all menu caches for users with this role
     *
     * Clears cache for all locales and all users with this role
     * Uses both legacy (without role ID) and new (with role ID) cache keys
     */
    protected static function invalidateMenuCaches($role)
    {
        $locales = ['uz', 'oz', 'ru', 'en'];
        $clearedCount = 0;

        // Get all users with this role
        $users = $role->admins()->get();

        foreach ($users as $user) {
            foreach ($locales as $locale) {
                // Legacy cache key (backward compatibility)
                $legacyKey = "menu:user:{$user->id}:locale:{$locale}";
                if (Cache::forget($legacyKey)) {
                    $clearedCount++;
                }

                // New cache key (with role ID)
                $newKey = "menu:user:{$user->id}:role:{$role->id}:locale:{$locale}";
                if (Cache::forget($newKey)) {
                    $clearedCount++;
                }
            }
        }

        // Also clear resource cache for this role
        $resourceCacheKey = "menu:resources:role:{$role->id}";
        Cache::forget($resourceCacheKey);

        Log::info("[EAdminRole] Menu cache invalidation completed", [
            'role_id' => $role->id,
            'affected_users' => $users->count(),
            'cache_keys_cleared' => $clearedCount,
        ]);
    }

    public function getDisplayNameAttribute(): string
    {
        $translations = $this->_translations ?? [];
        $locale = app()->getLocale();
        
        // Build candidate keys matching Yii2 format: name_uz, name_oz, name_ru, name_en
        $candidates = [];
        if ($locale) {
            $base = substr($locale, 0, 2); // uz from uz-UZ
            $candidates[] = "name_{$base}"; // name_uz
            $candidates[] = "name_" . strtoupper($base); // name_UZ
            $candidates[] = "name_{$locale}"; // name_uz-UZ
            $candidates[] = $locale; // uz
            $candidates[] = $base; // uz
        }
        
        // Try candidates
        foreach ($candidates as $candidate) {
            if (isset($translations[$candidate]) && is_string($translations[$candidate]) && trim($translations[$candidate]) !== '') {
                return $translations[$candidate];
            }
        }
        
        // Fallback: any non-empty translation value
        if (is_array($translations)) {
            foreach ($translations as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }
            }
        }
        
        return $this->name ?? $this->code ?? '';
    }

    public function admins()
    {
        return $this->hasMany(EAdmin::class, '_role', 'id');
    }

    public function resources()
    {
        return $this->belongsToMany(
            EAdminResource::class,
            'e_admin_role_resource',
            '_role',
            '_resource',
            'id',
            'id'
        );
    }

    /**
     * Get all permissions for this role
     * Returns array of permission names (e.g., ['student.view', 'employee.create'])
     */
    public function getPermissions(): array
    {
        // Super admin has all permissions
        if ($this->code === 'super_admin') {
            return ['*']; // Wildcard for all permissions
        }

        // Get permissions from linked resources (active)
        $linkedResources = $this->resources()->active()->get();

        // Public resources (skip = true) are accessible for everyone
        $publicResources = EAdminResource::active()->public()->get();

        $all = $linkedResources->merge($publicResources);

        // Build union of dot-notation permissions AND legacy route paths
        $dotPermissions = $all->map(function ($resource) {
            return $resource->toPermissionName();
        });

        $routePermissions = $all->map(function ($resource) {
            return trim($resource->path, '/');
        });

        return $dotPermissions
            ->merge($routePermissions)
            ->filter(fn ($p) => is_string($p) && $p !== '')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->code === 'super_admin') {
            return true;
        }

        $permissions = $this->getPermissions();

        // Check wildcard
        if (in_array('*', $permissions)) {
            return true;
        }

        // Exact match
        if (in_array($permission, $permissions)) {
            return true;
        }

        // Check wildcard patterns (e.g., 'student.*' matches 'student.view')
        foreach ($permissions as $p) {
            if (str_ends_with($p, '.*')) {
                $prefix = substr($p, 0, -2);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if role can access a URL path
     */
    public function canAccessPath(string $path): bool
    {
        $path = trim($path, '/');

        // Super admin can access everything
        if ($this->code === 'super_admin') {
            return true;
        }

        // Ajax requests are allowed
        if (str_starts_with($path, 'ajax')) {
            return true;
        }

        // Check if resource exists and is accessible
        $resource = EAdminResource::findByPath($path);

        if (!$resource) {
            return false;
        }

        return $resource->isAccessibleByRole($this);
    }
}
