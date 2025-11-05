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
     * @OA\Get(
     *     path="/api/v1/employee/menu",
     *     summary="Get employee menu",
     *     description="Retrieve role-based menu structure for the authenticated employee",
     *     operationId="employeeMenu",
     *     tags={"Employee - Menu"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="locale",
     *         in="query",
     *         description="Language code for menu translations",
     *         required=false,
     *         @OA\Schema(type="string", example="uz", enum={"uz", "ru", "en"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Menu retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="menu",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="label", type="string", example="Dashboard"),
     *                         @OA\Property(property="icon", type="string", example="dashboard"),
     *                         @OA\Property(property="path", type="string", example="/dashboard"),
     *                         @OA\Property(property="order", type="integer", example=1),
     *                         @OA\Property(
     *                             property="children",
     *                             type="array",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=2),
     *                                 @OA\Property(property="label", type="string", example="Students"),
     *                                 @OA\Property(property="path", type="string", example="/students")
     *                             )
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to load menu",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to load menu"),
     *             @OA\Property(property="error", type="string", nullable=true, example="Service error")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('employee-api')->user();

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
     * @OA\Post(
     *     path="/api/v1/employee/menu/check-access",
     *     summary="Check menu path access",
     *     description="Verify if the authenticated employee has access to a specific menu path",
     *     operationId="employeeMenuCheckAccess",
     *     tags={"Employee - Menu"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"path"},
     *             @OA\Property(property="path", type="string", example="/students", description="Menu path to check access for")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Access check completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="path", type="string", example="/students"),
     *                 @OA\Property(property="accessible", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/v1/employee/menu/clear-cache",
     *     summary="Clear all system cache",
     *     description="Clear menu, translations, and other cached data. Similar to Yii2's /system/cache endpoint. Accessible by all authenticated users.",
     *     operationId="employeeMenuClearCache",
     *     tags={"Employee - Menu"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Cache cleared successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Tizim keshi tozalandi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Failed to clear cache",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Failed to clear cache")
     *         )
     *     )
     * )
     */
    public function clearCache(): JsonResponse
    {
        $user = auth('employee-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            // Clear all application cache (like Yii2's system/cache)
            \Illuminate\Support\Facades\Artisan::call('cache:clear');

            // Also clear specific caches
            $this->menuService->invalidateUserMenuCache($user);

            logger()->info('[MenuController] System cache cleared', [
                'user_id' => $user->id,
                'user_login' => $user->login,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tizim keshi tozalandi',
            ]);
        } catch (\Exception $e) {
            logger()->error('[MenuController] Failed to clear cache', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Keshni tozalashda xatolik',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/employee/menu/structure",
     *     summary="Get menu structure",
     *     description="Retrieve the complete menu structure (admin only)",
     *     operationId="employeeMenuStructure",
     *     tags={"Employee - Menu"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Menu structure retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="menu",
     *                     type="array",
     *                     description="Complete menu structure with all items",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Forbidden: Admin access required")
     *         )
     *     )
     * )
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


