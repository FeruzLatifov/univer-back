<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * EStudentRole Model
 *
 * Hybrid permission system for students (new in Laravel)
 * Mirrors e_admin_role structure but for student guard
 *
 * @property int $id
 * @property string $code Unique role code (e.g., 'student', 'senior_student')
 * @property string $name Role name
 * @property string $status Role status (enable/disable)
 * @property string $guard_name Laravel guard (always 'student-api')
 * @property bool $spatie_enabled Flag for gradual migration
 * @property int|null $parent Parent role ID (hierarchical)
 * @property array|null $_options Additional options (JSON)
 * @property array|null $_translations Translations (JSON)
 * @property int $position Display position
 */
class EStudentRole extends Model
{
    protected $table = 'e_student_role';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'status',
        'guard_name',
        'spatie_enabled',
        'parent',
        '_options',
        '_translations',
        'position',
    ];

    protected $casts = [
        'spatie_enabled' => 'boolean',
        '_options' => 'array',
        '_translations' => 'array',
        'position' => 'integer',
        'parent' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'guard_name' => 'student-api',
        'spatie_enabled' => false,
        'status' => 'enable',
        'position' => 0,
    ];

    /**
     * Get students with this role
     */
    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'e_student_roles',
            '_role',
            '_student',
            'id',
            'id'
        );
    }

    /**
     * Get resources/permissions for this role
     */
    public function resources()
    {
        return $this->belongsToMany(
            EStudentResource::class,
            'e_student_role_resource',
            '_role',
            '_resource',
            'id',
            'id'
        );
    }

    /**
     * Get parent role (hierarchical)
     */
    public function parentRole()
    {
        return $this->belongsTo(EStudentRole::class, 'parent', 'id');
    }

    /**
     * Get child roles
     */
    public function childRoles()
    {
        return $this->hasMany(EStudentRole::class, 'parent', 'id');
    }

    /**
     * Get display name with translations
     */
    public function getDisplayNameAttribute(): string
    {
        $translations = $this->_translations ?? [];
        $locale = app()->getLocale();

        $candidates = [];
        if ($locale) {
            $base = substr($locale, 0, 2);
            $candidates[] = "name_{$base}";
            $candidates[] = "name_" . strtoupper($base);
            $candidates[] = "name_{$locale}";
            $candidates[] = $locale;
            $candidates[] = $base;
        }

        foreach ($candidates as $candidate) {
            if (isset($translations[$candidate]) && is_string($translations[$candidate]) && trim($translations[$candidate]) !== '') {
                return $translations[$candidate];
            }
        }

        if (is_array($translations)) {
            foreach ($translations as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }
            }
        }

        return $this->name ?? $this->code ?? '';
    }

    /**
     * Get all permissions for this role
     */
    public function getPermissions(): array
    {
        $linkedResources = $this->resources()->active()->get();
        $publicResources = EStudentResource::active()->public()->get();
        $all = $linkedResources->merge($publicResources);

        $permissions = $all->map(function ($resource) {
            return $resource->permission_name ?? str_replace('/', '-', $resource->path);
        });

        return $permissions
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
        $permissions = $this->getPermissions();

        if (in_array('*', $permissions)) {
            return true;
        }

        if (in_array($permission, $permissions)) {
            return true;
        }

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
}
