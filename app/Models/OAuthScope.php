<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * OAuth2 Scope Model
 *
 * Represents OAuth2 scopes/permissions
 * Compatible with Yii2 oauth_scope table
 */
class OAuthScope extends Model
{
    protected $table = 'oauth_scope';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'string',
    ];

    // ============================================
    // HELPERS
    // ============================================

    public static function findByIdentifier(string $identifier): ?self
    {
        return static::find($identifier);
    }

    public static function exists(string $identifier): bool
    {
        return static::where('id', $identifier)->exists();
    }
}
