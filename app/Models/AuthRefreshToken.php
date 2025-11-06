<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $user_type
 * @property string $token_hash
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AuthRefreshToken extends Model
{
    public const TYPE_STUDENT = 'student';
    public const TYPE_EMPLOYEE = 'employee';

    protected $fillable = [
        'user_id',
        'user_type',
        'token_hash',
        'ip_address',
        'user_agent',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}

