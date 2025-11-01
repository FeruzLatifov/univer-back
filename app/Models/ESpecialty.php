<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ESpecialty extends Model
{
    protected $table = 'e_specialty';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        '_department',
        '_education_type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function department()
    {
        return $this->belongsTo(EDepartment::class, '_department', 'id');
    }

    public function groups()
    {
        return $this->hasMany(EGroup::class, '_specialty', 'id');
    }
}
