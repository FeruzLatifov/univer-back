<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Lesson Plan (Taqvimiy reja)
 *
 * Laravel-only table — not present in Yii2.
 * One row per (teacher, subject, group, topic) tuple.
 *
 * @property int $id
 * @property int $_employee
 * @property int $_subject
 * @property int|null $_group
 * @property int $_topic
 * @property string $planned_date
 * @property string|null $actual_date
 * @property int $hours
 * @property string|null $notes
 * @property bool $active
 */
class ELessonPlan extends Model
{
    protected $table = 'e_lesson_plan';

    protected $fillable = [
        '_employee',
        '_subject',
        '_group',
        '_topic',
        'planned_date',
        'actual_date',
        'hours',
        'notes',
        'active',
    ];

    protected $casts = [
        'active'       => 'boolean',
        'hours'        => 'integer',
        'planned_date' => 'date',
        'actual_date'  => 'date',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(ESubjectTopic::class, '_topic', 'id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(ESubject::class, '_subject', 'id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(EGroup::class, '_group', 'id');
    }
}
