<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Curriculum Model (O'quv rejasi)
 *
 * Represents a curriculum/study plan for a specialty
 *
 * @property int $id
 * @property string $name Curriculum name
 * @property int $_specialty Specialty ID
 * @property int $_education_type Education type ID
 * @property int $_education_form Education form ID (kunduzgi, sirtqi, etc.)
 * @property int|null $_education_year Education year ID
 * @property int|null $year Year of study
 * @property boolean $active Active status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class ECurriculum extends Model
{
    protected $table = 'e_curriculum';

    protected $fillable = [
        'name',
        '_specialty',
        '_education_type',
        '_education_form',
        '_education_year',
        'year',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'year' => 'integer',
    ];

    /**
     * Specialty relationship
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(ESpecialty::class, '_specialty');
    }

    /**
     * Curriculum subjects
     */
    public function curriculumSubjects(): HasMany
    {
        return $this->hasMany(ECurriculumSubject::class, '_curriculum');
    }

    /**
     * Scope: Active curriculums
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: By specialty
     */
    public function scopeBySpecialty($query, $specialtyId)
    {
        return $query->where('_specialty', $specialtyId);
    }

    /**
     * Get total credits
     */
    public function getTotalCreditsAttribute(): int
    {
        return $this->curriculumSubjects()
            ->join('e_subject', 'e_curriculum_subject._subject', '=', 'e_subject.id')
            ->sum('e_subject.credit');
    }
}
