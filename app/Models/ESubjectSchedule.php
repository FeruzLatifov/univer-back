<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Subject Schedule Model (Dars jadvali)
 *
 * Represents a scheduled class/lesson
 *
 * @property int $id
 * @property int $_subject Subject ID
 * @property int $_group Group ID
 * @property int $_employee Teacher/Employee ID
 * @property int $_lesson_pair Lesson pair ID (time slot)
 * @property int $_auditorium Room/Auditorium ID
 * @property int $_semester Semester
 * @property int $_education_year Education year ID
 * @property int $week Day of week (1-6)
 * @property string $_training_type Training type (lecture, practice, lab)
 * @property boolean $active Active status
 */
class ESubjectSchedule extends Model
{
    protected $table = 'e_subject_schedule';

    protected $fillable = [
        '_subject',
        '_group',
        '_employee',
        '_lesson_pair',
        '_auditorium',
        '_semester',
        '_education_year',
        'week',
        '_training_type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'week' => 'integer',
        '_semester' => 'integer',
    ];

    /**
     * Subject relationship
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(ESubject::class, '_subject');
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
     * Lesson pair (time slot) relationship
     */
    public function lessonPair(): BelongsTo
    {
        return $this->belongsTo(ELessonPair::class, '_lesson_pair');
    }

    /**
     * Attendance control relationship (if attendance was marked)
     *
     * If this relationship exists, it means attendance was taken for this class
     * If null, attendance has not been marked yet (pending)
     */
    public function attendanceControl(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EAttendanceControl::class, '_subject_schedule');
    }

    /**
     * Attendance records for this schedule
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(EAttendance::class, '_subject_schedule');
    }

    /**
     * Scope: Active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: By teacher
     */
    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('_employee', $teacherId);
    }

    /**
     * Scope: By group
     */
    public function scopeByGroup($query, $groupId)
    {
        return $query->where('_group', $groupId);
    }

    /**
     * Scope: By semester
     */
    public function scopeBySemester($query, $semester)
    {
        return $query->where('_semester', $semester);
    }

    /**
     * Scope: By day of week
     */
    public function scopeByDay($query, $day)
    {
        return $query->where('week', $day);
    }

    /**
     * Get day name in Uzbek
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            1 => 'Dushanba',
            2 => 'Seshanba',
            3 => 'Chorshanba',
            4 => 'Payshanba',
            5 => 'Juma',
            6 => 'Shanba',
        ];

        return $days[$this->week] ?? '';
    }
}
