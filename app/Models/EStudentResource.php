<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * EStudentResource Model
 *
 * Hybrid permission system for students (new in Laravel)
 * Mirrors e_admin_resource structure but for student guard
 *
 * @property int $id
 * @property string $path Yii2 path-based permission (e.g., 'student/grades')
 * @property string|null $permission_name Laravel name-based permission (e.g., 'student-grades')
 * @property string $guard_name Laravel guard (always 'student-api')
 * @property bool $spatie_enabled Flag for gradual migration
 * @property string $name Resource display name
 * @property string $group Resource group
 * @property string|null $comment Description
 * @property bool $active Is resource active
 * @property bool $login Requires login
 * @property bool $skip Skip permission check (public)
 * @property array|null $_options Additional options (JSON)
 */
class EStudentResource extends Model
{
    protected $table = 'e_student_resource';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'path',
        'permission_name',
        'guard_name',
        'spatie_enabled',
        'name',
        'group',
        'comment',
        'active',
        'login',
        'skip',
        '_options',
    ];

    protected $casts = [
        'active' => 'boolean',
        'login' => 'boolean',
        'skip' => 'boolean',
        'spatie_enabled' => 'boolean',
        '_options' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'guard_name' => 'student-api',
        'spatie_enabled' => false,
        'active' => true,
        'login' => false,
        'skip' => false,
    ];

    /**
     * Get roles that have access to this resource
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            EStudentRole::class,
            'e_student_role_resource',
            '_resource',
            '_role',
            'id',
            'id'
        );
    }

    /**
     * Check if a role has access to this resource
     */
    public function isAccessibleByRole(EStudentRole $role): bool
    {
        // Skip permission check if resource is public
        if ($this->skip) {
            return true;
        }

        // Check if role has this resource
        return $this->roles()->where('e_student_role.id', $role->id)->exists();
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
    public static function getAccessibleByRole(EStudentRole $role): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->whereHas('roles', function ($query) use ($role) {
                $query->where('e_student_role.id', $role->id);
            })
            ->get();
    }

    /**
     * Get permission name (Laravel-compatible)
     */
    public function getPermissionNameAttribute($value): string
    {
        // Return permission_name if set, otherwise convert path to name
        return $value ?? str_replace('/', '-', $this->path);
    }

    /**
     * Get display name
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->path;
    }
}
