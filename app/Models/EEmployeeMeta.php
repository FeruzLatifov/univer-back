<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EEmployeeMeta extends Model
{
    protected $table = 'e_employee_meta';
    public $timestamps = false;

    protected $fillable = [
        '_employee',
        '_department',
        '_position',
        '_employment_form',
        '_employment_staff',
        '_employee_status',
        'contract_number',
        'contract_date',
        'active',
        'employee_id_number',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(EEmployee::class, '_employee', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(EDepartment::class, '_department', 'id');
    }
}


