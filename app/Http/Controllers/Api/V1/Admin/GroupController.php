<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\GroupService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Group Management Controller
 * MODULAR MONOLITH - Admin Module
 * ✅ CLEAN ARCHITECTURE
 */
class GroupController extends Controller
{
    use ApiResponse;
    private GroupService $service;

    public function __construct(GroupService $service)
    {
        $this->service = $service;
    }

    /**
     * Get groups list with filters
     *
     * @OA\Get(
     *     path="/api/v1/admin/groups",
     *     tags={"Admin - Groups"},
     *     summary="Get all academic groups with filters",
     *     description="Returns a paginated list of academic groups with optional filtering by specialty, level, and status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by group name or code",
     *         required=false,
     *         @OA\Schema(type="string", example="CS-101")
     *     ),
     *     @OA\Parameter(
     *         name="specialty_id",
     *         in="query",
     *         description="Filter by specialty ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="_level",
     *         in="query",
     *         description="Filter by education level (1-4 for Bachelor, 5-6 for Master)",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="active",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean", example=true)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="CS-101"),
     *                     @OA\Property(property="code", type="string", example="CS101"),
     *                     @OA\Property(property="_level", type="integer", example=1),
     *                     @OA\Property(property="specialty", type="string", example="Computer Science"),
     *                     @OA\Property(property="students_count", type="integer", example=30),
     *                     @OA\Property(property="active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=8),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=112)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getGroupsList($request->all());
        return $this->successResponse($result);
    }

    /**
     * Get single group
     *
     * @OA\Get(
     *     path="/api/v1/admin/groups/{id}",
     *     tags={"Admin - Groups"},
     *     summary="Get single group details",
     *     description="Returns detailed information about a specific academic group including students list",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Group ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="CS-101"),
     *                 @OA\Property(property="code", type="string", example="CS101"),
     *                 @OA\Property(property="_level", type="integer", example=1),
     *                 @OA\Property(property="specialty", type="object"),
     *                 @OA\Property(property="students", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="students_count", type="integer", example=30),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Group not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Topilmadi")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show($id): JsonResponse
    {
        try {
            $item = $this->service->getGroup($id);
            return $this->successResponse($item);
        } catch (\Exception $e) {
            return $this->errorResponse('Topilmadi', 404);
        }
    }

    /**
     * Create new group
     *
     * @OA\Post(
     *     path="/api/v1/admin/groups",
     *     tags={"Admin - Groups"},
     *     summary="Create a new academic group",
     *     description="Creates a new academic group with the provided data",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code", "specialty_id", "_level"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 maxLength=255,
     *                 example="CS-101",
     *                 description="Group name"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="string",
     *                 maxLength=50,
     *                 example="CS101",
     *                 description="Unique group code"
     *             ),
     *             @OA\Property(
     *                 property="specialty_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Specialty ID (must exist)"
     *             ),
     *             @OA\Property(
     *                 property="_level",
     *                 type="integer",
     *                 minimum=1,
     *                 maximum=6,
     *                 example=1,
     *                 description="Education level (1-4 for Bachelor, 5-6 for Master)"
     *             ),
     *             @OA\Property(
     *                 property="_education_year",
     *                 type="integer",
     *                 nullable=true,
     *                 example=2024,
     *                 description="Education year"
     *             ),
     *             @OA\Property(
     *                 property="active",
     *                 type="boolean",
     *                 example=true,
     *                 description="Active status"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Group created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Yaratildi"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="CS-101"),
     *                 @OA\Property(property="code", type="string", example="CS101")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $item = $this->service->createGroup($request->all());
            return $this->successResponse($item, 'Yaratildi', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update group
     *
     * @OA\Put(
     *     path="/api/v1/admin/groups/{id}",
     *     tags={"Admin - Groups"},
     *     summary="Update an existing group",
     *     description="Updates group details. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Group ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255, nullable=true, example="CS-101 Updated"),
     *             @OA\Property(property="code", type="string", maxLength=50, nullable=true, example="CS101U"),
     *             @OA\Property(property="specialty_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="_level", type="integer", minimum=1, maximum=6, nullable=true, example=2),
     *             @OA\Property(property="_education_year", type="integer", nullable=true, example=2024),
     *             @OA\Property(property="active", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Yangilandi"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Group not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = $this->service->updateGroup($id, $request->all());
            return $this->successResponse($item, 'Yangilandi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete group
     *
     * @OA\Delete(
     *     path="/api/v1/admin/groups/{id}",
     *     tags={"Admin - Groups"},
     *     summary="Delete a group",
     *     description="Soft deletes a group. The group will be marked as inactive.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Group ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="O'chirildi"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Group not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Group not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->service->deleteGroup($id);
            return $this->successResponse([], 'O\'chirildi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
