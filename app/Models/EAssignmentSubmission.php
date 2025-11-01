<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Assignment Submission Model (Topshiriq javoblari)
 *
 * Talabalar tomonidan yuborilgan topshiriq javoblari
 *
 * @property int $id
 * @property int $_assignment
 * @property int|null $_curriculum
 * @property int|null $_subject
 * @property string|null $_training_type
 * @property string|null $_education_year
 * @property string|null $_semester
 * @property int $_student
 * @property int|null $_group
 * @property int|null $_employee
 * @property string|null $text_content
 * @property string|null $file_path
 * @property string|null $file_name
 * @property array|null $files
 * @property int $attempt_number
 * @property \DateTime|null $submitted_at
 * @property bool $is_late
 * @property int $position
 * @property float|null $score
 * @property int $max_score
 * @property string|null $feedback
 * @property \DateTime|null $graded_at
 * @property \DateTime|null $viewed_at
 * @property \DateTime|null $returned_at
 * @property string $status
 * @property bool $active
 */
class EAssignmentSubmission extends Model
{
    protected $table = 'e_assignment_submission';

    protected $fillable = [
        '_assignment',
        '_curriculum',
        '_subject',
        '_training_type',
        '_education_year',
        '_semester',
        '_student',
        '_group',
        '_employee',
        'text_content',
        'file_path',
        'file_name',
        'files',
        'attempt_number',
        'submitted_at',
        'is_late',
        'position',
        'score',
        'max_score',
        'feedback',
        'graded_at',
        'viewed_at',
        'returned_at',
        'status',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_late' => 'boolean',
        'attempt_number' => 'integer',
        'position' => 'integer',
        'score' => 'decimal:2',
        'max_score' => 'integer',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'viewed_at' => 'datetime',
        'returned_at' => 'datetime',
        'files' => 'array',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';          // Kutilmoqda (hali yuborilmagan)
    const STATUS_DRAFT = 'draft';              // Qoralama (saqlangan lekin yuborilmagan)
    const STATUS_SUBMITTED = 'submitted';      // Yuborilgan
    const STATUS_VIEWED = 'viewed';            // Ko'rilgan (o'qituvchi ko'rdi)
    const STATUS_GRADING = 'grading';          // Baholanmoqda
    const STATUS_GRADED = 'graded';            // Baholangan
    const STATUS_RETURNED = 'returned';        // Qaytarilgan (revision kerak)

    /**
     * Assignment relationship
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(EAssignment::class, '_assignment');
    }

    /**
     * Student relationship
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(EStudent::class, '_student');
    }

    /**
     * Group relationship
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(EGroup::class, '_group');
    }

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
     * Grader/Teacher relationship
     */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(EEmployee::class, '_employee');
    }

    /**
     * Activity logs relationship
     */
    public function activities(): HasMany
    {
        return $this->hasMany(EStudentTaskActivity::class, '_assignment')
            ->where('_student', $this->_student);
    }

    /**
     * Scope: Active submissions
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Submitted (not draft)
     */
    public function scopeSubmitted($query)
    {
        return $query->whereNotNull('submitted_at')
            ->where('status', '!=', self::STATUS_DRAFT);
    }

    /**
     * Scope: Pending grading
     */
    public function scopePendingGrading($query)
    {
        return $query->whereNotNull('submitted_at')
            ->whereNull('graded_at')
            ->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_VIEWED, self::STATUS_GRADING]);
    }

    /**
     * Scope: Graded
     */
    public function scopeGraded($query)
    {
        return $query->whereNotNull('graded_at')
            ->where('status', self::STATUS_GRADED);
    }

    /**
     * Scope: Late submissions
     */
    public function scopeLate($query)
    {
        return $query->where('is_late', true);
    }

    /**
     * Scope: By assignment
     */
    public function scopeByAssignment($query, $assignmentId)
    {
        return $query->where('_assignment', $assignmentId);
    }

    /**
     * Scope: By student
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('_student', $studentId);
    }

    /**
     * Scope: Latest attempt
     */
    public function scopeLatestAttempt($query)
    {
        return $query->orderBy('attempt_number', 'desc');
    }

    /**
     * Get percentage score
     */
    public function getPercentageAttribute(): ?float
    {
        if ($this->score === null || $this->max_score == 0) {
            return null;
        }

        return round(($this->score / $this->max_score) * 100, 2);
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

    /**
     * Check if passed
     */
    public function getPassedAttribute(): ?bool
    {
        if ($this->numeric_grade === null) {
            return null;
        }

        return $this->numeric_grade !== '2';
    }

    /**
     * Check if submission is late
     */
    public function checkIfLate(): bool
    {
        if (!$this->submitted_at || !$this->assignment) {
            return false;
        }

        return $this->submitted_at > $this->assignment->deadline;
    }

    /**
     * Get days late
     */
    public function getDaysLateAttribute(): int
    {
        if (!$this->is_late || !$this->submitted_at || !$this->assignment) {
            return 0;
        }

        return $this->assignment->deadline->diffInDays($this->submitted_at);
    }

    /**
     * Get file count
     */
    public function getFileCountAttribute(): int
    {
        $count = 0;

        if ($this->file_path) {
            $count++;
        }

        if ($this->files) {
            $count += count($this->files);
        }

        return $count;
    }

    /**
     * Get all files (legacy + new format)
     */
    public function getAllFilesAttribute(): array
    {
        $allFiles = [];

        // Add legacy single file if exists
        if ($this->file_path && $this->file_name) {
            $allFiles[] = [
                'path' => $this->file_path,
                'name' => $this->file_name,
                'legacy' => true,
            ];
        }

        // Add new multiple files
        if ($this->files && is_array($this->files)) {
            foreach ($this->files as $file) {
                $allFiles[] = array_merge($file, ['legacy' => false]);
            }
        }

        return $allFiles;
    }

    /**
     * Get status name in Uzbek
     */
    public function getStatusNameAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING => 'Kutilmoqda',
            self::STATUS_DRAFT => 'Qoralama',
            self::STATUS_SUBMITTED => 'Yuborilgan',
            self::STATUS_VIEWED => 'Ko\'rilgan',
            self::STATUS_GRADING => 'Baholanmoqda',
            self::STATUS_GRADED => 'Baholangan',
            self::STATUS_RETURNED => 'Qaytarilgan',
        ];

        return $statuses[$this->status] ?? 'Noma\'lum';
    }
}
