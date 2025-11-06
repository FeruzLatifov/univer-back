<?php

namespace App\Services\Student;

use App\Models\AuthRefreshToken;
use App\Models\EStudent;
use App\Models\PasswordResetToken;
use App\Models\SystemLogin;
use App\Services\Auth\RefreshTokenService;
use App\Services\CaptchaService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Student Authentication Service
 *
 * BUSINESS LOGIC LAYER
 * ======================
 *
 * Modular Monolith Architecture - Student Module
 * Contains all business logic for student authentication
 *
 * Controller → Service → Repository → Model
 *
 * @package App\Services\Student
 */
class AuthService
{
    public function __construct(
        private CaptchaService $captchaService,
        private RefreshTokenService $refreshTokenService,
    ) {
    }

    /**
     * Attempt student login
     *
     * @param string $studentId
     * @param string $password
     * @param string|null $captcha
     * @param string $ipAddress
     * @return array
     * @throws \Exception
     */
    public function attemptLogin(string $studentId, string $password, ?string $captcha, string $ipAddress, ?string $userAgent = null): array
    {
        $maxAttempts = (int) env('AUTH_MAX_ATTEMPTS', 5);
        $lockoutMinutes = (int) env('AUTH_LOCKOUT_MINUTES', 15);
        $maxIpAttempts = (int) env('AUTH_MAX_ATTEMPTS_PER_IP', 20);

        if ($maxAttempts > 0 && SystemLogin::isLockedOut($studentId, $maxAttempts, $lockoutMinutes)) {
            throw new \Exception(__('auth.too_many_attempts', ['minutes' => $lockoutMinutes]));
        }

        if ($maxIpAttempts > 0) {
            $ipAttempts = SystemLogin::getFailedAttemptsByIp($ipAddress, $lockoutMinutes);
            if ($ipAttempts >= $maxIpAttempts) {
                throw new \Exception(__('auth.too_many_attempts', ['minutes' => $lockoutMinutes]));
            }
        }

        // CAPTCHA verification
        if ($this->captchaService->isEnabled()) {
            $captchaResult = $this->captchaService->verify($captcha, $ipAddress, 'student_login');

            if (!$captchaResult['success']) {
                logger()->warning('Student login CAPTCHA failed', [
                    'student_id' => $studentId,
                    'ip' => $ipAddress,
                    'score' => $captchaResult['score'] ?? 0,
                    'error_codes' => $captchaResult['error_codes'] ?? [],
                ]);

                throw new \Exception(
                    __('auth.captcha_failed_with_reason', [
                        'reason' => $this->captchaService->getErrorMessage($captchaResult['error_codes'] ?? []),
                    ])
                );
            }

            logger()->info('Student login CAPTCHA passed', [
                'student_id' => $studentId,
                'ip' => $ipAddress,
                'score' => $captchaResult['score'],
            ]);
        }

        // Find student
        $student = EStudent::where('student_id_number', $studentId)
            ->where('active', true)
            ->with('meta.specialty', 'meta.group')
            ->first();

        // Check password
        if (!$student || !Hash::check($password, $student->password)) {
            SystemLogin::logFailure($studentId, SystemLogin::TYPE_LOGIN);
            throw new \Exception(__('auth.invalid_student_credentials'));
        }

        // Check student status
        $meta = $student->meta;
        if ($meta) {
            $blockedStatuses = ['13', '15']; // 13: Expelled, 15: Dropped out
            if (in_array($meta->_student_status, $blockedStatuses)) {
                $statusMessages = [
                    '13' => __('auth.student_status_expelled'),
                    '15' => __('auth.student_status_dropout'),
                ];
                $statusName = $statusMessages[$meta->_student_status] ?? __('auth.status_unknown');

                SystemLogin::logFailure($studentId, SystemLogin::TYPE_LOGIN);
                throw new \Exception(__('auth.status_blocked', ['status' => $statusName]));
            }
        }

        // Generate tokens
        $token = auth('student-api')->login($student);

        // Log successful login
        SystemLogin::logSuccess($studentId, SystemLogin::TYPE_LOGIN, $student->id);

        $refreshToken = $this->refreshTokenService->createForUser(
            $student->id,
            AuthRefreshToken::TYPE_STUDENT,
            $ipAddress,
            $userAgent
        );

        return [
            'student' => $student,
            'token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Refresh authentication token
     *
     * @return array
     * @throws \Exception
     */
    public function refreshToken(string $refreshToken, string $ipAddress, ?string $userAgent = null): array
    {
        $record = $this->refreshTokenService->findValid($refreshToken, AuthRefreshToken::TYPE_STUDENT);

        if (!$record) {
            throw new \Exception(__('auth.token_refresh_failed'));
        }

        $student = EStudent::where('id', $record->user_id)
            ->where('active', true)
            ->with('meta.specialty', 'meta.group')
            ->first();

        if (!$student) {
            $this->refreshTokenService->revokeByToken($refreshToken, AuthRefreshToken::TYPE_STUDENT);
            throw new \Exception(__('auth.user_not_found'));
        }

        $accessToken = auth('student-api')->login($student);

        $newRefreshToken = $this->refreshTokenService->rotate(
            $record,
            $ipAddress,
            $userAgent
        );

        SystemLogin::logSuccess($student->student_id_number, SystemLogin::TYPE_REFRESH, $student->id);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Logout student
     *
     * @return void
     * @throws \Exception
     */
    public function logout(?string $refreshToken = null): void
    {
        try {
            $student = auth('student-api')->user();
            if ($student) {
                SystemLogin::logSuccess($student->student_id_number, SystemLogin::TYPE_LOGOUT, $student->id);
            }

            auth('student-api')->logout();
        } catch (\Exception $e) {
            throw new \Exception(__('auth.logout_failed'));
        } finally {
            if ($refreshToken) {
                $this->refreshTokenService->revokeByToken($refreshToken, AuthRefreshToken::TYPE_STUDENT);
            } elseif (isset($student)) {
                $this->refreshTokenService->revokeAllForUser($student->id, AuthRefreshToken::TYPE_STUDENT);
            }
        }
    }

    /**
     * Request password reset
     *
     * @param string|null $email
     * @param string|null $login
     * @return array
     */
    public function requestPasswordReset(?string $email, ?string $login): array
    {
        if (!$email && !$login) {
            throw new \Exception(__('auth.email_or_student_id_required'));
        }

        // Find student by email or student_id_number
        $studentQuery = EStudent::query()->where('active', true);
        if ($email) {
            $studentQuery->where('email', $email);
        } elseif ($login) {
            $studentQuery->where('student_id_number', $login);
        }
        $student = $studentQuery->first();

        if (!$student) {
            // Don't reveal if email exists (security best practice)
            return [
                'message' => __('auth.reset_link_email_notice'),
            ];
        }

        // Get email
        $studentEmail = $email ?? $student->email;
        if (!$studentEmail) {
            return [
                'message' => __('auth.reset_link_generic_notice'),
            ];
        }

        // Delete any existing tokens for this user
        PasswordResetToken::deleteForUser($studentEmail, 'student');

        // Generate reset token
        $token = Str::random(64);
        $expiresMinutes = (int) env('PASSWORD_RESET_EXPIRE_MINUTES', 60);

        // Store token
        PasswordResetToken::create([
            'email' => $studentEmail,
            'token' => $token,
            'user_type' => 'student',
            'expires_at' => now()->addMinutes($expiresMinutes),
        ]);

        // Generate reset link
        $resetLink = config('app.frontend_url') . "/reset-password?token={$token}&email={$studentEmail}";

        logger()->info('Password reset requested', [
            'email' => $studentEmail,
            'user_type' => 'student',
            'token' => $token,
            'reset_link' => $resetLink,
        ]);

        return [
            'message' => __('auth.reset_link_success'),
            'debug' => app()->environment('local') ? [
                'token' => $token,
                'reset_link' => $resetLink,
            ] : null,
        ];
    }

    /**
     * Reset password using token
     *
     * @param string $email
     * @param string $token
     * @param string $newPassword
     * @return void
     * @throws \Exception
     */
    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        // Find valid token
        $resetToken = PasswordResetToken::where('email', $email)
            ->where('token', $token)
            ->where('user_type', 'student')
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetToken) {
            throw new \Exception(__('auth.reset_token_invalid'));
        }

        // Find student
        $student = EStudent::where('email', $email)
            ->where('active', true)
            ->first();

        if (!$student) {
            throw new \Exception(__('auth.user_not_found'));
        }

        // Update password
        $student->password = Hash::make($newPassword);
        $student->save();

        // Delete used token and any other tokens for this user
        PasswordResetToken::deleteForUser($email, 'student');

        // Clean up expired tokens
        PasswordResetToken::deleteExpired();
    }

    /**
     * Check if 2FA is enabled
     *
     * @return bool
     */
    public function is2FAEnabled(): bool
    {
        return filter_var(env('MFA_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }
}
