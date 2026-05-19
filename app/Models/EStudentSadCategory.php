<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Social Activity Direction / Category (SAD).
 * Tree via parent_id (0 = top-level direction).
 */
class EStudentSadCategory extends Model
{
    protected $table = 'e_student_sad_category';

    protected $fillable = [
        'name',
        'max_point',
        'input_type',
        'select_type',
        'parent_id',
        'input_by',
        'is_strict',
        'position',
        'active',
    ];

    protected $casts = [
        'is_strict' => 'boolean',
        'active'    => 'boolean',
        'max_point' => 'integer',
        'parent_id' => 'integer',
        'position'  => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('active', true)->orderBy('position');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(EStudentSadCriteria::class, '_category')->where('active', true)->orderBy('position');
    }
}
