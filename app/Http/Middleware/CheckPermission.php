<?php

namespace App\Http\Middleware;

use App\Services\Permission\PermissionCacheService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Permission Middleware
 *
 * ZERO TRUST SECURITY with Redis Caching
 * =======================================
 *
 * 10-year senior developer implementation:
 * - Server-side validation (F12 localStorage changes = useless)
 * - Redis cache for performance (10-minute TTL)
 * - Wildcard permission support (teacher.* matches teacher.dashboard.view)
 * - Multiple permission OR logic (permission:a,b,c)
 * - Student & Employee guard support
 *
 * Uses EXISTING Yii2 tables:
 * - e_admin (users)
 * - e_admin_role (roles)
 * - e_admin_resource (permissions)
 * - e_admin_role_resource (role-permission pivot)
 *
 * Usage:
 * - Single: ->middleware('permission:teacher.dashboard.view')
 * - Multiple OR: ->middleware('permission:teacher.view,admin.view')
 *
 * @yii2-compatible Uses existing database
 * @zero-trust F12 attacks cannot bypass
 */
class CheckPermission
{
    /**
     * @var PermissionCacheService
     */
    private PermissionCacheService $permissionService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(PermissionCacheService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle an incoming request
     *
     * @param Request $request
     * @param Closure $next
     * @param string|null $permissions Required permissions (comma-separated for OR logic)
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?string $permissions = null): Response
    {
        // Get authenticated user (supports multiple guards)
        $user = auth('admin-api')->user()
            ?? auth('employee-api')->user()
            ?? auth('student-api')->user();

        if (!$user) {
            Log::warning('[CheckPermission] Unauthenticated access attempt', [
                'path' => $request->path(),
                'permissions' => $permissions,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Tizimga kirish talab qilinadi',
            ], 401);
        }

        // If no permission specified, just check authentication
        if ($permissions === null || $permissions === '') {
            return $next($request);
        }

        // Determine user type
        $userType = $this->getUserType($request, $user);
        $userId = $user->id;

        // Parse permissions (comma-separated = OR logic)
        $requiredPermissions = array_map('trim', explode(',', $permissions));

        // Check if user has ANY of the required permissions (OR logic)
        $hasPermission = $this->permissionService->hasAnyPermission(
            $userId,
            $userType,
            $requiredPermissions
        );

        if (!$hasPermission) {
            Log::warning('[CheckPermission] Permission denied (Zero Trust)', [
                'user_id' => $userId,
                'user_type' => $userType,
                'user_login' => $user->login ?? $user->student_id_number ?? 'unknown',
                'role_id' => $user->_role ?? null,
                'path' => $request->path(),
                'required_permissions' => $requiredPermissions,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Bu amalni bajarish uchun ruxsatingiz yo\'q',
                'required_permissions' => $requiredPermissions,
            ], 403);
        }

        // Permission granted - continue with request
        Log::info('[CheckPermission] Access granted (cached)', [
            'user_id' => $userId,
            'user_type' => $userType,
            'granted_permissions' => $requiredPermissions,
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        return $next($request);
    }

    /**
     * Determine user type from guard or JWT claims
     *
     * @param Request $request
     * @param mixed $user
     * @return string 'employee' or 'student'
     */
    private function getUserType(Request $request, $user): string
    {
        // Check guard name first
        foreach (['employee-api', 'admin-api', 'teacher-api'] as $guardName) {
            if ($request->user($guardName)) {
                return 'employee';
            }
        }

        if ($request->user('student-api')) {
            return 'student';
        }

        // Fallback: check JWT claims
        try {
            $token = auth()->getToken();
            if ($token) {
                $payload = auth()->getPayload($token);
                $type = $payload->get('type');

                if (in_array($type, ['admin', 'employee', 'teacher'])) {
                    return 'employee';
                }

                if ($type === 'student') {
                    return 'student';
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[CheckPermission] Failed to determine user type from JWT', [
                'error' => $e->getMessage(),
            ]);
        }

        // Final fallback: check model type
        if ($user instanceof \App\Models\EAdmin) {
            return 'employee';
        }

        if ($user instanceof \App\Models\EStudent) {
            return 'student';
        }

        // Default to employee
        return 'employee';
    }
}

