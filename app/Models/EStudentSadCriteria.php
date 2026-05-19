<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EStudentSadCriteria extends Model
{
    protected $table = 'e_student_sad_criteria';

    protected $fillable = [
        '_category',
        'name',
        'min_value',
        'max_value',
        'point',
        'position',
        'input_by',
        'active',
    ];

    protected $casts = [
        'active'    => 'boolean',
        'point'     => 'float',
        'min_value' => 'float',
        'max_value' => 'float',
        'position'  => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(EStudentSadCategory::class, '_category');
    }
}
