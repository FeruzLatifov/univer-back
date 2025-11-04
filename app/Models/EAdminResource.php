<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * EAdminResource Model
 *
 * Maps to e_admin_resource table from Yii2 system
 * Represents URL paths/resources that can be accessed with permissions
 *
 * @property int $id
 * @property string $path URL path (e.g., 'student/student')
 * @property string $name Resource name
 * @property string|null $group Resource group (e.g., 'student', 'employee')
 * @property string|null $comment Description
 * @property bool $active Is resource active
 * @property bool $skip Skip permission check (public resource)
 * @property bool $login Requires login
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class EAdminResource extends Model
{
    protected $table = 'e_admin_resource';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'path',
        'name',
        'group',
        'comment',
        'active',
        'skip',
        'login',
    ];

    protected $casts = [
        'active' => 'boolean',
        'skip' => 'boolean',
        'login' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Model lifecycle events for automatic cache invalidation
     *
     * When resources are modified, all menu caches must be cleared
     * because resource changes affect permissions for all users
     */
    protected static function booted()
    {
        // When resource is updated (path, name, active status changed)
        static::updated(function ($resource) {
            Log::info("[EAdminResource] Resource updated, clearing all menu caches", [
                'resource_id' => $resource->id,
                'resource_path' => $resource->path,
            ]);

            self::invalidateAllMenuCaches();
        });

        // When resource is deleted
        static::deleted(function ($resource) {
            Log::info("[EAdminResource] Resource deleted, clearing all menu caches", [
                'resource_id' => $resource->id,
                'resource_path' => $resource->path,
            ]);

            self::invalidateAllMenuCaches();
        });

        // When resource is created (less common, but still important)
        static::created(function ($resource) {
            Log::info("[EAdminResource] Resource created, clearing all menu caches", [
                'resource_id' => $resource->id,
                'resource_path' => $resource->path,
            ]);

            self::invalidateAllMenuCaches();
        });
    }

    /**
     * Invalidate all menu caches system-wide
     *
     * This is a "nuclear option" - clears ALL menu caches
     * Use when resources are modified since they affect all users
     *
     * TODO: Consider using cache tags for more granular invalidation
     */
    protected static function invalidateAllMenuCaches()
    {
        // Clear all menu-related cache keys by pattern
        // Pattern: menu:*

        // For file/redis cache without tag support, we use prefix-based clearing
        $locales = ['uz', 'oz', 'ru', 'en'];
        $clearedCount = 0;

        // Get all users to clear their menu caches
        $users = \App\Models\EAdmin::all();

        foreach ($users as $user) {
            if ($user->_role) {
                foreach ($locales as $locale) {
                    // Legacy key
                    $legacyKey = "menu:user:{$user->id}:locale:{$locale}";
                    if (Cache::forget($legacyKey)) {
                        $clearedCount++;
                    }

                    // New key with role ID
                    $newKey = "menu:user:{$user->id}:role:{$user->_role}:locale:{$locale}";
                    if (Cache::forget($newKey)) {
                        $clearedCount++;
                    }
                }

                // Clear resource cache for user's role
                $resourceKey = "menu:resources:role:{$user->_role}";
                Cache::forget($resourceKey);
            }
        }

        Log::info("[EAdminResource] All menu caches cleared", [
            'total_users' => $users->count(),
            'cache_keys_cleared' => $clearedCount,
        ]);
    }

    /**
     * Get roles that have access to this resource
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            EAdminRole::class,
            'e_admin_role_resource',
            '_resource',
            '_role',
            'id',
            'id'
        );
    }

    /**
     * Check if a role has access to this resource
     */
    public function isAccessibleByRole(EAdminRole $role): bool
    {
        // Skip permission check if resource is public
        if ($this->skip) {
            return true;
        }

        // Super admin has access to everything
        if ($role->code === 'super_admin') {
            return true;
        }

        // Check if role has this resource
        return $this->roles()->where('e_admin_role.id', $role->id)->exists();
    }

    /**
     * Scope: Only active resources
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Resources by group
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: Public resources (skip=true)
     */
    public function scopePublic($query)
    {
        return $query->where('skip', true);
    }

    /**
     * Get resource by path
     */
    public static function findByPath(string $path): ?self
    {
        return static::where('path', trim($path, '/'))->first();
    }

    /**
     * Get all resources accessible by a role
     */
    public static function getAccessibleByRole(EAdminRole $role): \Illuminate\Database\Eloquent\Collection
    {
        // Super admin gets all active resources
        if ($role->code === 'super_admin') {
            return static::active()->get();
        }

        // Get resources linked to this role
        return static::active()
            ->whereHas('roles', function ($query) use ($role) {
                $query->where('e_admin_role.id', $role->id);
            })
            ->get();
    }

    /**
     * Convert to Spatie permission name
     * Format: group.action (e.g., 'student.view')
     */
    public function toPermissionName(): string
    {
        // 1) Precise mapping first (avoid over-broad 'employee.view', etc.)
        $path = trim($this->path, '/');
        $pathMap = config('permission_aliases.path_to_dot', []);
        if (isset($pathMap[$path]) && is_string($pathMap[$path])) {
            return $pathMap[$path];
        }

        // 2) Fallback mapping: 'resource.action' with basic action normalization
        $parts = explode('/', $path);

        if (count($parts) === 1) {
            return $path . '.view';
        }

        $resource = $parts[0];
        $action = $parts[1] ?? 'view';

        // Map actions
        $actionMap = [
            'index' => 'view',
            'view' => 'view',
            'create' => 'create',
            'edit' => 'edit',
            'update' => 'edit',
            'delete' => 'delete',
        ];

        $mappedAction = $actionMap[$action] ?? 'view';

        return "{$resource}.{$mappedAction}";
    }

    /**
     * Get display name (for API responses)
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->path;
    }
}
