<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Subject Resource Model (Fan resurslari - fayllar)
 *
 * Stores files/resources for subjects
 *
 * @property int $id
 * @property int $_subject Subject ID
 * @property int|null $_employee Teacher/uploader ID
 * @property string $name Resource name
 * @property string|null $description Resource description
 * @property string $filename Original filename
 * @property string $path File path
 * @property string|null $mime_type MIME type
 * @property int|null $size File size in bytes
 * @property string $_resource_type Resource type (lecture, assignment, etc.)
 * @property boolean $active Active status
 * @property string|null $created_at
 * @property string|null $updated_at
 */
class ESubjectResource extends Model
{
    protected $table = 'e_subject_resource';

    protected $fillable = [
        '_subject',
        '_employee',
        'name',
        'description',
        'filename',
        'path',
        'mime_type',
        'size',
        '_resource_type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'size' => 'integer',
    ];

    // Resource type constants
    const TYPE_LECTURE = '11';      // Ma'ruza
    const TYPE_PRACTICE = '12';     // Amaliyot
    const TYPE_ASSIGNMENT = '13';   // Topshiriq
    const TYPE_REFERENCE = '14';    // Qo'shimcha adabiyot
    const TYPE_EXAM = '15';         // Imtihon materiallari

    /**
     * Subject relationship
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(ESubject::class, '_subject');
    }

    /**
     * Teacher/uploader relationship
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(EEmployee::class, '_employee');
    }

    /**
     * Scope: Active resources
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
     * Scope: By resource type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('_resource_type', $type);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute(): string
    {
        if (!$this->size) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->size;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Get resource type name
     */
    public function getTypeNameAttribute(): string
    {
        $types = [
            self::TYPE_LECTURE => 'Ma\'ruza',
            self::TYPE_PRACTICE => 'Amaliyot',
            self::TYPE_ASSIGNMENT => 'Topshiriq',
            self::TYPE_REFERENCE => 'Qo\'shimcha adabiyot',
            self::TYPE_EXAM => 'Imtihon materiallari',
        ];

        return $types[$this->_resource_type] ?? 'Noma\'lum';
    }
}
