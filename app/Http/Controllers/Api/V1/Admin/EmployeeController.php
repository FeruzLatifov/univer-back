<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\EmployeeService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Employee Management Controller
 * MODULAR MONOLITH - Admin Module
 * ✅ CLEAN ARCHITECTURE
 */
class EmployeeController extends Controller
{
    use ApiResponse;
    private EmployeeService $service;

    public function __construct(EmployeeService $service)
    {
        $this->service = $service;
    }

    /**
     * Get employees list with filters
     *
     * @OA\Get(
     *     path="/api/v1/admin/employees",
     *     tags={"Admin - Employees"},
     *     summary="Get all employees with filters",
     *     description="Returns a paginated list of employees with optional filtering by department, position, and status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by employee name or code",
     *         required=false,
     *         @OA\Schema(type="string", example="John")
     *     ),
     *     @OA\Parameter(
     *         name="department_id",
     *         in="query",
     *         description="Filter by department ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="position_id",
     *         in="query",
     *         description="Filter by position ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
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
     *                     @OA\Property(property="first_name", type="string", example="John"),
     *                     @OA\Property(property="last_name", type="string", example="Doe"),
     *                     @OA\Property(property="middle_name", type="string", example="Smith"),
     *                     @OA\Property(property="employee_code", type="string", example="EMP001"),
     *                     @OA\Property(property="position", type="string", example="Professor"),
     *                     @OA\Property(property="department", type="string", example="Computer Science"),
     *                     @OA\Property(property="email", type="string", example="john.doe@university.uz"),
     *                     @OA\Property(property="phone", type="string", example="+998901234567"),
     *                     @OA\Property(property="active", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=10),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=145)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $result = $this->service->getEmployeesList($request->all());
        return $this->successResponse($result);
    }

    /**
     * Get single employee
     *
     * @OA\Get(
     *     path="/api/v1/admin/employees/{id}",
     *     tags={"Admin - Employees"},
     *     summary="Get single employee details",
     *     description="Returns detailed information about a specific employee",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Employee ID",
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
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="middle_name", type="string", example="Smith"),
     *                 @OA\Property(property="employee_code", type="string", example="EMP001"),
     *                 @OA\Property(property="position", type="object"),
     *                 @OA\Property(property="department", type="object"),
     *                 @OA\Property(property="email", type="string", example="john.doe@university.uz"),
     *                 @OA\Property(property="phone", type="string", example="+998901234567"),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="1980-05-15"),
     *                 @OA\Property(property="passport_number", type="string", example="AA1234567"),
     *                 @OA\Property(property="active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found",
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
            $item = $this->service->getEmployee($id);
            return $this->successResponse($item);
        } catch (\Exception $e) {
            return $this->errorResponse('Topilmadi', 404);
        }
    }

    /**
     * Create new employee
     *
     * @OA\Post(
     *     path="/api/v1/admin/employees",
     *     tags={"Admin - Employees"},
     *     summary="Create a new employee",
     *     description="Creates a new employee record with the provided data",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "employee_code", "department_id", "position_id"},
     *             @OA\Property(
     *                 property="first_name",
     *                 type="string",
     *                 maxLength=255,
     *                 example="John",
     *                 description="Employee first name"
     *             ),
     *             @OA\Property(
     *                 property="last_name",
     *                 type="string",
     *                 maxLength=255,
     *                 example="Doe",
     *                 description="Employee last name"
     *             ),
     *             @OA\Property(
     *                 property="middle_name",
     *                 type="string",
     *                 maxLength=255,
     *                 nullable=true,
     *                 example="Smith",
     *                 description="Employee middle name"
     *             ),
     *             @OA\Property(
     *                 property="employee_code",
     *                 type="string",
     *                 maxLength=50,
     *                 example="EMP001",
     *                 description="Unique employee code"
     *             ),
     *             @OA\Property(
     *                 property="department_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Department ID (must exist)"
     *             ),
     *             @OA\Property(
     *                 property="position_id",
     *                 type="integer",
     *                 example=5,
     *                 description="Position ID (must exist)"
     *             ),
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 nullable=true,
     *                 example="john.doe@university.uz",
     *                 description="Employee email address"
     *             ),
     *             @OA\Property(
     *                 property="phone",
     *                 type="string",
     *                 nullable=true,
     *                 example="+998901234567",
     *                 description="Employee phone number"
     *             ),
     *             @OA\Property(
     *                 property="birth_date",
     *                 type="string",
     *                 format="date",
     *                 nullable=true,
     *                 example="1980-05-15",
     *                 description="Date of birth"
     *             ),
     *             @OA\Property(
     *                 property="passport_number",
     *                 type="string",
     *                 nullable=true,
     *                 example="AA1234567",
     *                 description="Passport number"
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
     *         description="Employee created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Yaratildi"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="employee_code", type="string", example="EMP001")
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
            $item = $this->service->createEmployee($request->all());
            return $this->successResponse($item, 'Yaratildi', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update employee
     *
     * @OA\Put(
     *     path="/api/v1/admin/employees/{id}",
     *     tags={"Admin - Employees"},
     *     summary="Update an existing employee",
     *     description="Updates employee details. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Employee ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", maxLength=255, nullable=true, example="John"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, nullable=true, example="Doe"),
     *             @OA\Property(property="middle_name", type="string", maxLength=255, nullable=true, example="Smith"),
     *             @OA\Property(property="employee_code", type="string", maxLength=50, nullable=true, example="EMP001"),
     *             @OA\Property(property="department_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="position_id", type="integer", nullable=true, example=5),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="john.doe@university.uz"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+998901234567"),
     *             @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="1980-05-15"),
     *             @OA\Property(property="passport_number", type="string", nullable=true, example="AA1234567"),
     *             @OA\Property(property="active", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Yangilandi"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Employee not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $item = $this->service->updateEmployee($id, $request->all());
            return $this->successResponse($item, 'Yangilandi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Delete employee
     *
     * @OA\Delete(
     *     path="/api/v1/admin/employees/{id}",
     *     tags={"Admin - Employees"},
     *     summary="Delete an employee",
     *     description="Soft deletes an employee. The employee will be marked as inactive.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Employee ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="O'chirildi"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Employee not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->service->deleteEmployee($id);
            return $this->successResponse([], 'O\'chirildi');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
