<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

/**
 * OAuth2 Access Token Model
 *
 * Represents access tokens issued to clients
 * Compatible with Yii2 oauth_access_token table
 */
class OAuthAccessToken extends Model
{
    protected $table = 'oauth_access_token';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        '_client',
        '_user',
        'expires_at',
        'revoked',
    ];

    protected $casts = [
        '_client' => 'integer',
        '_user' => 'integer',
        'expires_at' => 'datetime',
        'revoked' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function client(): BelongsTo
    {
        return $this->belongsTo(OAuthClient::class, '_client', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(EAdmin::class, '_user', 'id');
    }

    public function refreshToken(): HasOne
    {
        return $this->hasOne(OAuthRefreshToken::class, '_access_token', 'id');
    }

    public function scopes(): BelongsToMany
    {
        return $this->belongsToMany(
            OAuthScope::class,
            'oauth_access_token_scope',
            '_access_token',
            '_scope',
            'id',
            'id'
        );
    }

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

    public function scopeRevoked($query)
    {
        return $query->where('revoked', true);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('_client', $clientId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('_user', $userId);
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

    public function isRevoked(): bool
    {
        return $this->revoked === true;
    }

    public function revoke(): bool
    {
        $this->revoked = true;
        return $this->save();
    }

    public function expiresIn(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        return $this->expires_at->diffInSeconds(Carbon::now());
    }

    public function hasScope(string $scopeId): bool
    {
        return $this->scopes()->where('id', $scopeId)->exists();
    }
}
