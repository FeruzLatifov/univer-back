<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * EAdmin Model
 *
 * Uses EXISTING Yii2 database tables:
 * - e_admin (users)
 * - e_admin_role (roles)
 * - e_admin_resource (permissions/resources)
 * - e_admin_role_resource (role-permission pivot)
 *
 * NO NEW TABLES - 100% Yii2 compatible
 */
class EAdmin extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'e_admin';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'login',
        'email',
        'password',
        'full_name',
        '_employee',
        '_role',
        'status',
        'language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // JWT methods
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        // Keep JWT lightweight: do not embed large permission arrays.
        // Authorization is enforced server-side; frontend fetches permissions via API when needed.
        return [
            'type' => 'admin',
            'role' => $this->_role,
        ];
    }

    /**
     * Get all permissions for this admin user
     */
    public function getAllPermissions(): array
    {
        if (!$this->role) {
            return [];
        }

        return $this->role->getPermissions();
    }

    /**
     * Check if admin has a specific permission
     *
     * Uses ONLY Yii2 e_admin_resource system
     * NO Spatie - pure Yii2 compatibility
     *
     * Permission format: 'student.view' or 'student/student' (Yii2 path)
     */
    public function hasPermission(string $permission): bool
    {
        // Super admins and tech admins have all permissions
        if ($this->login === 'admin' || $this->login === 'techadmin') {
            return true;
        }

        // Check if user has role
        if (!$this->role) {
            return false;
        }

        // Use Yii2 role permission check
        return $this->role->hasPermission($permission);
    }

    /**
     * Check if admin can access a URL path
     */
    public function canAccessPath(string $path): bool
    {
        $path = trim($path, '/');

        // Super admins and tech admins can access everything
        if ($this->login === 'admin' || $this->login === 'techadmin') {
            return true;
        }

        // Ajax requests are allowed
        if (str_starts_with($path, 'ajax')) {
            return true;
        }

        if (!$this->role) {
            return false;
        }

        return $this->role->canAccessPath($path);
    }

    // Relationships
    public function employee()
    {
        return $this->belongsTo(EEmployee::class, '_employee', 'id');
    }

    public function role()
    {
        return $this->belongsTo(EAdminRole::class, '_role', 'id');
    }

    public function structure()
    {
        return $this->belongsTo(EStructure::class, '_structure', 'id');
    }

    public function roles()
    {
        return $this->belongsToMany(
            EAdminRole::class,
            'e_admin_roles',
            '_admin',
            '_role'
        );
    }
}
