<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lesson Pair Model (Dars juftligi - vaqt)
 *
 * Represents a time slot for lessons
 *
 * @property int $id
 * @property int $number Pair number (1, 2, 3, etc.)
 * @property string $start_time Start time (e.g., 08:00)
 * @property string $end_time End time (e.g., 09:30)
 * @property boolean $active Active status
 */
class ELessonPair extends Model
{
    protected $table = 'e_lesson_pair';

    public $timestamps = false;

    protected $fillable = [
        'number',
        'start_time',
        'end_time',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'number' => 'integer',
    ];

    /**
     * Schedules using this lesson pair
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ESubjectSchedule::class, '_lesson_pair');
    }

    /**
     * Scope: Active lesson pairs
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute(): string
    {
        return $this->start_time . ' - ' . $this->end_time;
    }
}
