<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * OAuth2 Refresh Token Model
 *
 * Compatible with Yii2 oauth_refresh_token table
 */
class OAuthRefreshToken extends Model
{
    protected $table = 'oauth_refresh_token';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        '_access_token',
        'expires_at',
        'revoked',
    ];

    protected $casts = [
        '_access_token' => 'integer',
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // SCOPES
    // ============================================

    public function scopeValid($query)
    {
        return $query->where('revoked', false)
            ->where('expires_at', '>', Carbon::now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', Carbon::now());
    }

    // ============================================
    // HELPERS
    // ============================================

    public function isValid(): bool
    {
        return !$this->revoked && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function revoke(): bool
    {
        $this->revoked = true;
        return $this->save();
    }
}
