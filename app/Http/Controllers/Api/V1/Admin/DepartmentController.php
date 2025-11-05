<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DepartmentResource;
use App\Services\Admin\DepartmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Department Management Controller (Admin Panel)
 *
 * MODULAR MONOLITH - Admin Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Admin
 */
class DepartmentController extends Controller
{
    use ApiResponse;

    private DepartmentService $departmentService;

    public function __construct(DepartmentService $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    /**
     * Get departments list with filters
     *
     * @OA\Get(
     *     path="/api/v1/admin/departments",
     *     tags={"Admin - Departments"},
     *     summary="Get all departments with filters",
     *     description="Returns a paginated list of departments (faculties and divisions) with optional filtering by type, parent, and status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by department name or code",
     *         required=false,
     *         @OA\Schema(type="string", example="Computer Science")
     *     ),
     *     @OA\Parameter(
     *         name="_structure_type",
     *         in="query",
     *         description="Filter by structure type",
     *         required=false,
     *         @OA\Schema(type="string", example="11")
     *     ),
     *     @OA\Parameter(
     *         name="_parent",
     *         in="query",
     *         description="Filter by parent department ID",
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
     *                     @OA\Property(property="name", type="string", example="Faculty of Computer Science"),
     *                     @OA\Property(property="code", type="string", example="FCS"),
     *                     @OA\Property(property="_structure_type", type="string", example="11"),
     *                     @OA\Property(property="_parent", type="integer", nullable=true, example=null),
     *                     @OA\Property(property="active", type="boolean", example=true),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=67)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->departmentService->getDepartmentsList($request->all());

        return $this->successResponse([
            'data' => DepartmentResource::collection($result['data']),
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Get single department
     *
     * @OA\Get(
     *     path="/api/v1/admin/departments/{id}",
     *     tags={"Admin - Departments"},
     *     summary="Get single department details",
     *     description="Returns detailed information about a specific department",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Department ID",
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
     *                 @OA\Property(property="name", type="string", example="Faculty of Computer Science"),
     *                 @OA\Property(property="code", type="string", example="FCS"),
     *                 @OA\Property(property="_structure_type", type="string", example="11"),
     *                 @OA\Property(property="_parent", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Department not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Fakultet/Bo'lim topilmadi")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show($id): JsonResponse
    {
        try {
            $department = $this->departmentService->getDepartment($id);

            return $this->successResponse(new DepartmentResource($department));

        } catch (\Exception $e) {
            return $this->errorResponse('Fakultet/Bo\'lim topilmadi', 404);
        }
    }

    /**
     * Create new department
     *
     * @OA\Post(
     *     path="/api/v1/admin/departments",
     *     tags={"Admin - Departments"},
     *     summary="Create a new department",
     *     description="Creates a new department (faculty or division) with the provided data",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "code", "_structure_type"},
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 maxLength=500,
     *                 example="Faculty of Engineering",
     *                 description="Department name"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="string",
     *                 maxLength=50,
     *                 example="FE",
     *                 description="Unique department code"
     *             ),
     *             @OA\Property(
     *                 property="_structure_type",
     *                 type="string",
     *                 maxLength=50,
     *                 example="11",
     *                 description="Structure type code (e.g., 11 for faculty)"
     *             ),
     *             @OA\Property(
     *                 property="_parent",
     *                 type="integer",
     *                 nullable=true,
     *                 example=1,
     *                 description="Parent department ID (if this is a sub-department)"
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
     *         description="Department created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fakultet/Bo'lim muvaffaqiyatli yaratildi"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Faculty of Engineering"),
     *                 @OA\Property(property="code", type="string", example="FE"),
     *                 @OA\Property(property="_structure_type", type="string", example="11"),
     *                 @OA\Property(property="_parent", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The code has already been taken")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:500',
            'code' => 'required|string|max:50|unique:e_department,code',
            '_structure_type' => 'required|string|max:50',
            '_parent' => 'nullable|integer|exists:e_department,id',
            'active' => 'sometimes|boolean',
        ]);

        try {
            $department = $this->departmentService->createDepartment($validated);

            return $this->successResponse(
                new DepartmentResource($department),
                'Fakultet/Bo\'lim muvaffaqiyatli yaratildi',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update department
     *
     * @OA\Put(
     *     path="/api/v1/admin/departments/{id}",
     *     tags={"Admin - Departments"},
     *     summary="Update an existing department",
     *     description="Updates department details. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Department ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="name",
     *                 type="string",
     *                 maxLength=500,
     *                 nullable=true,
     *                 example="Updated Faculty Name",
     *                 description="Department name"
     *             ),
     *             @OA\Property(
     *                 property="code",
     *                 type="string",
     *                 maxLength=50,
     *                 nullable=true,
     *                 example="UFC",
     *                 description="Department code"
     *             ),
     *             @OA\Property(
     *                 property="_structure_type",
     *                 type="string",
     *                 maxLength=50,
     *                 nullable=true,
     *                 example="11",
     *                 description="Structure type code"
     *             ),
     *             @OA\Property(
     *                 property="_parent",
     *                 type="integer",
     *                 nullable=true,
     *                 example=1,
     *                 description="Parent department ID"
     *             ),
     *             @OA\Property(
     *                 property="active",
     *                 type="boolean",
     *                 nullable=true,
     *                 example=true,
     *                 description="Active status"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fakultet/Bo'lim muvaffaqiyatli yangilandi"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Updated Faculty Name"),
     *                 @OA\Property(property="code", type="string", example="UFC")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Department not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:500',
            'code' => 'sometimes|string|max:50|unique:e_department,code,' . $id,
            '_structure_type' => 'sometimes|string|max:50',
            '_parent' => 'nullable|integer|exists:e_department,id',
            'active' => 'sometimes|boolean',
        ]);

        try {
            $department = $this->departmentService->updateDepartment($id, $validated);

            return $this->successResponse(
                new DepartmentResource($department),
                'Fakultet/Bo\'lim muvaffaqiyatli yangilandi'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete department (soft delete)
     *
     * @OA\Delete(
     *     path="/api/v1/admin/departments/{id}",
     *     tags={"Admin - Departments"},
     *     summary="Delete a department",
     *     description="Soft deletes a department. The department will be marked as inactive.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Department ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Department deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Fakultet/Bo'lim o'chirildi"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Department not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Department not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->departmentService->deleteDepartment($id);

            return $this->successResponse([], 'Fakultet/Bo\'lim o\'chirildi');

        } catch (\Exception $e) {
            return $this->errorResponse('Xatolik: ' . $e->getMessage(), 500);
        }
    }
}
