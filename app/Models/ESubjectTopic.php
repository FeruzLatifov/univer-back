<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subject Topic Model (Fan mavzulari)
 *
 * Represents a topic/chapter in a subject syllabus
 *
 * @property int $id
 * @property int $_subject Subject ID
 * @property string $name Topic name
 * @property string|null $content Topic content/description
 * @property int|null $order_number Order in syllabus
 * @property int|null $hours Allocated hours
 * @property boolean $active Active status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class ESubjectTopic extends Model
{
    protected $table = 'e_subject_topic';

    protected $fillable = [
        '_subject',
        'name',
        'content',
        'order_number',
        'hours',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'order_number' => 'integer',
        'hours' => 'integer',
    ];

    /**
     * Subject relationship
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(ESubject::class, '_subject');
    }

    /**
     * Scope: Active topics
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: By subject
     */
    public function scopeBySubject($query, $subjectId)
    {
        return $query->where('_subject', $subjectId);
    }

    /**
     * Scope: Ordered topics
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }
}
