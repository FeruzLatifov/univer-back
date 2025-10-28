<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * EStudentTestAnswer Model
 *
 * Represents a student's answer to a specific question in a test attempt
 */
class EStudentTestAnswer extends Model
{
    protected $table = 'e_student_test_answer';

    protected $fillable = [
        '_attempt',
        '_question',
        '_answer',
        'answer_text',
        'answer_boolean',
        'selected_answers',
        'points_earned',
        'points_possible',
        'is_correct',
        'manually_graded',
        'graded_by',
        'graded_at',
        'feedback',
        'answered_at',
        'active',
    ];

    protected $casts = [
        'answer_boolean' => 'boolean',
        'points_earned' => 'decimal:2',
        'points_possible' => 'decimal:2',
        'is_correct' => 'boolean',
        'manually_graded' => 'boolean',
        'graded_at' => 'datetime',
        'answered_at' => 'datetime',
        'active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Answer belongs to an attempt
     */
    public function attempt(): BelongsTo
    {
        return $this->belongsTo(EStudentTestAttempt::class, '_attempt');
    }

    /**
     * Answer belongs to a question
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(ESubjectTestQuestion::class, '_question');
    }

    /**
     * Answer belongs to an answer option (for MC only)
     */
    public function answer(): BelongsTo
    {
        return $this->belongsTo(ESubjectTestAnswer::class, '_answer');
    }

    /**
     * Answer graded by employee
     */
    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(EEmployee::class, 'graded_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Only active answers
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Manually graded answers
     */
    public function scopeManuallyGraded($query)
    {
        return $query->where('manually_graded', true);
    }

    /**
     * Scope: Auto graded answers
     */
    public function scopeAutoGraded($query)
    {
        return $query->where('manually_graded', false);
    }

    /**
     * Scope: Correct answers
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    /**
     * Scope: Incorrect answers
     */
    public function scopeIncorrect($query)
    {
        return $query->where('is_correct', false);
    }

    /**
     * Scope: Pending grading (not yet graded)
     */
    public function scopePendingGrading($query)
    {
        return $query->whereNull('is_correct')
            ->whereNull('graded_at');
    }

    // ==========================================
    // ATTRIBUTES
    // ==========================================

    /**
     * Get selected answers as array
     */
    public function getSelectedAnswersArrayAttribute(): array
    {
        if (!$this->selected_answers) {
            return [];
        }

        $decoded = json_decode($this->selected_answers, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Check if answer can be auto-graded
     */
    public function getCanAutoGradeAttribute(): bool
    {
        return $this->question && $this->question->can_auto_grade;
    }

    /**
     * Check if answer needs manual grading
     */
    public function getNeedsManualGradingAttribute(): bool
    {
        return $this->question && $this->question->requires_manual_grading;
    }

    /**
     * Get percentage of points earned
     */
    public function getPercentageAttribute(): ?float
    {
        if ($this->points_earned === null || $this->points_possible === null) {
            return null;
        }

        if ($this->points_possible == 0) {
            return 0;
        }

        return round(($this->points_earned / $this->points_possible) * 100, 2);
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Auto-grade the answer
     */
    public function autoGrade(): bool
    {
        if (!$this->can_auto_grade) {
            return false;
        }

        // Get the student's answer based on question type
        $studentAnswer = $this->getStudentAnswer();

        // Check if answer is correct
        $this->is_correct = $this->question->checkAnswer($studentAnswer);

        // Award points based on correctness
        $this->points_possible = $this->question->points;
        $this->points_earned = $this->is_correct ? $this->question->points : 0;

        $this->answered_at = $this->answered_at ?? now();
        $this->manually_graded = false;

        return $this->save();
    }

    /**
     * Manually grade the answer
     *
     * @param float $pointsEarned Points awarded by teacher
     * @param int $gradedBy Teacher ID
     * @param string|null $feedback Optional feedback
     */
    public function manualGrade(float $pointsEarned, int $gradedBy, ?string $feedback = null): bool
    {
        $this->points_possible = $this->question->points;
        $this->points_earned = min($pointsEarned, $this->points_possible);

        // Calculate correctness based on points
        if ($this->points_possible > 0) {
            $percentage = ($this->points_earned / $this->points_possible) * 100;
            $this->is_correct = $percentage >= 50; // Consider 50%+ as correct
        } else {
            $this->is_correct = false;
        }

        $this->manually_graded = true;
        $this->graded_by = $gradedBy;
        $this->graded_at = now();
        $this->answered_at = $this->answered_at ?? now();

        if ($feedback !== null) {
            $this->feedback = $feedback;
        }

        return $this->save();
    }

    /**
     * Get student's answer in appropriate format based on question type
     */
    protected function getStudentAnswer()
    {
        switch ($this->question->question_type) {
            case ESubjectTestQuestion::TYPE_MULTIPLE_CHOICE:
                // If allow_multiple, return array, otherwise single ID
                if ($this->question->allow_multiple) {
                    return $this->selected_answers_array;
                } else {
                    return $this->_answer ?? ($this->selected_answers_array[0] ?? null);
                }

            case ESubjectTestQuestion::TYPE_TRUE_FALSE:
                return $this->answer_boolean;

            case ESubjectTestQuestion::TYPE_SHORT_ANSWER:
                return $this->answer_text;

            case ESubjectTestQuestion::TYPE_ESSAY:
                return $this->answer_text;

            default:
                return null;
        }
    }

    /**
     * Check if answer is empty/unanswered
     */
    public function isEmpty(): bool
    {
        return $this->answer_text === null
            && $this->answer_boolean === null
            && $this->_answer === null
            && empty($this->selected_answers_array);
    }

    /**
     * Get display value for answer
     */
    public function getDisplayValue(): ?string
    {
        switch ($this->question->question_type) {
            case ESubjectTestQuestion::TYPE_MULTIPLE_CHOICE:
                if ($this->question->allow_multiple) {
                    $answers = ESubjectTestAnswer::whereIn('id', $this->selected_answers_array)->get();
                    return $answers->pluck('answer_text')->implode(', ');
                } else {
                    return $this->answer?->answer_text;
                }

            case ESubjectTestQuestion::TYPE_TRUE_FALSE:
                return $this->answer_boolean ? 'To\'g\'ri' : 'Noto\'g\'ri';

            case ESubjectTestQuestion::TYPE_SHORT_ANSWER:
            case ESubjectTestQuestion::TYPE_ESSAY:
                return $this->answer_text;

            default:
                return null;
        }
    }

    /**
     * Award partial credit
     *
     * @param float $percentage Percentage of total points to award (0-100)
     */
    public function awardPartialCredit(float $percentage): bool
    {
        $percentage = max(0, min(100, $percentage));

        $this->points_possible = $this->question->points;
        $this->points_earned = ($this->points_possible * $percentage) / 100;
        $this->is_correct = $percentage >= 50;

        return $this->save();
    }
}
