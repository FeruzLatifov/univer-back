<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HGender extends Model
{
    protected $table = 'h_gender';
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
