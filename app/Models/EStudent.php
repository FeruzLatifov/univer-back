<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class EStudent extends Authenticatable implements JWTSubject
{
    protected $table = 'e_student';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'first_name',
        'second_name',
        'third_name',
        'birth_date',
        'student_id_number',
        '_gender',
        '_country',
        '_province',
        '_district',
        'passport_number',
        'passport_pin',
        'phone_number',
        'email',
        'password',
        'image',
        'active',
    ];

    protected $hidden = [
        'password',
        'passport_pin',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'active' => 'boolean',
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
            'type' => 'student',
            'student_id' => $this->student_id_number,
        ];
    }

    // Accessors
    public function getFullNameAttribute()
    {
        return trim("{$this->second_name} {$this->first_name} {$this->third_name}");
    }

    // Relationships
    public function meta()
    {
        return $this->hasOne(EStudentMeta::class, '_student', 'id')
            ->where('active', true)
            ->latest('updated_at');
    }

    public function allMeta()
    {
        return $this->hasMany(EStudentMeta::class, '_student', 'id');
    }

    public function country()
    {
        return $this->belongsTo(HCountry::class, '_country', 'code');
    }

    public function gender()
    {
        return $this->belongsTo(HGender::class, '_gender', 'code');
    }
}
