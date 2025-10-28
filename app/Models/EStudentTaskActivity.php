<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Student Task Activity Model (Talaba topshiriq faolligi)
 *
 * Tracks all student interactions with assignments
 *
 * @property int $id
 * @property int $_assignment
 * @property int $_student
 * @property string $activity_type
 * @property string|null $details
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 */
class EStudentTaskActivity extends Model
{
    protected $table = 'e_student_task_activity';

    protected $fillable = [
        '_assignment',
        '_student',
        'activity_type',
        'details',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Activity type constants
    const ACTIVITY_VIEWED = 'viewed';              // Topshiriqni ko'rdi
    const ACTIVITY_STARTED = 'started';            // Topshiriqni boshladi
    const ACTIVITY_DRAFT_SAVED = 'draft_saved';    // Qoralama saqlandi
    const ACTIVITY_SUBMITTED = 'submitted';        // Topshiriq yuborildi
    const ACTIVITY_RESUBMITTED = 'resubmitted';    // Qayta yuborildi
    const ACTIVITY_VIEWED_BY_TEACHER = 'viewed_by_teacher'; // O'qituvchi ko'rdi
    const ACTIVITY_GRADED = 'graded';              // Baholandi
    const ACTIVITY_RETURNED = 'returned';          // Qaytarildi (revision)
    const ACTIVITY_FEEDBACK_VIEWED = 'feedback_viewed'; // Fikr-mulohaza ko'rildi
    const ACTIVITY_FILE_DOWNLOADED = 'file_downloaded'; // Fayl yuklandi

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
     * Scope: By activity type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * Scope: Recent activities
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get activity type name in Uzbek
     */
    public function getActivityNameAttribute(): string
    {
        $activities = [
            self::ACTIVITY_VIEWED => 'Topshiriq ko\'rildi',
            self::ACTIVITY_STARTED => 'Topshiriq boshlandi',
            self::ACTIVITY_DRAFT_SAVED => 'Qoralama saqlandi',
            self::ACTIVITY_SUBMITTED => 'Topshiriq yuborildi',
            self::ACTIVITY_RESUBMITTED => 'Qayta yuborildi',
            self::ACTIVITY_VIEWED_BY_TEACHER => 'O\'qituvchi ko\'rdi',
            self::ACTIVITY_GRADED => 'Baholandi',
            self::ACTIVITY_RETURNED => 'Qaytarildi',
            self::ACTIVITY_FEEDBACK_VIEWED => 'Fikr-mulohaza ko\'rildi',
            self::ACTIVITY_FILE_DOWNLOADED => 'Fayl yuklandi',
        ];

        return $activities[$this->activity_type] ?? 'Noma\'lum faollik';
    }

    /**
     * Get parsed details (from JSON)
     */
    public function getParsedDetailsAttribute(): ?array
    {
        if (!$this->details) {
            return null;
        }

        $decoded = json_decode($this->details, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Log activity helper method
     *
     * @param int $assignmentId
     * @param int $studentId
     * @param string $activityType
     * @param array|null $details
     * @return static
     */
    public static function logActivity(
        int $assignmentId,
        int $studentId,
        string $activityType,
        ?array $details = null
    ): self {
        return self::create([
            '_assignment' => $assignmentId,
            '_student' => $studentId,
            'activity_type' => $activityType,
            'details' => $details ? json_encode($details) : null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
