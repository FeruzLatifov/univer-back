<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exam Student Model (Imtihon natijalari)
 *
 * @property int $id
 * @property int $_exam
 * @property int $_student
 * @property float $score
 * @property int $max_score
 * @property string $grade
 * @property string $letter_grade
 * @property boolean $passed
 * @property boolean $attended
 * @property boolean $active
 */
class EExamStudent extends Model
{
    protected $table = 'e_exam_student';

    protected $fillable = [
        '_exam',
        '_student',
        'score',
        'max_score',
        'grade',
        'letter_grade',
        'passed',
        'attended',
        'comment',
        'graded_at',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'passed' => 'boolean',
        'attended' => 'boolean',
        'score' => 'float',
        'max_score' => 'integer',
        'graded_at' => 'datetime',
    ];

    /**
     * Exam relationship
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(EExam::class, '_exam');
    }

    /**
     * Student relationship
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(EStudent::class, '_student');
    }

    /**
     * Get percentage
     */
    public function getPercentageAttribute(): float
    {
        if (!$this->max_score || $this->max_score == 0) {
            return 0;
        }
        return ($this->score / $this->max_score) * 100;
    }

    /**
     * Calculate and set letter grade based on percentage
     */
    public function calculateLetterGrade(): string
    {
        $percentage = $this->percentage;

        if ($percentage >= 86) return 'A';
        if ($percentage >= 71) return 'B';
        if ($percentage >= 56) return 'C';
        if ($percentage >= 41) return 'D';
        if ($percentage >= 31) return 'E';
        return 'F';
    }

    /**
     * Calculate numeric grade (5-point system)
     */
    public function calculateNumericGrade(): string
    {
        $percentage = $this->percentage;

        if ($percentage >= 86) return '5';
        if ($percentage >= 71) return '4';
        if ($percentage >= 56) return '3';
        return '2';
    }
}
