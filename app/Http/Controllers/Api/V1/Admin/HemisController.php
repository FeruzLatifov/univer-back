<?php
namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\HemisService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * HEMIS Integration Controller
 * MODULAR MONOLITH - Admin Module
 * ✅ CLEAN ARCHITECTURE
 */
class HemisController extends Controller
{
    use ApiResponse;
    private HemisService $service;

    public function __construct(HemisService $service) { $this->service = $service; }

    /**
     * Sync data from HEMIS
     *
     * @OA\Post(
     *     path="/api/v1/admin/hemis/sync",
     *     tags={"Admin - HEMIS Integration"},
     *     summary="Synchronize data from HEMIS system",
     *     description="Triggers data synchronization from HEMIS (Higher Education Management Information System). Can sync all entities or specific entity types.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="entity",
     *                 type="string",
     *                 example="all",
     *                 description="Entity type to sync (all, students, employees, departments, specialties, groups)",
     *                 enum={"all", "students", "employees", "departments", "specialties", "groups"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sync completed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Data synchronized successfully"),
     *                 @OA\Property(property="entity", type="string", example="all"),
     *                 @OA\Property(property="synced_at", type="string", format="date-time", example="2025-11-05 14:30:00"),
     *                 @OA\Property(
     *                     property="results",
     *                     type="object",
     *                     @OA\Property(property="students_synced", type="integer", example=1250),
     *                     @OA\Property(property="employees_synced", type="integer", example=145),
     *                     @OA\Property(property="departments_synced", type="integer", example=12),
     *                     @OA\Property(property="specialties_synced", type="integer", example=42),
     *                     @OA\Property(property="groups_synced", type="integer", example=112)
     *                 ),
     *                 @OA\Property(property="duration_seconds", type="number", example=45.2)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Sync failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="HEMIS sync failed: Connection timeout")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function sync(Request $request): JsonResponse
    {
        $entity = $request->input('entity', 'all');
        $result = $this->service->syncData($entity);
        return $this->successResponse($result);
    }

    /**
     * Get HEMIS sync status
     *
     * @OA\Get(
     *     path="/api/v1/admin/hemis/status",
     *     tags={"Admin - HEMIS Integration"},
     *     summary="Get HEMIS synchronization status",
     *     description="Returns the current status of HEMIS integration including last sync time, connection status, and sync history",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="connection_status", type="string", example="connected", enum={"connected", "disconnected", "error"}),
     *                 @OA\Property(property="last_sync_at", type="string", format="date-time", example="2025-11-05 10:00:00", nullable=true),
     *                 @OA\Property(property="last_sync_entity", type="string", example="all", nullable=true),
     *                 @OA\Property(property="last_sync_status", type="string", example="success", enum={"success", "failed", "partial"}),
     *                 @OA\Property(property="hemis_api_version", type="string", example="v2.1.5"),
     *                 @OA\Property(property="sync_in_progress", type="boolean", example=false),
     *                 @OA\Property(
     *                     property="sync_history",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="sync_at", type="string", format="date-time"),
     *                         @OA\Property(property="entity", type="string", example="students"),
     *                         @OA\Property(property="records_synced", type="integer", example=1250),
     *                         @OA\Property(property="status", type="string", example="success")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="next_scheduled_sync",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-11-06 06:00:00",
     *                     nullable=true,
     *                     description="Next automatic sync time (if scheduled)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function status(): JsonResponse
    {
        return $this->successResponse($this->service->getStatus());
    }
}
