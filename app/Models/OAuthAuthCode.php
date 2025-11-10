<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

/**
 * OAuth2 Authorization Code Model
 *
 * Compatible with Yii2 oauth_auth_code table
 */
class OAuthAuthCode extends Model
{
    protected $table = 'oauth_auth_code';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $timestamps = false;

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

    public function scopes(): BelongsToMany
    {
        return $this->belongsToMany(
            OAuthScope::class,
            'oauth_auth_code_scope',
            '_auth_code',
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

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('_client', $clientId);
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
