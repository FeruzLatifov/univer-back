<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ESubjectTestAnswer Model
 *
 * Represents an answer option for multiple choice questions
 */
class ESubjectTestAnswer extends Model
{
    protected $table = 'e_subject_test_answer';

    protected $fillable = [
        '_question',
        'answer_text',
        'image_path',
        'position',
        'is_correct',
        'active',
    ];

    protected $casts = [
        'position' => 'integer',
        'is_correct' => 'boolean',
        'active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Answer belongs to a question
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(ESubjectTestQuestion::class, '_question');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Scope: Only active answers
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope: Only correct answers
     */
    public function scopeCorrect($query)
    {
        return $query->where('is_correct', true);
    }

    // ==========================================
    // ATTRIBUTES
    // ==========================================

    /**
     * Get is_correct attribute
     */
    public function getCorrectAttribute(): bool
    {
        return $this->is_correct;
    }
}
