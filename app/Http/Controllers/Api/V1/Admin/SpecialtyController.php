<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SpecialtyService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Specialty Management Controller
 * MODULAR MONOLITH - Admin Module
 * ✅ CLEAN ARCHITECTURE
 */
class SpecialtyController extends Controller
{
    use ApiResponse;
    private SpecialtyService $service;

    public function __construct(SpecialtyService $service)
    {
        $this->service = $service;
    }

    /**
     * Get specialties list with filters
     *
     * @OA\Get(
     *     path="/api/v1/admin/specialties",
     *     tags={"Admin - Specialties"},
     *     summary="Get all specialties with filters",
     *     description="Returns a paginated list of academic specialties with optional filtering by department and education type",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by specialty name or code",
     *         required=false,
     *         @OA\Schema(type="string", example="Computer Science")
     *     ),
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         description="Filter by department ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="_education_type",
     *         in="query",
     *         description="Filter by education type (11=Bachelor, 12=Master, 13=PhD)",
     *         required=false,
     *         @OA\Schema(type="string", example="11")
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
     *                     @OA\Property(property="name", type="string", example="Computer Science"),
     *                     @OA\Property(property="code", type="string", example="60610100"),
     *                     @OA\Property(property="_education_type", type="string", example="11"),
     *                     @OA\Property(property="department", type="string", example="Faculty of Computer Science"),
     *                     @OA\Property(property="groups_count", type="integer", example=8),
     *                     @OA\Property(property="students_count", type="integer", example=240),
     *                     @OA\Property(property="active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=42)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getSpecialtysList($request->all());
        return $this->successResponse($result);
    }

    /**
     * Get single specialty
     *
     * @OA\Get(
     *     path="/api/v1/admin/specialties/{id}",
     *     tags={"Admin - Specialties"},
     *     summary="Get single specialty details",
     *     description="Returns detailed information about a specific specialty including groups and curriculum",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Specialty ID",
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
     *                 @OA\Property(property="name", type="string", example="Computer Science"),
     *                 @OA\Property(property="code", type="string", example="60610100"),
     *                 @OA\Property(property="_education_type", type="string", example="11"),
     *                 @OA\Property(property="department", type="object"),
     *                 @OA\Property(property="groups", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="curriculum", type="object"),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Specialty not found",
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
            $item = $this->service->getSpecialty($id);
            return $this->successResponse($item);
        } catch (\Exception $e) {
            return $this->errorResponse('Topilmadi', 404);
        }
    }

    /**
     * Create new specialty
     *
     * @OA\Post(
     *     path="/api/v1/admin/specialties",
     *     tags={"Admin - Specialties"},
     *     summary="Create a new specialty",
     *     description="Creates a new academic specialty with the provided data",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code", "department_id", "_education_type"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 maxLength=500,
     *                 example="Computer Science",
     *                 description="Specialty name"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="string",
     *                 maxLength=50,
     *                 example="60610100",
     *                 description="Unique specialty code (HEMIS code)"
     *             ),
     *             @OA\Property(
     *                 property="department_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Department ID (must exist)"
     *             ),
     *             @OA\Property(
     *                 property="_education_type",
     *                 type="string",
     *                 example="11",
     *                 description="Education type (11=Bachelor, 12=Master, 13=PhD)"
     *             ),
     *             @OA\Property(
     *                 property="duration_years",
     *                 type="integer",
     *                 nullable=true,
     *                 example=4,
     *                 description="Duration in years (e.g., 4 for Bachelor)"
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
     *         description="Specialty created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Yaratildi"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Computer Science"),
     *                 @OA\Property(property="code", type="string", example="60610100")
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
            $item = $this->service->createSpecialty($request->all());
            return $this->successResponse($item, 'Yaratildi', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update specialty
     *
     * @OA\Put(
     *     path="/api/v1/admin/specialties/{id}",
     *     tags={"Admin - Specialties"},
     *     summary="Update an existing specialty",
     *     description="Updates specialty details. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Specialty ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=500, nullable=true, example="Updated Specialty Name"),
     *             @OA\Property(property="code", type="string", maxLength=50, nullable=true, example="60610101"),
     *             @OA\Property(property="department_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="_education_type", type="string", nullable=true, example="11"),
     *             @OA\Property(property="duration_years", type="integer", nullable=true, example=4),
     *             @OA\Property(property="active", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Specialty updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Yangilandi"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Specialty not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = $this->service->updateSpecialty($id, $request->all());
            return $this->successResponse($item, 'Yangilandi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete specialty
     *
     * @OA\Delete(
     *     path="/api/v1/admin/specialties/{id}",
     *     tags={"Admin - Specialties"},
     *     summary="Delete a specialty",
     *     description="Soft deletes a specialty. The specialty will be marked as inactive.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Specialty ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Specialty deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="O'chirildi"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Specialty not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Specialty not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->service->deleteSpecialty($id);
            return $this->successResponse([], 'O\'chirildi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
