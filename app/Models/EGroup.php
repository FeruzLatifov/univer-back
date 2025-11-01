<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Translatable;

class EGroup extends Model
{
    use Translatable;

    protected $table = 'e_group';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'code',
        '_department',
        '_specialty',
        '_education_type',
        '_education_form',
        '_education_year',
        '_level',
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

    public function students()
    {
        return $this->hasMany(EStudentMeta::class, '_group', 'id')
            ->where('active', true);
    }

    public function specialty()
    {
        return $this->belongsTo(ESpecialty::class, '_specialty', 'id');
    }

    public function department()
    {
        return $this->belongsTo(EDepartment::class, '_department', 'id');
    }
}
