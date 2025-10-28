<?php

namespace App\Http\Controllers\Api\V1\Staff;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdminResource;
use App\Models\EAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Staff Authentication Controller
 *
 * API Version: 1.0
 * Purpose: Xodimlar (admin, teacher, employee) uchun authentication
 */
class AuthController extends Controller
{
    /**
     * Staff login
     *
     * @route POST /api/v1/staff/auth/login
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $admin = EAdmin::where('login', $request->login)
            ->where('active', true)
            ->with('employee.structure')
            ->first();

        // Check password
        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Login yoki parol noto\'g\'ri',
            ], 401);
        }

        // Generate token with staff guard
        $token = auth('staff-api')->login($admin);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => new AdminResource($admin),
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60,
            ],
        ]);
    }

    /**
     * Get authenticated staff info
     *
     * @route GET /api/v1/staff/auth/me
     */
    public function me()
    {
        $admin = auth('staff-api')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Staff topilmadi',
            ], 404);
        }

        $admin->load('employee.structure');

        return response()->json([
            'success' => true,
            'data' => new AdminResource($admin),
        ]);
    }

    /**
     * Refresh token
     *
     * @route POST /api/v1/staff/auth/refresh
     */
    public function refresh()
    {
        try {
            $token = auth('staff-api')->refresh();

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
     * Logout staff
     *
     * @route POST /api/v1/staff/auth/logout
     */
    public function logout()
    {
        try {
            auth('staff-api')->logout();

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
