<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EStructure extends Model
{
    protected $table = 'e_structure';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'code',
        '_parent',
        '_structure_type',
        '_translations',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        '_translations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parent()
    {
        return $this->belongsTo(EStructure::class, '_parent', 'id');
    }

    public function children()
    {
        return $this->hasMany(EStructure::class, '_parent', 'id');
    }
}


