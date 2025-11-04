<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Contracts\Services\MenuServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Menu Controller (API V1)
 *
 * Handles menu-related API endpoints
 * Uses dependency injection for service (SOLID principles)
 *
 * @microservice-ready Thin controller, business logic in service
 */
class MenuController extends Controller
{
    public function __construct(
        protected MenuServiceInterface $menuService
    ) {
        // Middleware applied in routes
    }

    /**
     * Get menu for authenticated employee
     *
     * @route GET /api/v1/employee/menu
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('admin-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Check for locale in query params, X-Locale header, Accept-Language header, or use app default
        $locale = $request->input('locale')
            ?? $request->header('X-Locale')
            ?? $request->header('Accept-Language')
            ?? app()->getLocale();

        try {
            $menuResponse = $this->menuService->getMenuForUser($user, $locale);

            return response()->json($menuResponse->toArray());
        } catch (\Exception $e) {
            logger()->error('[MenuController] Failed to get menu', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load menu',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Check if user can access a path
     *
     * @route POST /api/v1/employee/menu/check-access
     * @authenticated
     */
    public function checkAccess(Request $request): JsonResponse
    {
        $user = auth('admin-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');
        $accessible = $this->menuService->canUserAccessPath($user, $path);

        return response()->json([
            'success' => true,
            'data' => [
                'path' => $path,
                'accessible' => $accessible,
            ],
        ]);
    }

    /**
     * Invalidate menu cache for current user
     *
     * @route POST /api/v1/employee/menu/clear-cache
     * @authenticated
     */
    public function clearCache(): JsonResponse
    {
        $user = auth('admin-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $this->menuService->invalidateUserMenuCache($user);

            return response()->json([
                'success' => true,
                'message' => 'Menu cache cleared',
            ]);
        } catch (\Exception $e) {
            logger()->error('[MenuController] Failed to clear cache', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache',
            ], 500);
        }
    }

    /**
     * Get menu structure (admin only)
     *
     * @route GET /api/v1/employee/menu/structure
     * @authenticated
     */
    public function structure(): JsonResponse
    {
        $user = auth('admin-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        // Only allow super admin
        if ($user->login !== 'admin' && $user->login !== 'techadmin') {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Admin access required',
            ], 403);
        }

        $structure = $this->menuService->getMenuStructure();

        return response()->json([
            'success' => true,
            'data' => [
                'menu' => $structure,
            ],
        ]);
    }
}


