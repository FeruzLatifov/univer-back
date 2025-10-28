<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HEducationType extends Model
{
    protected $table = 'h_education_type';
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
