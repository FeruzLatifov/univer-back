<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Check Permission Middleware
 *
 * PURE YII2 RESOURCE SYSTEM
 * ==========================
 *
 * Uses EXISTING Yii2 tables:
 * - e_admin (users)
 * - e_admin_role (roles)
 * - e_admin_resource (permissions)
 * - e_admin_role_resource (role-permission pivot)
 *
 * NO NEW TABLES - 100% Yii2 compatible
 * Same login, same permissions, same users as Yii2
 *
 * Usage: ->middleware('permission:student.view')
 *
 * @yii2-compatible Uses existing database
 * @microservice-ready Stateless permission checking
 */
class CheckPermission
{
    /**
     * Handle an incoming request
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @param string|null $permission Required permission (e.g., 'student.view')
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        // Get authenticated user (supports multiple guards)
        $user = auth('admin-api')->user() ?? auth('employee-api')->user();

        if (!$user) {
            Log::warning('[CheckPermission] Unauthenticated access attempt', [
                'path' => $request->path(),
                'permission' => $permission,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // If no permission specified, just check authentication
        if ($permission === null) {
            return $next($request);
        }

        // YII2 PERMISSION CHECK (using e_admin_resource)
        $hasPermission = $user->hasPermission($permission);

        if (!$hasPermission) {
            Log::warning('[CheckPermission] Unauthorized access attempt', [
                'user_id' => $user->id,
                'user_login' => $user->login ?? $user->email,
                'role_id' => $user->_role,
                'path' => $request->path(),
                'permission' => $permission,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Forbidden: You do not have permission to access this resource',
                'required_permission' => $permission,
            ], 403);
        }

        Log::info('[CheckPermission] Access granted', [
            'user_id' => $user->id,
            'permission' => $permission,
            'method' => 'yii2_resource',
        ]);

        return $next($request);
    }
}
