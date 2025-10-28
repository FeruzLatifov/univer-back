<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HCountry extends Model
{
    protected $table = 'h_country';
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
