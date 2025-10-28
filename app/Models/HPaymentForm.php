<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HPaymentForm extends Model
{
    protected $table = 'h_payment_form';
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
