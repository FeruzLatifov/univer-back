<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance Model (Davomat)
 *
 * Tracks student attendance in classes
 *
 * @property int $id
 * @property int $_student Student ID
 * @property int $_subject_schedule Subject schedule ID
 * @property string $lesson_date Date of the lesson
 * @property string $_attendance_type Attendance type (present, absent, late, excused)
 * @property string|null $reason Reason for absence
 * @property boolean $active Active status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class EAttendance extends Model
{
    protected $table = 'e_attendance';

    protected $fillable = [
        '_student',
        '_subject_schedule',
        'lesson_date',
        '_attendance_type',
        'reason',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'lesson_date' => 'date',
    ];

    // Attendance status constants
    const STATUS_PRESENT = '11'; // Kelgan
    const STATUS_ABSENT = '12';  // Kelmagan
    const STATUS_LATE = '13';    // Kech kelgan
    const STATUS_EXCUSED = '14'; // Sababli

    /**
     * Student relationship
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(EStudent::class, '_student');
    }

    /**
     * Subject schedule relationship
     */
    public function subjectSchedule(): BelongsTo
    {
        return $this->belongsTo(ESubjectSchedule::class, '_subject_schedule');
    }

    /**
     * Scope: By student
     */
    public function scopeByStudent($query, $studentId)
    {
        return $query->where('_student', $studentId);
    }

    /**
     * Scope: By subject schedule
     */
    public function scopeBySchedule($query, $scheduleId)
    {
        return $query->where('_subject_schedule', $scheduleId);
    }

    /**
     * Scope: By date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('lesson_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Present students
     */
    public function scopePresent($query)
    {
        return $query->where('_attendance_type', self::STATUS_PRESENT);
    }

    /**
     * Scope: Absent students
     */
    public function scopeAbsent($query)
    {
        return $query->where('_attendance_type', self::STATUS_ABSENT);
    }

    /**
     * Get attendance status name
     */
    public function getStatusNameAttribute(): string
    {
        $statuses = [
            self::STATUS_PRESENT => 'Kelgan',
            self::STATUS_ABSENT => 'Kelmagan',
            self::STATUS_LATE => 'Kech kelgan',
            self::STATUS_EXCUSED => 'Sababli',
        ];

        return $statuses[$this->_attendance_type] ?? 'Noma\'lum';
    }

    /**
     * Check if present
     */
    public function isPresentAttribute(): bool
    {
        return $this->_attendance_type === self::STATUS_PRESENT;
    }
}
