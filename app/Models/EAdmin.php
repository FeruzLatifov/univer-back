<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

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
        return [
            'type' => 'admin',
            'role' => $this->_role,
        ];
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
