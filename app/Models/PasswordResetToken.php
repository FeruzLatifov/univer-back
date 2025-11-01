<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Password Reset Token Model
 *
 * Stores temporary tokens for password reset functionality
 * Tokens expire after configured time (default 60 minutes)
 */
class PasswordResetToken extends Model
{
    protected $table = 'password_reset_tokens';

    // Disable updated_at timestamp (we only need created_at)
    const UPDATED_AT = null;

    protected $fillable = [
        'email',
        'token',
        'user_type',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Check if token is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if token is still valid
     */
    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    /**
     * Delete all expired tokens (cleanup)
     */
    public static function deleteExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }

    /**
     * Delete all tokens for a given email and user type
     */
    public static function deleteForUser(string $email, string $userType): int
    {
        return self::where('email', $email)
            ->where('user_type', $userType)
            ->delete();
    }
}
