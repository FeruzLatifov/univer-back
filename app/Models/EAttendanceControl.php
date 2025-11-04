<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attendance Control Model (Davomat nazorati)
 *
 * Represents a lesson where attendance was taken
 * If this record exists for a schedule, it means attendance was marked
 *
 * @property int $id
 * @property int|null $_subject_schedule Subject schedule ID
 * @property int $_group Group ID
 * @property string $_education_year Education year code
 * @property string $_semester Semester code
 * @property int $_subject Subject ID
 * @property string $_training_type Training type code
 * @property int $_employee Employee ID
 * @property string|null $_lesson_pair Lesson pair code
 * @property string $lesson_date Lesson date
 * @property int|null $load Load hours (default 2)
 * @property string|null $start_time Start time
 * @property bool $active Active status
 */
class EAttendanceControl extends Model
{
    protected $table = 'e_attendance_control';

    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        '_subject_schedule',
        '_group',
        '_education_year',
        '_semester',
        '_subject',
        '_training_type',
        '_employee',
        '_lesson_pair',
        'lesson_date',
        'load',
        'start_time',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'load' => 'integer',
        'lesson_date' => 'date',
    ];

    /**
     * Subject schedule relationship
     */
    public function subjectSchedule(): BelongsTo
    {
        return $this->belongsTo(ESubjectSchedule::class, '_subject_schedule');
    }

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
     * Employee/Teacher relationship
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EEmployee::class, '_employee');
    }
}
