<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ESubjectTest Model
 *
 * Represents a test/quiz in the system
 *
 * @property int $id
 * @property int $_subject
 * @property int $_employee
 * @property int|null $_group
 * @property int|null $_subject_topic
 * @property int|null $_curriculum
 * @property string|null $_education_year
 * @property string|null $_semester
 * @property string $title
 * @property string|null $description
 * @property string|null $instructions
 * @property int|null $duration
 * @property float|null $passing_score
 * @property float $max_score
 * @property int $question_count
 * @property bool $randomize_questions
 * @property bool $randomize_answers
 * @property bool $show_correct_answers
 * @property int $attempt_limit
 * @property bool $allow_review
 * @property string|null $start_date
 * @property string|null $end_date
 * @property bool $is_published
 * @property string|null $published_at
 * @property bool $active
 * @property int $position
 */
class ESubjectTest extends Model
{
    protected $table = 'e_subject_test';

    protected $fillable = [
        '_subject',
        '_employee',
        '_group',
        '_subject_topic',
        '_curriculum',
        '_education_year',
        '_semester',
        'title',
        'description',
        'instructions',
        'duration',
        'passing_score',
        'max_score',
        'question_count',
        'randomize_questions',
        'randomize_answers',
        'show_correct_answers',
        'attempt_limit',
        'allow_review',
        'start_date',
        'end_date',
        'is_published',
        'published_at',
        'active',
        'position',
    ];

    protected $casts = [
        'duration' => 'integer',
        'passing_score' => 'decimal:2',
        'max_score' => 'decimal:2',
        'question_count' => 'integer',
        'randomize_questions' => 'boolean',
        'randomize_answers' => 'boolean',
        'show_correct_answers' => 'boolean',
        'attempt_limit' => 'integer',
        'allow_review' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'active' => 'boolean',
        'position' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Test belongs to a subject
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(CurriculumSubject::class, '_subject');
    }

    /**
     * Test belongs to an employee (teacher)
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EEmployee::class, '_employee');
    }

    /**
     * Test belongs to a group (optional)
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(EGroup::class, '_group');
    }

    /**
     * Test belongs to a topic (optional)
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(ESubjectTopic::class, '_subject_topic');
    }

    /**
     * Test has many questions
     */
    public function questions(): HasMany
    {
        return $this->hasMany(ESubjectTestQuestion::class, '_test')->orderBy('position');
    }

    /**
     * Test has many attempts
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(EStudentTestAttempt::class, '_test');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Only published tests
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope: Only active tests
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Upcoming tests (not started yet)
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>', now())
            ->orWhereNull('start_date');
    }

    /**
     * Scope: Currently available tests
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->where('start_date', '<=', now())
              ->orWhereNull('start_date');
        })->where(function ($q) {
            $q->where('end_date', '>=', now())
              ->orWhereNull('end_date');
        });
    }

    /**
     * Scope: Expired tests
     */
    public function scopeExpired($query)
    {
        return $query->where('end_date', '<', now());
    }

    // ==========================================
    // ATTRIBUTES
    // ==========================================

    /**
     * Check if test is expired
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->end_date && $this->end_date < now();
    }

    /**
     * Check if test is currently available
     */
    public function getIsAvailableAttribute(): bool
    {
        $afterStart = !$this->start_date || $this->start_date <= now();
        $beforeEnd = !$this->end_date || $this->end_date >= now();

        return $afterStart && $beforeEnd;
    }

    /**
     * Get attempt statistics
     */
    public function getAttemptStatsAttribute(): array
    {
        $attempts = $this->attempts()
            ->where('active', true)
            ->whereNotNull('submitted_at')
            ->get();

        return [
            'total_attempts' => $attempts->count(),
            'completed' => $attempts->where('status', 'graded')->count(),
            'pending' => $attempts->where('status', 'submitted')->count(),
            'average_score' => round($attempts->avg('percentage'), 2),
            'pass_rate' => $this->passing_score
                ? round(($attempts->where('passed', true)->count() / max($attempts->count(), 1)) * 100, 2)
                : null,
        ];
    }

    /**
     * Get average score across all attempts
     */
    public function getAverageScoreAttribute(): ?float
    {
        return $this->attempts()
            ->where('active', true)
            ->whereNotNull('total_score')
            ->avg('total_score');
    }

    /**
     * Get formatted duration
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration) {
            return 'Unlimited';
        }

        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0) {
            return $minutes > 0
                ? "{$hours} soat {$minutes} daqiqa"
                : "{$hours} soat";
        }

        return "{$minutes} daqiqa";
    }

    /**
     * Get days until end date
     */
    public function getDaysUntilEndAttribute(): ?int
    {
        if (!$this->end_date) {
            return null;
        }

        return now()->diffInDays($this->end_date, false);
    }

    // ==========================================
    // METHODS
    // ==========================================

    /**
     * Publish the test
     */
    public function publish(): bool
    {
        $this->is_published = true;
        $this->published_at = now();
        return $this->save();
    }

    /**
     * Unpublish the test
     */
    public function unpublish(): bool
    {
        $this->is_published = false;
        $this->published_at = null;
        return $this->save();
    }

    /**
     * Duplicate the test
     */
    public function duplicate(): self
    {
        $newTest = $this->replicate();
        $newTest->title = $this->title . ' (Copy)';
        $newTest->is_published = false;
        $newTest->published_at = null;
        $newTest->save();

        // Duplicate questions
        foreach ($this->questions as $question) {
            $newQuestion = $question->duplicate();
            $newQuestion->_test = $newTest->id;
            $newQuestion->save();
        }

        return $newTest;
    }

    /**
     * Calculate total score from questions
     */
    public function calculateTotalScore(): float
    {
        return $this->questions()->sum('points');
    }

    /**
     * Update question count
     */
    public function updateQuestionCount(): bool
    {
        $this->question_count = $this->questions()->count();
        return $this->save();
    }

    /**
     * Check if student can take test
     */
    public function canStudentTakeTest(int $studentId): array
    {
        // Check if published
        if (!$this->is_published) {
            return ['can_take' => false, 'reason' => 'Test nashr qilinmagan'];
        }

        // Check availability
        if (!$this->is_available) {
            return ['can_take' => false, 'reason' => 'Test mavjud emas'];
        }

        // Check attempt limit
        $attemptCount = $this->attempts()
            ->where('_student', $studentId)
            ->where('active', true)
            ->whereNotNull('submitted_at')
            ->count();

        if ($attemptCount >= $this->attempt_limit) {
            return ['can_take' => false, 'reason' => 'Urinishlar soni tugadi'];
        }

        return ['can_take' => true, 'remaining_attempts' => $this->attempt_limit - $attemptCount];
    }
}
