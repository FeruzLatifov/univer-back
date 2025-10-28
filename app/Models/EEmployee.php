<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EEmployee extends Model
{
    protected $table = 'e_employee';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'first_name',
        'second_name',
        'third_name',
        'birth_date',
        'employee_id_number',
        '_gender',
        '_country',
        'passport_number',
        'passport_pin',
        'hire_date',
        'image',
        'active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'hire_date' => 'date',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getFullNameAttribute()
    {
        return trim("{$this->second_name} {$this->first_name} {$this->third_name}");
    }

    public function admin()
    {
        return $this->hasOne(EAdmin::class, '_employee', 'id');
    }

    public function meta()
    {
        return $this->hasOne(EEmployeeMeta::class, '_employee', 'id')
            ->where('active', true)
            ->latest('updated_at');
    }
}
