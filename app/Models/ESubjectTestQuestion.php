<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ESubjectTestQuestion Model
 *
 * Represents a question in a test
 *
 * Question Types:
 * - multiple_choice: Multiple choice (single or multiple correct answers)
 * - true_false: True/False question
 * - short_answer: Short text answer (exact match)
 * - essay: Long text answer (manual grading required)
 */
class ESubjectTestQuestion extends Model
{
    protected $table = 'e_subject_test_question';

    const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    const TYPE_TRUE_FALSE = 'true_false';
    const TYPE_SHORT_ANSWER = 'short_answer';
    const TYPE_ESSAY = 'essay';

    protected $fillable = [
        '_test',
        'question_text',
        'question_type',
        'points',
        'position',
        'is_required',
        'correct_answers',
        'allow_multiple',
        'correct_answer_text',
        'case_sensitive',
        'correct_answer_boolean',
        'word_limit',
        'explanation',
        'image_path',
        'active',
    ];

    protected $casts = [
        'points' => 'decimal:2',
        'position' => 'integer',
        'is_required' => 'boolean',
        'allow_multiple' => 'boolean',
        'case_sensitive' => 'boolean',
        'correct_answer_boolean' => 'boolean',
        'word_limit' => 'integer',
        'active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Question belongs to a test
     */
    public function test(): BelongsTo
    {
        return $this->belongsTo(ESubjectTest::class, '_test');
    }

    /**
     * Question has many answer options (for multiple choice)
     */
    public function answers(): HasMany
    {
        return $this->hasMany(ESubjectTestAnswer::class, '_question')->orderBy('position');
    }

    /**
     * Question has many student answers
     */
    public function studentAnswers(): HasMany
    {
        return $this->hasMany(EStudentTestAnswer::class, '_question');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Only active questions
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Multiple choice questions
     */
    public function scopeMultipleChoice($query)
    {
        return $query->where('question_type', self::TYPE_MULTIPLE_CHOICE);
    }

    /**
     * Scope: True/False questions
     */
    public function scopeTrueFalse($query)
    {
        return $query->where('question_type', self::TYPE_TRUE_FALSE);
    }

    /**
     * Scope: Short answer questions
     */
    public function scopeShortAnswer($query)
    {
        return $query->where('question_type', self::TYPE_SHORT_ANSWER);
    }

    /**
     * Scope: Essay questions
     */
    public function scopeEssay($query)
    {
        return $query->where('question_type', self::TYPE_ESSAY);
    }

    // ==========================================
    // ATTRIBUTES
    // ==========================================

    /**
     * Get correct answers as array
     */
    public function getCorrectAnswersArrayAttribute(): array
    {
        if (!$this->correct_answers) {
            return [];
        }

        $decoded = json_decode($this->correct_answers, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Check if question is multiple choice
     */
    public function getIsMultipleChoiceAttribute(): bool
    {
        return $this->question_type === self::TYPE_MULTIPLE_CHOICE;
    }

    /**
     * Check if question is true/false
     */
    public function getIsTrueFalseAttribute(): bool
    {
        return $this->question_type === self::TYPE_TRUE_FALSE;
    }

    /**
     * Check if question is short answer
     */
    public function getIsShortAnswerAttribute(): bool
    {
        return $this->question_type === self::TYPE_SHORT_ANSWER;
    }

    /**
     * Check if question is essay
     */
    public function getIsEssayAttribute(): bool
    {
        return $this->question_type === self::TYPE_ESSAY;
    }

    /**
     * Check if question requires manual grading
     */
    public function getRequiresManualGradingAttribute(): bool
    {
        return $this->question_type === self::TYPE_ESSAY;
    }

    /**
     * Check if question can be auto-graded
     */
    public function getCanAutoGradeAttribute(): bool
    {
        return in_array($this->question_type, [
            self::TYPE_MULTIPLE_CHOICE,
            self::TYPE_TRUE_FALSE,
            self::TYPE_SHORT_ANSWER,
        ]);
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Check if answer is correct
     *
     * @param mixed $answer Student's answer (ID, boolean, text, or array of IDs)
     * @return bool
     */
    public function checkAnswer($answer): bool
    {
        switch ($this->question_type) {
            case self::TYPE_MULTIPLE_CHOICE:
                return $this->checkMultipleChoiceAnswer($answer);

            case self::TYPE_TRUE_FALSE:
                return $this->checkTrueFalseAnswer($answer);

            case self::TYPE_SHORT_ANSWER:
                return $this->checkShortAnswer($answer);

            case self::TYPE_ESSAY:
                // Essays require manual grading
                return false;

            default:
                return false;
        }
    }

    /**
     * Check multiple choice answer
     */
    protected function checkMultipleChoiceAnswer($answer): bool
    {
        $correctAnswers = $this->correct_answers_array;

        if ($this->allow_multiple) {
            // Multiple correct answers - check if arrays match
            $studentAnswers = is_array($answer) ? $answer : [$answer];
            sort($correctAnswers);
            sort($studentAnswers);
            return $correctAnswers === $studentAnswers;
        } else {
            // Single correct answer
            return in_array($answer, $correctAnswers);
        }
    }

    /**
     * Check true/false answer
     */
    protected function checkTrueFalseAnswer($answer): bool
    {
        return $this->correct_answer_boolean === (bool) $answer;
    }

    /**
     * Check short answer
     */
    protected function checkShortAnswer($answer): bool
    {
        $correctText = $this->correct_answer_text;
        $studentText = (string) $answer;

        if (!$this->case_sensitive) {
            $correctText = mb_strtolower($correctText);
            $studentText = mb_strtolower($studentText);
        }

        // Trim whitespace
        $correctText = trim($correctText);
        $studentText = trim($studentText);

        return $correctText === $studentText;
    }

    /**
     * Get correct answer for display
     */
    public function getCorrectAnswer()
    {
        switch ($this->question_type) {
            case self::TYPE_MULTIPLE_CHOICE:
                return $this->correct_answers_array;

            case self::TYPE_TRUE_FALSE:
                return $this->correct_answer_boolean;

            case self::TYPE_SHORT_ANSWER:
                return $this->correct_answer_text;

            case self::TYPE_ESSAY:
                return null; // No single correct answer

            default:
                return null;
        }
    }

    /**
     * Duplicate the question
     */
    public function duplicate(): self
    {
        $newQuestion = $this->replicate();
        $newQuestion->save();

        // Duplicate answer options (for multiple choice)
        if ($this->is_multiple_choice) {
            foreach ($this->answers as $answer) {
                $newAnswer = $answer->replicate();
                $newAnswer->_question = $newQuestion->id;
                $newAnswer->save();
            }
        }

        return $newQuestion;
    }

    /**
     * Get statistics for this question
     */
    public function getStatistics(): array
    {
        $studentAnswers = $this->studentAnswers()
            ->whereHas('attempt', function ($q) {
                $q->whereNotNull('submitted_at');
            })
            ->get();

        $totalAnswers = $studentAnswers->count();
        $correctAnswers = $studentAnswers->where('is_correct', true)->count();

        return [
            'total_answers' => $totalAnswers,
            'correct_answers' => $correctAnswers,
            'incorrect_answers' => $totalAnswers - $correctAnswers,
            'correct_percentage' => $totalAnswers > 0
                ? round(($correctAnswers / $totalAnswers) * 100, 2)
                : 0,
            'average_points' => round($studentAnswers->avg('points_earned'), 2),
        ];
    }
}
