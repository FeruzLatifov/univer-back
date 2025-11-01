<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\EStudent;
use App\Models\PasswordResetToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Student Authentication Controller
 *
 * API Version: 1.0
 * Purpose: Talabalar uchun authentication (login, logout, refresh, me)
 */
class AuthController extends Controller
{
    /**
     * Student login
     *
     * @route POST /api/v1/student/auth/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'nullable|string',
        ]);

        // Optional CAPTCHA validation
        if (filter_var(env('CAPTCHA_ENABLED', false), FILTER_VALIDATE_BOOL)) {
            if (!$request->filled('captcha')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Captcha talab qilinadi',
                ], 422);
            }
            // TODO: Verify captcha token via provider
        }

        $student = EStudent::where('student_id_number', $request->student_id)
            ->where('active', true)
            ->with('meta.specialty', 'meta.group')
            ->first();

        // Check password
        if (!$student || !Hash::check($request->password, $student->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID yoki parol noto\'g\'ri',
            ], 401);
        }

        // Check student status - prevent login for expelled or dropped out students
        $meta = $student->meta;
        if ($meta) {
            $blockedStatuses = ['13', '15']; // 13: Expelled, 15: Dropped out
            if (in_array($meta->_student_status, $blockedStatuses)) {
                $statusMessages = [
                    '13' => 'O\'qishdan chetlashtirilgan',
                    '15' => 'O\'qishni to\'xtatgan',
                ];
                $statusName = $statusMessages[$meta->_student_status] ?? 'Noma\'lum';

                return response()->json([
                    'success' => false,
                    'message' => "Sizning statusingiz: {$statusName}. Tizimga kirish mumkin emas.",
                    'student_status' => $meta->_student_status,
                ], 403);
            }
        }

        // Generate token with student guard
        $token = auth('student-api')->login($student);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new StudentResource($student),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    /**
     * Optional 2FA: start challenge (stub if disabled)
     * @route POST /api/v1/student/auth/2fa/challenge
     */
    public function twoFAChallenge(Request $request)
    {
        $enabled = filter_var(env('MFA_ENABLED', false), FILTER_VALIDATE_BOOL);
        if (!$enabled) {
            return response()->json([
                'success' => true,
                'mfa_required' => false,
            ]);
        }

        // TODO: Send TOTP/SMS code based on user preference
        return response()->json([
            'success' => true,
            'mfa_required' => true,
            'method' => 'totp',
        ]);
    }

    /**
     * Optional 2FA: verify challenge (stub if disabled)
     * @route POST /api/v1/student/auth/2fa/verify
     */
    public function twoFAVerify(Request $request)
    {
        $enabled = filter_var(env('MFA_ENABLED', false), FILTER_VALIDATE_BOOL);
        if (!$enabled) {
            return response()->json([
                'success' => true,
                'message' => '2FA disabled',
            ]);
        }

        $request->validate([
            'code' => 'required|string',
        ]);

        // TODO: Verify code
        return response()->json([
            'success' => true,
            'message' => '2FA verified',
        ]);
    }

    /**
     * Get authenticated student info
     *
     * @route GET /api/v1/student/auth/me
     */
    public function me()
    {
        $student = auth('student-api')->user();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student topilmadi',
            ], 404);
        }

        $student->load('meta.specialty', 'meta.group');

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
        ]);
    }

    /**
     * Refresh token
     *
     * @route POST /api/v1/student/auth/refresh
     */
    public function refresh()
    {
        try {
            $token = auth('student-api')->refresh();

            return response()->json([
                'success' => true,
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
            ], 401);
        }
    }

    /**
     * Logout student
     *
     * @route POST /api/v1/student/auth/logout
     */
    public function logout()
    {
        try {
            auth('student-api')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
            ], 500);
        }
    }

    /**
     * Forgot password - send reset token
     *
     * @route POST /api/student/auth/forgot-password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'nullable|email',
            'login' => 'nullable|string',
        ]);

        if (!$request->filled('email') && !$request->filled('login')) {
            return response()->json([
                'success' => false,
                'message' => 'Email yoki Student ID talab qilinadi',
            ], 422);
        }

        // Find student by email or student_id_number
        $studentQuery = EStudent::query()->where('active', true);
        if ($request->filled('email')) {
            $studentQuery->where('email', $request->email);
        } elseif ($request->filled('login')) {
            $studentQuery->where('student_id_number', $request->login);
        }
        $student = $studentQuery->first();

        if (!$student) {
            // Don't reveal if email exists (security best practice)
            return response()->json([
                'success' => true,
                'message' => 'Agar email topilsa, parolni tiklash uchun havola yuboriladi',
            ]);
        }

        // Delete any existing tokens for this user
        $email = $request->email ?? $student?->email;
        if (!$email) {
            return response()->json([
                'success' => true,
                'message' => 'Agar maʼlumot topilsa, parolni tiklash uchun havola yuboriladi',
            ]);
        }

        PasswordResetToken::deleteForUser($email, 'student');

        // Generate reset token
        $token = Str::random(64);
        $expiresMinutes = (int) env('PASSWORD_RESET_EXPIRE_MINUTES', 60);

        // Store token
        PasswordResetToken::create([
            'email' => $email,
            'token' => $token,
            'user_type' => 'student',
            'expires_at' => now()->addMinutes($expiresMinutes),
        ]);

        // TODO: Send email with reset link containing $token
        // For now, return token in response (ONLY for development)
        $resetLink = config('app.frontend_url') . "/reset-password?token={$token}&email={$email}";

        logger()->info('Password reset requested', [
            'email' => $email,
            'user_type' => 'student',
            'token' => $token,
            'reset_link' => $resetLink,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Parolni tiklash uchun havola emailingizga yuborildi',
            // Remove these in production!
            'debug' => app()->environment('local') ? [
                'token' => $token,
                'reset_link' => $resetLink,
            ] : null,
        ]);
    }

    /**
     * Reset password using token
     *
     * @route POST /api/student/auth/reset-password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Find valid token
        $resetToken = PasswordResetToken::where('email', $request->email)
            ->where('token', $request->token)
            ->where('user_type', 'student')
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token noto\'g\'ri yoki muddati tugagan',
            ], 400);
        }

        // Find student
        $student = EStudent::where('email', $request->email)
            ->where('active', true)
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Foydalanuvchi topilmadi',
            ], 404);
        }

        // Update password
        $student->password = Hash::make($request->password);
        $student->save();

        // Delete used token and any other tokens for this user
        PasswordResetToken::deleteForUser($request->email, 'student');

        // Clean up expired tokens
        PasswordResetToken::deleteExpired();

        return response()->json([
            'success' => true,
            'message' => 'Parol muvaffaqiyatli o\'zgartirildi. Endi tizimga kirishingiz mumkin.',
        ]);
    }
}
