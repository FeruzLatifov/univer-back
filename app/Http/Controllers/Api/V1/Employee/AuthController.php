<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdminResource;
use App\Models\EAdmin;
use App\Models\PasswordResetToken;
use App\Models\EEmployee;
use App\Models\EAdminRole;
use App\Models\SystemLogin;
use App\Services\CaptchaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Employee Authentication Controller
 *
 * API Version: 1.0
 * Purpose: Xodimlar (admin, teacher, employee) uchun authentication
 */
class AuthController extends Controller
{
    /**
     * Employee login
     *
     * @route POST /api/v1/employee/auth/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'nullable|string',
        ]);

        // CAPTCHA verification (Google reCAPTCHA v3)
        $captchaService = app(CaptchaService::class);

        if ($captchaService->isEnabled()) {
            $captchaResult = $captchaService->verify(
                $request->input('captcha'),
                $request->ip(),
                'employee_login' // Action name for reCAPTCHA v3
            );

            if (!$captchaResult['success']) {
                logger()->warning('Employee login CAPTCHA failed', [
                    'login' => $request->login,
                    'ip' => $request->ip(),
                    'score' => $captchaResult['score'] ?? 0,
                    'error_codes' => $captchaResult['error_codes'] ?? [],
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'CAPTCHA verification failed. Please try again.',
                    'captcha_error' => $captchaService->getErrorMessage($captchaResult['error_codes'] ?? []),
                ], 422);
            }

            // Log CAPTCHA success with score
            logger()->info('Employee login CAPTCHA passed', [
                'login' => $request->login,
                'ip' => $request->ip(),
                'score' => $captchaResult['score'],
            ]);
        }

        $login = trim($request->login);
        $this->debug('Login attempt (employee)', ['login' => $login]);
        $admin = null;

        // Yii2 behavior: if looks like EmployeeID (numeric, >9), resolve via e_employee.employee_id_number
        if (ctype_digit($login) && strlen($login) > 9) {
            $employee = EEmployee::where('employee_id_number', $login)->first();
            if ($employee && $employee->admin) {
                $admin = $employee->admin()->with($this->withRelations())->first();
                $this->debug('Resolved via employee_id_number', ['employee_id_number' => $login, 'admin_id' => $admin?->id]);
            }
        }

        // Fallback: by login and status=enable (Yii2 Admin::findByLogin)
        if (!$admin) {
            $admin = EAdmin::query()
                ->where('login', $login)
                ->where('status', 'enable')
                ->with($this->withRelations())
                ->first();
            $this->debug('Resolved via login', ['admin_id' => $admin?->id]);
        }

        // Additional checks (Yii2 parity)
        $isTechAdmin = $admin && isset($admin->login) && $admin->login === 'techadmin';

        if ($admin && $admin->employee && !$admin->employee->active) {
            $this->debug('Employee inactive', ['admin_id' => $admin->id, 'employee_id' => $admin->employee->id]);
            return response()->json([
                'success' => false,
                'message' => 'Employee is not active. Please contact an administrator.',
            ], 403);
        }

        if ($admin && !$isTechAdmin && !$admin->employee) {
            $this->debug('Admin without employee link', ['admin_id' => $admin->id]);
            return response()->json([
                'success' => false,
                'message' => 'Employee not linked to account.',
            ], 403);
        }

        // Password check with legacy MD5 fallback (auto-upgrade to bcrypt)
        if (!$admin) {
            $this->debug('Admin not found', ['login' => $login]);

            // Log failed login attempt
            SystemLogin::logFailure($login, SystemLogin::TYPE_LOGIN);

            return response()->json([
                'success' => false,
                'message' => 'Login yoki parol noto\'g\'ri',
            ], 401);
        }

        $passwordOk = Hash::check($request->password, $admin->password);
        $this->debug('Primary bcrypt check', ['ok' => $passwordOk]);

        if (!$passwordOk) {
            $allowMd5 = filter_var(env('AUTH_ALLOW_MD5', true), FILTER_VALIDATE_BOOL);
            $looksLikeMd5 = is_string($admin->password) && strlen($admin->password) === 32 && ctype_xdigit($admin->password);
            if ($allowMd5 && $looksLikeMd5 && hash_equals($admin->password, md5($request->password))) {
                $passwordOk = true;
                // Optional upgrade to bcrypt (disabled by default to keep Yii2 compatibility)
                $upgrade = filter_var(env('AUTH_UPGRADE_MD5_TO_BCRYPT', false), FILTER_VALIDATE_BOOL);
                if ($upgrade) {
                    try {
                        $admin->password = Hash::make($request->password);
                        $admin->save();
                        $this->debug('MD5 upgraded to bcrypt', ['admin_id' => $admin->id]);
                    } catch (\Throwable $e) {
                        // ignore upgrade failure; proceed with login
                    }
                }
            } else {
                $this->debug('MD5 fallback failed or not applicable', [
                    'allow_md5' => $allowMd5,
                    'looks_like_md5' => $looksLikeMd5,
                ]);
            }
        }

        if (!$passwordOk) {
            $this->debug('Password verification failed', ['admin_id' => $admin->id]);

            // Log failed login attempt
            SystemLogin::logFailure($login, SystemLogin::TYPE_LOGIN);

            return response()->json([
                'success' => false,
                'message' => 'Login yoki parol noto\'g\'ri',
            ], 401);
        }

        // Generate token with employee guard (alias of admin provider)
        try {
            $token = auth('employee-api')->login($admin);
            $this->debug('JWT issued', ['admin_id' => $admin->id]);

            // Log successful login attempt
            SystemLogin::logSuccess($login, SystemLogin::TYPE_LOGIN, $admin->id);

            $userResource = new AdminResource($admin);
            $this->debug('AdminResource created', ['admin_id' => $admin->id]);

            $response = [
                'success' => true,
                'data' => [
                    'user' => $userResource,
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60,
                ],
            ];

            // Log the complete response for debugging
            logger()->info('[AUTH_DEBUG] Login response prepared', [
                'admin_id' => $admin->id,
                'response_structure' => [
                    'success' => $response['success'],
                    'has_user' => isset($response['data']['user']),
                    'has_token' => isset($response['data']['access_token']),
                ]
            ]);

            return response()->json($response);
        } catch (\Throwable $e) {
            logger()->error('[AUTH_DEBUG] Login failed at response generation', [
                'admin_id' => $admin->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login yoki parol noto\'g\'ri',
                'debug' => app()->environment('local') ? [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ] : null,
            ], 401);
        }
    }

    /**
     * Optional 2FA: start challenge (stub if disabled)
     * @route POST /api/v1/employee/auth/2fa/challenge
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
     * @route POST /api/v1/employee/auth/2fa/verify
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
     * Get authenticated employee info
     *
     * @route GET /api/v1/employee/auth/me
     */
    public function me()
    {
        $admin = auth('employee-api')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Employee topilmadi',
            ], 404);
        }

        $admin->load($this->withRelations());

        return response()->json([
            'success' => true,
            'data' => new AdminResource($admin),
        ]);
    }

    private function withRelations(): array
    {
        $relations = ['employee', 'role'];
        try {
            if (Schema::hasTable('e_admin_roles')) {
                $relations[] = 'roles';
            }
            if (class_exists(\App\Models\EStructure::class) && Schema::hasTable('e_structure')) {
                $relations[] = 'structure';
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $relations;
    }

    private function debug(string $message, array $context = []): void
    {
        try {
            if (filter_var(env('AUTH_DEBUG', false), FILTER_VALIDATE_BOOL)) {
                logger()->info('[AUTH_DEBUG] ' . $message, $context);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Refresh token
     *
     * @route POST /api/v1/employee/auth/refresh
     */
    public function refresh()
    {
        try {
            $token = auth('employee-api')->refresh();

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
     * Logout employee
     *
     * @route POST /api/v1/employee/auth/logout
     */
    public function logout()
    {
        try {
            auth('employee-api')->logout();

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
     * Switch active role for the authenticated employee
     * @route POST /api/v1/employee/auth/role/switch
     */
    public function switchRole(Request $request)
    {
        $request->validate([
            'role' => 'required|integer',
        ]);

        $admin = auth('employee-api')->user();
        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Staff topilmadi',
            ], 401);
        }

        $roleId = (int) $request->input('role');
        $role = EAdminRole::find($roleId);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol topilmadi',
            ], 404);
        }

        // If pivot table exists, ensure the admin actually has this role
        if (Schema::hasTable('e_admin_roles')) {
            $hasRole = DB::table('e_admin_roles')
                ->where('_admin', $admin->id)
                ->where('_role', $role->id)
                ->exists();
            if (!$hasRole) {
                return response()->json([
                    'success' => false,
                    'message' => "Sizda '{$role->name}' roli mavjud emas",
                ], 403);
            }
        }

        // Switch role
        $admin->_role = $role->id;
        $admin->save();

        $admin->load($this->withRelations());

        // Issue fresh token with updated claims
        $token = auth('employee-api')->login($admin);

        return response()->json([
            'success' => true,
            'message' => "'{$role->name}' roliga o'tildi",
            'data' => [
                'user' => new AdminResource($admin),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    /**
     * Forgot password - send reset token
     *
     * @route POST /api/admin/auth/forgot-password
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
                'message' => 'Email yoki login talab qilinadi',
            ], 422);
        }

        // Find employee by email or login
        $staffQuery = EAdmin::query()
            ->where('status', 'enable');

        if ($request->filled('email')) {
            $staffQuery->where('email', $request->email);
        } elseif ($request->filled('login')) {
            $staffQuery->where('login', $request->login);
        }
        $staff = $staffQuery->first();

        if (!$staff) {
            // Don't reveal if email exists (security best practice)
            return response()->json([
                'success' => true,
                'message' => 'Agar email topilsa, parolni tiklash uchun havola yuboriladi',
            ]);
        }

        // Delete any existing tokens for this user
        $email = $request->email ?? $staff?->email;
        if (!$email) {
            // No email available → return generic success (no enumeration)
            return response()->json([
                'success' => true,
                'message' => 'Agar maʼlumot topilsa, parolni tiklash uchun havola yuboriladi',
            ]);
        }

        PasswordResetToken::deleteForUser($email, 'employee');

        // Generate reset token
        $token = Str::random(64);
        $expiresMinutes = (int) env('PASSWORD_RESET_EXPIRE_MINUTES', 60);

        // Store token
        PasswordResetToken::create([
            'email' => $email,
            'token' => $token,
            'user_type' => 'employee',
            'expires_at' => now()->addMinutes($expiresMinutes),
        ]);

        // TODO: Send email with reset link containing $token
        // For now, return token in response (ONLY for development)
        $resetLink = config('app.frontend_url') . "/reset-password?token={$token}&email={$email}";

        logger()->info('Password reset requested', [
            'email' => $email,
            'user_type' => 'employee',
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
     * @route POST /api/admin/auth/reset-password
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
            ->where('user_type', 'staff')
            ->where('expires_at', '>', now())
            ->first();

        if (!$resetToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token noto\'g\'ri yoki muddati tugagan',
            ], 400);
        }

        // Find employee
        $staff = EAdmin::where('email', $request->email)
            ->where('status', 'enable')
            ->first();

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Foydalanuvchi topilmadi',
            ], 404);
        }

        // Update password
        $staff->password = Hash::make($request->password);
        $staff->save();

        // Delete used token and any other tokens for this user
        PasswordResetToken::deleteForUser($request->email, 'employee');

        // Clean up expired tokens
        PasswordResetToken::deleteExpired();

        return response()->json([
            'success' => true,
            'message' => 'Parol muvaffaqiyatli o\'zgartirildi. Endi tizimga kirishingiz mumkin.',
        ]);
    }
}


