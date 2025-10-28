<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EAdminRole extends Model
{
    protected $table = 'e_admin_role';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        'status',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function admins()
    {
        return $this->hasMany(EAdmin::class, '_role', 'code');
    }

    public function resources()
    {
        return $this->belongsToMany(
            EAdminResource::class,
            'e_admin_role_resource',
            '_role',
            '_resource',
            'code',
            'id'
        );
    }
}
