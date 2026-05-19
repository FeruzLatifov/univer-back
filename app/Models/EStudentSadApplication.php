<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EStudentSadApplication extends Model
{
    protected $table = 'e_student_sad_application';

    protected $fillable = [
        '_student',
        '_education_year',
        'name',
        'description',
        'application_date',
        'total_point',
        'status',
    ];

    protected $casts = [
        'total_point'      => 'float',
        'status'           => 'integer',
        'application_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(EStudent::class, '_student');
    }

    public function applicationCriteria(): HasMany
    {
        return $this->hasMany(EStudentSadApplicationCriteria::class, '_application');
    }
}
