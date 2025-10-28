<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Assignment Model (Topshiriqlar)
 *
 * O'qituvchilar tomonidan talabalarga beriladigan topshiriqlar
 *
 * @property int $id
 * @property int $_subject
 * @property int|null $_curriculum
 * @property string|null $_language
 * @property string|null $_training_type
 * @property int|null $_subject_topic
 * @property string|null $_education_year
 * @property string|null $_semester
 * @property int $_group
 * @property int $_employee
 * @property string $title
 * @property string|null $description
 * @property string|null $instructions
 * @property int $max_score
 * @property string|null $_marking_category
 * @property \DateTime $deadline
 * @property int|null $attempt_count
 * @property bool $allow_late
 * @property array|null $files
 * @property int $position
 * @property bool $active
 * @property \DateTime|null $published_at
 */
class EAssignment extends Model
{
    protected $table = 'e_assignment';

    protected $fillable = [
        '_subject',
        '_curriculum',
        '_language',
        '_training_type',
        '_subject_topic',
        '_education_year',
        '_semester',
        '_group',
        '_employee',
        'title',
        'description',
        'instructions',
        'max_score',
        '_marking_category',
        'deadline',
        'attempt_count',
        'allow_late',
        'files',
        'position',
        'active',
        'published_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'allow_late' => 'boolean',
        'max_score' => 'integer',
        'attempt_count' => 'integer',
        'position' => 'integer',
        'deadline' => 'datetime',
        'published_at' => 'datetime',
        'files' => 'array',
    ];

    // Marking category constants
    const MARKING_MIDTERM = '11';        // Oraliq nazorat
    const MARKING_FINAL = '12';          // Yakuniy nazorat
    const MARKING_INDEPENDENT = '13';    // Mustaqil ish
    const MARKING_PRACTICAL = '14';      // Amaliy mashg'ulot
    const MARKING_LABORATORY = '15';     // Laboratoriya

    /**
     * Subject relationship
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(ESubject::class, '_subject');
    }

    /**
     * Curriculum relationship
     */
    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(ECurriculum::class, '_curriculum');
    }

    /**
     * Subject topic relationship
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(ESubjectTopic::class, '_subject_topic');
    }

    /**
     * Group relationship
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(EGroup::class, '_group');
    }

    /**
     * Teacher/Employee relationship
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(EEmployee::class, '_employee');
    }

    /**
     * Submissions relationship
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(EAssignmentSubmission::class, '_assignment');
    }

    /**
     * Activity logs relationship
     */
    public function activities(): HasMany
    {
        return $this->hasMany(EStudentTaskActivity::class, '_assignment');
    }

    /**
     * Scope: Active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Published assignments
     */
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: By teacher
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('_employee', $teacherId);
    }

    /**
     * Scope: By subject
     */
    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('_subject', $subjectId);
    }

    /**
     * Scope: By group
     */
    public function scopeByGroup($query, $groupId)
    {
        return $query->where('_group', $groupId);
    }

    /**
     * Scope: By education year and semester
     */
    public function scopeByAcademicPeriod($query, $year, $semester)
    {
        return $query->where('_education_year', $year)
            ->where('_semester', $semester);
    }

    /**
     * Scope: Upcoming deadlines
     */
    public function scopeUpcoming($query)
    {
        return $query->where('deadline', '>', now())
            ->orderBy('deadline', 'asc');
    }

    /**
     * Scope: Overdue assignments
     */
    public function scopeOverdue($query)
    {
        return $query->where('deadline', '<', now())
            ->where('active', true);
    }

    /**
     * Check if assignment is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        return $this->deadline < now();
    }

    /**
     * Check if assignment is published
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->published_at && $this->published_at <= now();
    }

    /**
     * Get marking category name
     */
    public function getMarkingCategoryNameAttribute(): ?string
    {
        if (!$this->_marking_category) {
            return null;
        }

        $categories = [
            self::MARKING_MIDTERM => 'Oraliq nazorat',
            self::MARKING_FINAL => 'Yakuniy nazorat',
            self::MARKING_INDEPENDENT => 'Mustaqil ish',
            self::MARKING_PRACTICAL => 'Amaliy mashg\'ulot',
            self::MARKING_LABORATORY => 'Laboratoriya',
        ];

        return $categories[$this->_marking_category] ?? 'Noma\'lum';
    }

    /**
     * Get submission statistics
     */
    public function getSubmissionStatsAttribute(): array
    {
        $totalStudents = EStudent::where('_group', $this->_group)
            ->where('active', true)
            ->count();

        $submissions = $this->submissions()
            ->where('active', true)
            ->whereNotNull('submitted_at')
            ->get();

        $graded = $submissions->whereNotNull('graded_at')->count();
        $pending = $submissions->whereNull('graded_at')->count();
        $notSubmitted = $totalStudents - $submissions->count();

        return [
            'total_students' => $totalStudents,
            'submitted' => $submissions->count(),
            'not_submitted' => $notSubmitted,
            'graded' => $graded,
            'pending_grading' => $pending,
            'submission_rate' => $totalStudents > 0 ? round(($submissions->count() / $totalStudents) * 100, 1) : 0,
        ];
    }

    /**
     * Get days until deadline
     */
    public function getDaysUntilDeadlineAttribute(): int
    {
        return now()->diffInDays($this->deadline, false);
    }

    /**
     * Get file count
     */
    public function getFileCountAttribute(): int
    {
        return $this->files ? count($this->files) : 0;
    }
}
