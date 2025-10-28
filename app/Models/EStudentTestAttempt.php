<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EStudentTestAttempt Model
 *
 * Represents a student's attempt at a test
 *
 * Status values:
 * - started: Attempt created but not yet begun
 * - in_progress: Student is actively taking the test
 * - submitted: Test submitted, awaiting grading
 * - graded: Test fully graded
 * - abandoned: Test started but not submitted (timeout/abandoned)
 */
class EStudentTestAttempt extends Model
{
    protected $table = 'e_student_test_attempt';

    const STATUS_STARTED = 'started';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_SUBMITTED = 'submitted';
    const STATUS_GRADED = 'graded';
    const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        '_test',
        '_student',
        'attempt_number',
        'status',
        'started_at',
        'submitted_at',
        'graded_at',
        'duration_seconds',
        'total_score',
        'max_score',
        'percentage',
        'passed',
        'auto_graded_score',
        'manual_graded_score',
        'feedback',
        'ip_address',
        'user_agent',
        'active',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'duration_seconds' => 'integer',
        'total_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'percentage' => 'decimal:2',
        'passed' => 'boolean',
        'auto_graded_score' => 'decimal:2',
        'manual_graded_score' => 'decimal:2',
        'active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Attempt belongs to a test
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(ESubjectTest::class, '_test');
    }

    /**
     * Attempt belongs to a student
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(EStudent::class, '_student');
    }

    /**
     * Attempt has many answers
     */
    public function answers(): HasMany
    {
        return $this->hasMany(EStudentTestAnswer::class, '_attempt');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Only active attempts
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Submitted attempts
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * Scope: Graded attempts
     */
    public function scopeGraded($query)
    {
        return $query->where('status', self::STATUS_GRADED);
    }

    /**
     * Scope: Pending grading
     */
    public function scopePendingGrading($query)
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }

    /**
     * Scope: In progress attempts
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    // ==========================================
    // ATTRIBUTES
    // ==========================================

    /**
     * Get percentage attribute
     */
    public function getPercentageAttribute($value): ?float
    {
        if ($value !== null) {
            return $value;
        }

        if ($this->total_score !== null && $this->max_score > 0) {
            return round(($this->total_score / $this->max_score) * 100, 2);
        }

        return null;
    }

    /**
     * Check if passed
     */
    public function getPassedAttribute($value): ?bool
    {
        if ($value !== null) {
            return $value;
        }

        if ($this->percentage !== null && $this->test->passing_score !== null) {
            return $this->percentage >= $this->test->passing_score;
        }

        return null;
    }

    /**
     * Get formatted duration
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '0 daqiqa';
        }

        $hours = floor($this->duration_seconds / 3600);
        $minutes = floor(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        $parts = [];
        if ($hours > 0) $parts[] = "{$hours} soat";
        if ($minutes > 0) $parts[] = "{$minutes} daqiqa";
        if ($seconds > 0 && $hours === 0) $parts[] = "{$seconds} soniya";

        return implode(' ', $parts) ?: '0 daqiqa';
    }

    /**
     * Get letter grade
     */
    public function getLetterGradeAttribute(): ?string
    {
        if ($this->percentage === null) {
            return null;
        }

        if ($this->percentage >= 86) return 'A';
        if ($this->percentage >= 71) return 'B';
        if ($this->percentage >= 56) return 'C';
        if ($this->percentage >= 41) return 'D';
        if ($this->percentage >= 31) return 'E';
        return 'F';
    }

    /**
     * Get numeric grade (5-point system)
     */
    public function getNumericGradeAttribute(): ?string
    {
        if ($this->percentage === null) {
            return null;
        }

        if ($this->percentage >= 86) return '5';
        if ($this->percentage >= 71) return '4';
        if ($this->percentage >= 56) return '3';
        return '2';
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Calculate and update score
     */
    public function calculateScore(): void
    {
        $autoGraded = 0;
        $manualGraded = 0;
        $maxScore = 0;

        foreach ($this->answers as $answer) {
            $maxScore += $answer->points_possible ?? 0;

            if ($answer->manually_graded) {
                $manualGraded += $answer->points_earned ?? 0;
            } else {
                $autoGraded += $answer->points_earned ?? 0;
            }
        }

        $this->auto_graded_score = $autoGraded;
        $this->manual_graded_score = $manualGraded;
        $this->total_score = $autoGraded + $manualGraded;
        $this->max_score = $maxScore;
        $this->save();
    }

    /**
     * Auto-grade all auto-gradable questions
     */
    public function autoGrade(): void
    {
        foreach ($this->answers as $answer) {
            if (!$answer->manually_graded && $answer->question->can_auto_grade) {
                $answer->autoGrade();
            }
        }

        $this->calculateScore();

        // Check if all questions are graded
        $allGraded = $this->answers->every(function ($answer) {
            return $answer->is_correct !== null || $answer->manually_graded;
        });

        if ($allGraded) {
            $this->status = self::STATUS_GRADED;
            $this->graded_at = now();
            $this->save();
        }
    }

    /**
     * Submit the attempt
     */
    public function submit(): bool
    {
        if ($this->submitted_at) {
            return false; // Already submitted
        }

        $this->submitted_at = now();
        $this->status = self::STATUS_SUBMITTED;

        // Calculate duration
        if ($this->started_at) {
            $this->duration_seconds = $this->started_at->diffInSeconds($this->submitted_at);
        }

        $this->save();

        // Auto-grade
        $this->autoGrade();

        return true;
    }

    /**
     * Check if can retake test
     */
    public function canRetake(): bool
    {
        $attemptCount = self::where('_test', $this->_test)
            ->where('_student', $this->_student)
            ->where('active', true)
            ->whereNotNull('submitted_at')
            ->count();

        return $attemptCount < $this->test->attempt_limit;
    }

    /**
     * Check if requires manual grading
     */
    public function requiresManualGrading(): bool
    {
        return $this->answers()->where('manually_graded', false)
            ->whereHas('question', function ($q) {
                $q->where('question_type', ESubjectTestQuestion::TYPE_ESSAY);
            })
            ->exists();
    }
}
