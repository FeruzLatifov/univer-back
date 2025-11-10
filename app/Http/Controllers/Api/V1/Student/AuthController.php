<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StudentResource;
use App\Services\Student\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Student Authentication Controller
 *
 * MODULAR MONOLITH - Student Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Student
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Auth Service (injected)
     */
    private AuthService $authService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Student login
     *
     * @OA\Post(
     *     path="/api/v1/student/auth/login",
     *     tags={"Student - Authentication"},
     *     summary="Student login",
     *     description="Authenticate student with student ID and password. Returns JWT access token and student information.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"student_id", "password"},
     *             @OA\Property(property="student_id", type="string", example="20210001", description="Student ID number"),
     *             @OA\Property(property="password", type="string", format="password", example="password123", description="Student password"),
     *             @OA\Property(property="captcha", type="string", nullable=true, example="abc123", description="Optional CAPTCHA code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=123),
     *                     @OA\Property(property="student_id", type="string", example="20210001"),
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="email", type="string", example="john.doe@university.edu")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *                 @OA\Property(property="refresh_token", type="string", example="6d1b1c4f..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600, description="Token expiration time in seconds")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid credentials")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Account inactive",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Your account status is inactive")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or CAPTCHA required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="CAPTCHA verification required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'nullable|string',
        ]);

        try {
            // Delegate to service
            $result = $this->authService->attemptLogin(
                $request->student_id,
                $request->password,
                $request->captcha,
                $request->ip(),
                $request->userAgent()
            );

            return $this->successResponse([
                'user' => new StudentResource($result['student']),
                'access_token' => $result['token'],
                'refresh_token' => $result['refresh_token'],
                'token_type' => $result['token_type'],
                'expires_in' => $result['expires_in'],
            ]);

        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'CAPTCHA')) {
                return $this->errorResponse($e->getMessage(), 422);
            }
            if (str_contains($e->getMessage(), 'statusingiz')) {
                return $this->errorResponse($e->getMessage(), 403);
            }
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    /**
     * Optional 2FA: start challenge
     *
     * @OA\Post(
     *     path="/api/v1/student/auth/2fa/challenge",
     *     tags={"Student - Authentication"},
     *     summary="Start 2FA challenge",
     *     description="Initiates two-factor authentication challenge. Returns whether MFA is required and the authentication method.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Challenge initiated or 2FA disabled",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="mfa_required", type="boolean", example=true),
     *                 @OA\Property(property="method", type="string", enum={"totp", "sms"}, example="totp", description="2FA method (only if mfa_required is true)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function twoFAChallenge(Request $request): JsonResponse
    {
        if (!$this->authService->is2FAEnabled()) {
            return $this->successResponse([
                'mfa_required' => false,
            ]);
        }

        // TODO: Send TOTP/SMS code based on user preference
        return $this->successResponse([
            'mfa_required' => true,
            'method' => 'totp',
        ]);
    }

    /**
     * Optional 2FA: verify challenge
     *
     * @OA\Post(
     *     path="/api/v1/student/auth/2fa/verify",
     *     tags={"Student - Authentication"},
     *     summary="Verify 2FA code",
     *     description="Verifies the two-factor authentication code provided by the student",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="123456", description="6-digit 2FA code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="2FA verified successfully or disabled",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="message", type="string", example="2FA verified")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The code field is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function twoFAVerify(Request $request): JsonResponse
    {
        if (!$this->authService->is2FAEnabled()) {
            return $this->successResponse([], '2FA disabled');
        }

        $request->validate([
            'code' => 'required|string',
        ]);

        // TODO: Verify code
        return $this->successResponse([], '2FA verified');
    }

    /**
     * Get authenticated student info
     *
     * @OA\Get(
     *     path="/api/v1/student/auth/me",
     *     tags={"Student - Authentication"},
     *     summary="Get authenticated student information",
     *     description="Returns the authenticated student's profile information including meta data, specialty, and group",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="student_id", type="string", example="20210001"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="email", type="string", example="john.doe@university.edu"),
     *                 @OA\Property(
     *                     property="meta",
     *                     type="object",
     *                     @OA\Property(
     *                         property="specialty",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="name", type="string", example="Computer Science")
     *                     ),
     *                     @OA\Property(
     *                         property="group",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="name", type="string", example="CS-101")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student topilmadi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function me(): JsonResponse
    {
        $student = auth('student-api')->user();

        if (!$student) {
            return $this->errorResponse('Student topilmadi', 404);
        }

        $student->load('meta.specialty', 'meta.group');

        return $this->successResponse(new StudentResource($student));
    }

    /**
     * Refresh token
     *
     * @OA\Post(
     *     path="/api/v1/student/auth/refresh",
     *     tags={"Student - Authentication"},
     *     summary="Refresh JWT token",
     *     description="Refreshes the authentication token and returns a new access token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *                 @OA\Property(property="refresh_token", type="string", example="6d1b1c4f..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated or invalid token"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token');

        if (!$refreshToken) {
            return $this->errorResponse(__('auth.token_refresh_failed'), 422);
        }

        try {
            $result = $this->authService->refreshToken(
                $refreshToken,
                $request->ip(),
                $request->userAgent()
            );

            return $this->successResponse($result);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    /**
     * Logout student
     *
     * @OA\Post(
     *     path="/api/v1/student/auth/logout",
     *     tags={"Student - Authentication"},
     *     summary="Logout student",
     *     description="Invalidates the current JWT token and logs out the student",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="refresh_token", type="string", example="6d1b1c4f...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logged out successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="message", type="string", example="Successfully logged out")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $refreshToken = $request->input('refresh_token') ?? $request->bearerToken();

        try {
            $this->authService->logout($refreshToken);

            return $this->successResponse([], 'Successfully logged out');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Forgot password - send reset token
     *
     * @OA\Post(
     *     path="/api/v1/student/auth/forgot-password",
     *     tags={"Student - Authentication"},
     *     summary="Request password reset",
     *     description="Sends a password reset token to the student's email or phone. Requires either email or login (student ID).",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="student@university.edu", description="Student email address"),
     *             @OA\Property(property="login", type="string", nullable=true, example="20210001", description="Student ID/login")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Reset token sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", description="Debug information (if available)"),
     *             @OA\Property(property="message", type="string", example="Password reset token sent to your email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error or email not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email or login is required")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'nullable|email',
            'login' => 'nullable|string',
        ]);

        try {
            $result = $this->authService->requestPasswordReset(
                $request->email,
                $request->login
            );

            return $this->successResponse($result['debug'] ?? [], $result['message']);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    /**
     * Reset password using token
     *
     * @OA\Post(
     *     path="/api/v1/student/auth/reset-password",
     *     tags={"Student - Authentication"},
     *     summary="Reset password with token",
     *     description="Resets the student's password using the token received via email/SMS",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "token", "password", "password_confirmation"},
     *             @OA\Property(property="email", type="string", format="email", example="student@university.edu", description="Student email address"),
     *             @OA\Property(property="token", type="string", example="abc123xyz789", description="Password reset token"),
     *             @OA\Property(property="password", type="string", format="password", minLength=6, example="newPassword123", description="New password (min 6 characters)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newPassword123", description="Password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password reset successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="message", type="string", example="Parol muvaffaqiyatli o'zgartirildi. Endi tizimga kirishingiz mumkin.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid token or request",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid or expired token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The password field is required")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        try {
            $this->authService->resetPassword(
                $request->email,
                $request->token,
                $request->password
            );

            return $this->successResponse(
                [],
                'Parol muvaffaqiyatli o\'zgartirildi. Endi tizimga kirishingiz mumkin.'
            );

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
