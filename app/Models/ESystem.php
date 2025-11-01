<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ESystem extends Model
{
    use HasFactory;

    protected $table = 'e_system';

    protected $fillable = [
        'code',
        'value',
        'type',
        'group',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get setting value by code
     */
    public static function getSetting(string $code, $default = null)
    {
        $setting = self::where('code', $code)
            ->where('active', true)
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value by code
     */
    public static function setSetting(string $code, $value): bool
    {
        return self::updateOrCreate(
            ['code' => $code],
            ['value' => $value, 'active' => true]
        ) !== null;
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group)
    {
        return self::where('group', $group)
            ->where('active', true)
            ->pluck('value', 'code');
    }
}
