<?php

namespace App\Services\Student;

use App\Models\EStudent;
use App\Models\PasswordResetToken;
use App\Models\SystemLogin;
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
    private CaptchaService $captchaService;

    public function __construct(CaptchaService $captchaService)
    {
        $this->captchaService = $captchaService;
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
    public function attemptLogin(string $studentId, string $password, ?string $captcha, string $ipAddress): array
    {
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

                throw new \Exception('CAPTCHA verification failed: ' .
                    $this->captchaService->getErrorMessage($captchaResult['error_codes'] ?? []));
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
            throw new \Exception('Student ID yoki parol noto\'g\'ri');
        }

        // Check student status
        $meta = $student->meta;
        if ($meta) {
            $blockedStatuses = ['13', '15']; // 13: Expelled, 15: Dropped out
            if (in_array($meta->_student_status, $blockedStatuses)) {
                $statusMessages = [
                    '13' => 'O\'qishdan chetlashtirilgan',
                    '15' => 'O\'qishni to\'xtatgan',
                ];
                $statusName = $statusMessages[$meta->_student_status] ?? 'Noma\'lum';

                SystemLogin::logFailure($studentId, SystemLogin::TYPE_LOGIN);
                throw new \Exception("Sizning statusingiz: {$statusName}. Tizimga kirish mumkin emas.");
            }
        }

        // Generate token
        $token = auth('student-api')->login($student);

        // Log successful login
        SystemLogin::logSuccess($studentId, SystemLogin::TYPE_LOGIN, $student->id);

        return [
            'student' => $student,
            'token' => $token,
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
    public function refreshToken(): array
    {
        try {
            $token = auth('student-api')->refresh();

            return [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Token refresh failed');
        }
    }

    /**
     * Logout student
     *
     * @return void
     * @throws \Exception
     */
    public function logout(): void
    {
        try {
            auth('student-api')->logout();
        } catch (\Exception $e) {
            throw new \Exception('Logout failed');
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
            throw new \Exception('Email yoki Student ID talab qilinadi');
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
                'message' => 'Agar email topilsa, parolni tiklash uchun havola yuboriladi',
            ];
        }

        // Get email
        $studentEmail = $email ?? $student->email;
        if (!$studentEmail) {
            return [
                'message' => 'Agar maʼlumot topilsa, parolni tiklash uchun havola yuboriladi',
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
            'message' => 'Parolni tiklash uchun havola emailingizga yuborildi',
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
            throw new \Exception('Token noto\'g\'ri yoki muddati tugagan');
        }

        // Find student
        $student = EStudent::where('email', $email)
            ->where('active', true)
            ->first();

        if (!$student) {
            throw new \Exception('Foydalanuvchi topilmadi');
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
