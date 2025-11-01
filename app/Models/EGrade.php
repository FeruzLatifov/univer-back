<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Grade Model (Baholar)
 *
 * Stores student grades for subjects
 *
 * @property int $id
 * @property int $_student Student ID
 * @property int $_subject Subject ID
 * @property int $_education_year Education year ID
 * @property int $_semester Semester
 * @property string $_grade_type Grade type (midterm, final, current)
 * @property float $grade Numeric grade
 * @property int|null $max_grade Maximum possible grade
 * @property string|null $comment Teacher's comment
 * @property int|null $_employee Teacher ID
 * @property boolean $active Active status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class EGrade extends Model
{
    protected $table = 'e_performance';  // Note: table might be named e_performance or e_grade

    protected $fillable = [
        '_student',
        '_subject',
        '_education_year',
        '_semester',
        '_grade_type',
        'grade',
        'max_grade',
        'comment',
        '_employee',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'grade' => 'float',
        'max_grade' => 'integer',
        '_semester' => 'integer',
    ];

    // Grade type constants
    const TYPE_CURRENT = '11';    // Joriy nazorat
    const TYPE_MIDTERM = '12';    // Oraliq nazorat
    const TYPE_FINAL = '13';      // Yakuniy nazorat
    const TYPE_OVERALL = '14';    // Umumiy baho

    /**
     * Student relationship
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(EStudent::class, '_student');
    }

    /**
     * Subject relationship
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(ESubject::class, '_subject');
    }

    /**
     * Teacher relationship
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(EEmployee::class, '_employee');
    }

    /**
     * Scope: By student
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('_student', $studentId);
    }

    /**
     * Scope: By subject
     */
    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('_subject', $subjectId);
    }

    /**
     * Scope: By semester
     */
    public function scopeBySemester($query, $semester)
    {
        return $query->where('_semester', $semester);
    }

    /**
     * Scope: By grade type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('_grade_type', $type);
    }

    /**
     * Scope: Current grades
     */
    public function scopeCurrent($query)
    {
        return $query->where('_grade_type', self::TYPE_CURRENT);
    }

    /**
     * Scope: Midterm grades
     */
    public function scopeMidterm($query)
    {
        return $query->where('_grade_type', self::TYPE_MIDTERM);
    }

    /**
     * Scope: Final grades
     */
    public function scopeFinal($query)
    {
        return $query->where('_grade_type', self::TYPE_FINAL);
    }

    /**
     * Get grade type name
     */
    public function getTypeNameAttribute(): string
    {
        $types = [
            self::TYPE_CURRENT => 'Joriy nazorat',
            self::TYPE_MIDTERM => 'Oraliq nazorat',
            self::TYPE_FINAL => 'Yakuniy nazorat',
            self::TYPE_OVERALL => 'Umumiy baho',
        ];

        return $types[$this->_grade_type] ?? 'Noma\'lum';
    }

    /**
     * Get percentage
     */
    public function getPercentageAttribute(): float
    {
        if (!$this->max_grade || $this->max_grade == 0) {
            return 0;
        }

        return ($this->grade / $this->max_grade) * 100;
    }

    /**
     * Get letter grade
     */
    public function getLetterGradeAttribute(): string
    {
        $percentage = $this->percentage;

        if ($percentage >= 86) return 'A';
        if ($percentage >= 71) return 'B';
        if ($percentage >= 56) return 'C';
        if ($percentage >= 41) return 'D';
        if ($percentage >= 31) return 'E';
        return 'F';
    }
}
