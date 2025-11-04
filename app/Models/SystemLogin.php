<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * System Login Audit Model
 *
 * Tracks all login attempts (successful and failed) for security auditing
 * Compatible with Yii2 e_system_login table structure
 *
 * @property int $id
 * @property string $login Login or student_id used for authentication
 * @property string|null $status 'success' or 'failed'
 * @property string|null $type 'login', 'reset', 'logout', etc.
 * @property string|null $ip IP address of the request
 * @property string|null $query Full request URL
 * @property int|null $user User ID (student or admin)
 * @property \Carbon\Carbon $created_at
 */
class SystemLogin extends Model
{
    /**
     * Table name
     */
    protected $table = 'e_system_login';

    /**
     * Disable Laravel timestamps (only created_at, no updated_at)
     */
    public $timestamps = false;

    /**
     * Date fields
     */
    protected $dates = ['created_at'];

    /**
     * Fillable attributes
     */
    protected $fillable = [
        'login',
        'status',
        'type',
        'ip',
        'query',
        'user',
        'created_at',
    ];

    /**
     * Constants for status
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * Constants for type
     */
    const TYPE_LOGIN = 'login';
    const TYPE_RESET = 'reset';
    const TYPE_LOGOUT = 'logout';
    const TYPE_REFRESH = 'refresh';

    /**
     * Log a successful login attempt
     *
     * @param string $login Login or student_id
     * @param string $type Type of login (login, reset, etc.)
     * @param int|null $userId User ID
     * @return self
     */
    public static function logSuccess(string $login, string $type = self::TYPE_LOGIN, ?int $userId = null): self
    {
        return self::create([
            'login' => $login,
            'status' => self::STATUS_SUCCESS,
            'type' => $type,
            'ip' => request()->ip(),
            'query' => request()->fullUrl(),
            'user' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * Log a failed login attempt
     *
     * @param string $login Login or student_id
     * @param string $type Type of login (login, reset, etc.)
     * @return self
     */
    public static function logFailure(string $login, string $type = self::TYPE_LOGIN): self
    {
        return self::create([
            'login' => $login,
            'status' => self::STATUS_FAILED,
            'type' => $type,
            'ip' => request()->ip(),
            'query' => request()->fullUrl(),
            'user' => null,
            'created_at' => now(),
        ]);
    }

    /**
     * Get failed login attempts for a specific login within time window
     *
     * @param string $login Login or student_id
     * @param int $minutes Time window in minutes (default: 60)
     * @return int Count of failed attempts
     */
    public static function getFailedAttemptsCount(string $login, int $minutes = 60): int
    {
        return self::where('login', $login)
            ->where('status', self::STATUS_FAILED)
            ->where('type', self::TYPE_LOGIN)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Get failed login attempts for a specific IP within time window
     *
     * @param string $ip IP address
     * @param int $minutes Time window in minutes (default: 60)
     * @return int Count of failed attempts
     */
    public static function getFailedAttemptsByIp(string $ip, int $minutes = 60): int
    {
        return self::where('ip', $ip)
            ->where('status', self::STATUS_FAILED)
            ->where('type', self::TYPE_LOGIN)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Check if login is locked due to too many failed attempts
     *
     * @param string $login Login or student_id
     * @param int $maxAttempts Maximum allowed failed attempts (default: 5)
     * @param int $lockoutMinutes Lockout window in minutes (default: 15)
     * @return bool True if locked out
     */
    public static function isLockedOut(string $login, int $maxAttempts = 5, int $lockoutMinutes = 15): bool
    {
        $failedAttempts = self::getFailedAttemptsCount($login, $lockoutMinutes);
        return $failedAttempts >= $maxAttempts;
    }

    /**
     * Get recent login attempts for a user (for admin dashboard)
     *
     * @param int $userId User ID
     * @param int $limit Number of records to return
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentAttempts(int $userId, int $limit = 10)
    {
        return self::where('user', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get suspicious activity (multiple failed attempts from different IPs)
     *
     * @param int $minutes Time window in minutes (default: 60)
     * @param int $minAttempts Minimum failed attempts to be considered suspicious (default: 3)
     * @return \Illuminate\Support\Collection
     */
    public static function getSuspiciousActivity(int $minutes = 60, int $minAttempts = 3)
    {
        return self::select('login', DB::raw('COUNT(DISTINCT ip) as unique_ips'), DB::raw('COUNT(*) as total_attempts'))
            ->where('status', self::STATUS_FAILED)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->groupBy('login')
            ->having('total_attempts', '>=', $minAttempts)
            ->having('unique_ips', '>=', 2)
            ->get();
    }

    /**
     * Clean up old login records (keep last 90 days)
     * Run this via scheduled task
     *
     * @param int $days Days to keep (default: 90)
     * @return int Number of deleted records
     */
    public static function cleanup(int $days = 90): int
    {
        return self::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get login statistics for dashboard
     *
     * @param int $days Number of days to analyze (default: 30)
     * @return array Statistics array
     */
    public static function getStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $total = self::where('created_at', '>=', $startDate)->count();
        $successful = self::where('created_at', '>=', $startDate)
            ->where('status', self::STATUS_SUCCESS)
            ->count();
        $failed = self::where('created_at', '>=', $startDate)
            ->where('status', self::STATUS_FAILED)
            ->count();

        $uniqueIps = self::where('created_at', '>=', $startDate)
            ->distinct('ip')
            ->count('ip');

        $uniqueUsers = self::where('created_at', '>=', $startDate)
            ->where('user', '!=', null)
            ->distinct('user')
            ->count('user');

        return [
            'total_attempts' => $total,
            'successful_logins' => $successful,
            'failed_logins' => $failed,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            'unique_ips' => $uniqueIps,
            'unique_users' => $uniqueUsers,
        ];
    }

    /**
     * Get daily login chart data
     *
     * @param int $days Number of days (default: 7)
     * @return array Chart data
     */
    public static function getDailyChartData(int $days = 7): array
    {
        $data = self::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(CASE WHEN status = "success" THEN 1 END) as successful'),
            DB::raw('COUNT(CASE WHEN status = "failed" THEN 1 END) as failed')
        )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return [
            'labels' => $data->pluck('date')->toArray(),
            'successful' => $data->pluck('successful')->toArray(),
            'failed' => $data->pluck('failed')->toArray(),
        ];
    }

    /**
     * Scope: Only successful logins
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope: Only failed logins
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope: Recent (last 24 hours)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDay());
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
