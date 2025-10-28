<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\Translatable;

/**
 * Subject Model (Fanlar)
 *
 * Represents a subject/course in the curriculum
 *
 * @property int $id
 * @property string $code Subject code (e.g., MATH101)
 * @property string $name Subject name
 * @property int $credit Credit hours
 * @property string $_curriculum_subject_type Type (majburiy, tanlov, etc.)
 * @property int|null $_department Department ID
 * @property boolean $active Active status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class ESubject extends Model
{
    use Translatable;

    protected $table = 'e_subject';

    protected $fillable = [
        'code',
        'name',
        'credit',
        '_curriculum_subject_type',
        '_department',
        '_translations',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'credit' => 'integer',
        '_translations' => 'array',
    ];

    /**
     * Translatable attributes
     */
    protected $translatable = ['name'];

    /**
     * Department relationship
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(EDepartment::class, '_department');
    }

    /**
     * Subject topics
     */
    public function topics(): HasMany
    {
        return $this->hasMany(ESubjectTopic::class, '_subject');
    }

    /**
     * Subject resources (files)
     */
    public function resources(): HasMany
    {
        return $this->hasMany(ESubjectResource::class, '_subject');
    }

    /**
     * Subject schedules
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(ESubjectSchedule::class, '_subject');
    }

    /**
     * Curriculum subjects (link to curriculums)
     */
    public function curriculumSubjects(): HasMany
    {
        return $this->hasMany(ECurriculumSubject::class, '_subject');
    }

    /**
     * Teachers assigned to this subject
     */
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(
            EEmployee::class,
            'e_subject_schedule',
            '_subject',
            '_employee'
        )->distinct();
    }

    /**
     * Scope: Active subjects only
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: By department
     */
    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('_department', $departmentId);
    }
}
