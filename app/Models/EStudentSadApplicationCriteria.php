<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EStudentSadApplicationCriteria extends Model
{
    protected $table = 'e_student_sad_application_criteria';

    protected $fillable = [
        '_application',
        '_criteria',
        'point',
        'basis',
        'basis_file',
        'status',
        'reject_comment',
    ];

    protected $casts = [
        'point'  => 'float',
        'status' => 'integer',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(EStudentSadApplication::class, '_application');
    }

    public function criteria(): BelongsTo
    {
        return $this->belongsTo(EStudentSadCriteria::class, '_criteria');
    }
}
