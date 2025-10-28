<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\EStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'password' => 'required|string|min:6',
        ]);

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
}
