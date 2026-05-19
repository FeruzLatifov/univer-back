<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tutor Visit Model (Tyutor tashrifi)
 *
 * Shared with Yii2 — schema owned by Yii2 migration
 * console/migrations/m251121_060102_create_e_tutor_visit_table.php
 *
 * Yii2 sets _tutor automatically via beforeSave; the Laravel service
 * mirrors that behaviour explicitly.
 *
 * @property int $id
 * @property int $_student
 * @property string|null $_student_living_status
 * @property string|null $_accommodation
 * @property string|null $_current_province
 * @property string|null $_current_district
 * @property string|null $_current_terrain
 * @property string|null $current_address
 * @property string|null $geolocation
 * @property int|null $roommate_count
 * @property string|null $comment
 * @property int|null $_tutor
 * @property int $position
 * @property bool $active
 */
class ETutorVisit extends Model
{
    protected $table = 'e_tutor_visit';

    protected $fillable = [
        '_student',
        '_student_living_status',
        '_accommodation',
        '_current_province',
        '_current_district',
        '_current_terrain',
        'current_address',
        'geolocation',
        'roommate_count',
        'comment',
        '_tutor',
        'position',
        'active',
    ];

    protected $casts = [
        'active'         => 'boolean',
        'position'       => 'integer',
        'roommate_count' => 'integer',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(EStudent::class, '_student', 'id');
    }
}
