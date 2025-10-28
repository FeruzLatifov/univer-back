<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Curriculum Subject Model (O'quv rejasidagi fanlar)
 *
 * Links subjects to curriculums
 *
 * @property int $id
 * @property int $_curriculum Curriculum ID
 * @property int $_subject Subject ID
 * @property int $_semester Semester number
 * @property string|null $_curriculum_subject_type Subject type (majburiy, tanlov)
 * @property int|null $credit_hours Credit hours
 * @property int|null $lecture_hours Lecture hours
 * @property int|null $practice_hours Practice hours
 * @property int|null $lab_hours Laboratory hours
 * @property boolean $active Active status
 */
class ECurriculumSubject extends Model
{
    protected $table = 'e_curriculum_subject';

    public $timestamps = false;

    protected $fillable = [
        '_curriculum',
        '_subject',
        '_semester',
        '_curriculum_subject_type',
        'credit_hours',
        'lecture_hours',
        'practice_hours',
        'lab_hours',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        '_semester' => 'integer',
        'credit_hours' => 'integer',
        'lecture_hours' => 'integer',
        'practice_hours' => 'integer',
        'lab_hours' => 'integer',
    ];

    // Subject type constants
    const TYPE_MANDATORY = '11';  // Majburiy
    const TYPE_ELECTIVE = '12';   // Tanlov

    /**
     * Curriculum relationship
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ECurriculum::class, '_curriculum');
    }

    /**
     * Subject relationship
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(ESubject::class, '_subject');
    }

    /**
     * Scope: By curriculum
     */
    public function scopeByCurriculum($query, $curriculumId)
    {
        return $query->where('_curriculum', $curriculumId);
    }

    /**
     * Scope: By semester
     */
    public function scopeBySemester($query, $semester)
    {
        return $query->where('_semester', $semester);
    }

    /**
     * Scope: Mandatory subjects
     */
    public function scopeMandatory($query)
    {
        return $query->where('_curriculum_subject_type', self::TYPE_MANDATORY);
    }

    /**
     * Scope: Elective subjects
     */
    public function scopeElective($query)
    {
        return $query->where('_curriculum_subject_type', self::TYPE_ELECTIVE);
    }

    /**
     * Get total hours
     */
    public function getTotalHoursAttribute(): int
    {
        return ($this->lecture_hours ?? 0) +
               ($this->practice_hours ?? 0) +
               ($this->lab_hours ?? 0);
    }
}
