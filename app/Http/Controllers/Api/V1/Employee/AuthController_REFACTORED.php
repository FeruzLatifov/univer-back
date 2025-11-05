<?php
namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Employee Authentication Controller
 * MODULAR MONOLITH - Employee Module
 * ✅ CLEAN ARCHITECTURE: Controller → Service → Model
 */
class AuthController extends Controller
{
    use ApiResponse;
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'nullable|string',
        ]);

        try {
            $result = $this->authService->attemptLogin(
                $request->login,
                $request->password,
                $request->captcha,
                $request->ip()
            );
            return $this->successResponse($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            return $this->successResponse($this->authService->refreshToken());
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();
        return $this->successResponse([], 'Logged out successfully');
    }

    public function me(): JsonResponse
    {
        return $this->successResponse(auth('api')->user());
    }
}
