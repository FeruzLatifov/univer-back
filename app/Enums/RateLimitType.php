<?php

namespace App\Enums;

/**
 * API Rate Limit Types
 * 
 * Defines rate limit categories for different API endpoints
 */
enum RateLimitType: string
{
    case PUBLIC = 'public';     // 30 requests per minute
    case STUDENT = 'student';   // 80 requests per minute
    case TEACHER = 'teacher';   // 100 requests per minute
    case ADMIN = 'admin';       // 120 requests per minute
    case AUTH = 'auth';         // 10 requests per minute (strict)
    case DEFAULT = 'default';   // 60 requests per minute

    /**
     * Get maximum attempts for this rate limit type
     */
    public function maxAttempts(): int
    {
        return match($this) {
            self::PUBLIC => 30,
            self::STUDENT => 80,
            self::TEACHER => 100,
            self::ADMIN => 120,
            self::AUTH => 10,
            self::DEFAULT => 60,
        };
    }

    /**
     * Get decay time in seconds (always 60 seconds)
     */
    public function decaySeconds(): int
    {
        return 60;
    }
}
