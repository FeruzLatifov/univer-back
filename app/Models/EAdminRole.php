<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EAdminRole extends Model
{
    protected $table = 'e_admin_role';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'code',
        'name',
        '_translations',
        'status',
        'position',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        '_translations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getDisplayNameAttribute(): string
    {
        $translations = $this->_translations ?? [];
        $locale = app()->getLocale();
        
        // Build candidate keys matching Yii2 format: name_uz, name_oz, name_ru, name_en
        $candidates = [];
        if ($locale) {
            $base = substr($locale, 0, 2); // uz from uz-UZ
            $candidates[] = "name_{$base}"; // name_uz
            $candidates[] = "name_" . strtoupper($base); // name_UZ
            $candidates[] = "name_{$locale}"; // name_uz-UZ
            $candidates[] = $locale; // uz
            $candidates[] = $base; // uz
        }
        
        // Try candidates
        foreach ($candidates as $candidate) {
            if (isset($translations[$candidate]) && is_string($translations[$candidate]) && trim($translations[$candidate]) !== '') {
                return $translations[$candidate];
            }
        }
        
        // Fallback: any non-empty translation value
        if (is_array($translations)) {
            foreach ($translations as $value) {
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }
            }
        }
        
        return $this->name ?? $this->code ?? '';
    }

    public function admins()
    {
        return $this->hasMany(EAdmin::class, '_role', 'id');
    }

    public function resources()
    {
        return $this->belongsToMany(
            EAdminResource::class,
            'e_admin_role_resource',
            '_role',
            '_resource',
            'id',
            'id'
        );
    }
}
