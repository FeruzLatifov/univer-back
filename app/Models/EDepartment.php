<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Translatable;

class EDepartment extends Model
{
    use Translatable;

    protected $table = 'e_department';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'code',
        '_structure_type',
        '_parent',
        '_translations',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        '_translations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Translatable attributes
     */
    protected $translatable = ['name'];

    public function parent()
    {
        return $this->belongsTo(EDepartment::class, '_parent', 'id');
    }

    public function children()
    {
        return $this->hasMany(EDepartment::class, '_parent', 'id');
    }

    public function groups()
    {
        return $this->hasMany(EGroup::class, '_department', 'id');
    }

    public function specialties()
    {
        return $this->hasMany(ESpecialty::class, '_department', 'id');
    }
}
