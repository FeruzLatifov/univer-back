<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OAuth2 Client Model
 *
 * Represents third-party applications that can access the API
 * Compatible with Yii2 oauth_client table
 *
 * Grant Types:
 * 1 = authorization_code
 * 2 = password
 * 3 = client_credentials
 * 4 = refresh_token
 *
 * Token Types:
 * 1 = bearer
 * 2 = mac
 */
class OAuthClient extends Model
{
    protected $table = 'oauth_client';

    protected $fillable = [
        '_user',
        'secret',
        'name',
        'redirect',
        'token_type',
        'grant_type',
        'revoked',
    ];

    protected $casts = [
        '_user' => 'integer',
        'token_type' => 'integer',
        'grant_type' => 'integer',
        'revoked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * Get the user that owns this client
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(EAdmin::class, '_user', 'id');
    }

    /**
     * Get all access tokens for this client
     */
    public function accessTokens(): HasMany
    {
        return $this->hasMany(OAuthAccessToken::class, '_client', 'id');
    }

    /**
     * Get all auth codes for this client
     */
    public function authCodes(): HasMany
    {
        return $this->hasMany(OAuthAuthCode::class, '_client', 'id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeActive($query)
    {
        return $query->where('revoked', false)->orWhereNull('revoked');
    }

    public function scopeByGrantType($query, int $grantType)
    {
        return $query->where('grant_type', $grantType);
    }

    // ============================================
    // HELPERS
    // ============================================

    public function isRevoked(): bool
    {
        return $this->revoked === true;
    }

    public function revoke(): bool
    {
        $this->revoked = true;
        return $this->save();
    }

    public function supportsGrantType(int $grantType): bool
    {
        return $this->grant_type === $grantType;
    }

    public function getGrantTypeName(): string
    {
        return match($this->grant_type) {
            1 => 'authorization_code',
            2 => 'password',
            3 => 'client_credentials',
            4 => 'refresh_token',
            default => 'unknown',
        };
    }

    public function getTokenTypeName(): string
    {
        return match($this->token_type) {
            1 => 'bearer',
            2 => 'mac',
            default => 'unknown',
        };
    }
}
