<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Exam Model
 *
 * @property int $id
 * @property int $_subject
 * @property int $_group
 * @property int $_semester
 * @property string $_exam_type
 * @property string $exam_date
 * @property int $_employee
 * @property int $duration
 * @property int $max_score
 * @property string $status
 * @property boolean $active
 */
class EExam extends Model
{
    protected $table = 'e_exam';

    protected $fillable = [
        '_subject',
        '_group',
        '_education_year',
        '_semester',
        '_exam_type',
        'exam_date',
        '_employee',
        '_auditorium',
        'duration',
        'max_score',
        'status',
        'notes',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'exam_date' => 'datetime',
        'duration' => 'integer',
        'max_score' => 'integer',
        '_semester' => 'integer',
    ];

    // Exam type constants
    const TYPE_MIDTERM = '11';
    const TYPE_FINAL = '12';

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Subject relationship
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(ESubject::class, '_subject');
    }

    /**
     * Group relationship
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(EGroup::class, '_group');
    }

    /**
     * Teacher relationship
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(EEmployee::class, '_employee');
    }

    /**
     * Exam results
     */
    public function results(): HasMany
    {
        return $this->hasMany(EExamStudent::class, '_exam');
    }

    /**
     * Scope: Active exams
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: By teacher
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('_employee', $teacherId);
    }

    /**
     * Scope: Upcoming exams
     */
    public function scopeUpcoming($query)
    {
        return $query->where('exam_date', '>', now())
            ->where('status', self::STATUS_SCHEDULED);
    }

    /**
     * Get exam type name
     */
    public function getTypeNameAttribute(): string
    {
        return $this->_exam_type === self::TYPE_MIDTERM ? 'Oraliq nazorat' : 'Yakuniy nazorat';
    }
}
