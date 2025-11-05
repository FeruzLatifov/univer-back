<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStudentRequest;
use App\Http\Requests\Api\V1\UpdateStudentRequest;
use App\Http\Resources\Api\V1\StudentResource;
use App\Services\Admin\StudentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Student Management Controller (Admin Panel)
 *
 * MODULAR MONOLITH - Admin Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Admin
 */
class StudentController extends Controller
{
    use ApiResponse;

    /**
     * Student Service (injected)
     */
    private StudentService $studentService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(StudentService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Get students list with filters
     *
     * @OA\Get(
     *     path="/api/v1/admin/students",
     *     tags={"Admin - Students"},
     *     summary="Get all students with filters",
     *     description="Returns a paginated list of students with optional filtering by group, specialty, level, and status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by student name or student code",
     *         required=false,
     *         @OA\Schema(type="string", example="Ali")
     *     ),
     *     @OA\Parameter(
     *         name="group_id",
     *         in="query",
     *         description="Filter by group ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
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
     *         description="Filter by education level (1-4 for Bachelor)",
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
     *                     @OA\Property(property="first_name", type="string", example="Ali"),
     *                     @OA\Property(property="last_name", type="string", example="Karimov"),
     *                     @OA\Property(property="student_code", type="string", example="STU001"),
     *                     @OA\Property(property="group_name", type="string", example="CS-101"),
     *                     @OA\Property(property="specialty_name", type="string", example="Computer Science"),
     *                     @OA\Property(property="level", type="integer", example=1),
     *                     @OA\Property(property="email", type="string", example="ali.karimov@student.uz"),
     *                     @OA\Property(property="phone", type="string", example="+998901234567"),
     *                     @OA\Property(property="active", type="boolean", example=true)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=25),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=365)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $students = $this->studentService->getStudentsList($request->all());

        return $this->successResponse([
            'data' => StudentResource::collection($students->items()),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    /**
     * Get single student
     *
     * @OA\Get(
     *     path="/api/v1/admin/students/{id}",
     *     tags={"Admin - Students"},
     *     summary="Get single student details",
     *     description="Returns detailed information about a specific student including group, grades, and attendance",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Student ID",
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
     *                 @OA\Property(property="first_name", type="string", example="Ali"),
     *                 @OA\Property(property="last_name", type="string", example="Karimov"),
     *                 @OA\Property(property="middle_name", type="string", example="Rustamovich"),
     *                 @OA\Property(property="student_code", type="string", example="STU001"),
     *                 @OA\Property(property="group", type="object"),
     *                 @OA\Property(property="specialty", type="object"),
     *                 @OA\Property(property="email", type="string", example="ali.karimov@student.uz"),
     *                 @OA\Property(property="phone", type="string", example="+998901234567"),
     *                 @OA\Property(property="birth_date", type="string", format="date", example="2000-03-15"),
     *                 @OA\Property(property="passport_number", type="string", example="AA1234567"),
     *                 @OA\Property(property="address", type="string", example="Tashkent, Uzbekistan"),
     *                 @OA\Property(property="active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student topilmadi")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show($id): JsonResponse
    {
        try {
            $student = $this->studentService->getStudent($id);

            return $this->successResponse(new StudentResource($student));

        } catch (\Exception $e) {
            return $this->errorResponse('Student topilmadi', 404);
        }
    }

    /**
     * Create new student
     *
     * @OA\Post(
     *     path="/api/v1/admin/students",
     *     tags={"Admin - Students"},
     *     summary="Create a new student",
     *     description="Creates a new student record with the provided data. Requires validated request data.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name", "last_name", "student_code", "group_id"},
     *             @OA\Property(property="first_name", type="string", maxLength=255, example="Ali", description="Student first name"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, example="Karimov", description="Student last name"),
     *             @OA\Property(property="middle_name", type="string", maxLength=255, nullable=true, example="Rustamovich", description="Student middle name"),
     *             @OA\Property(property="student_code", type="string", maxLength=50, example="STU001", description="Unique student code"),
     *             @OA\Property(property="group_id", type="integer", example=1, description="Group ID (must exist)"),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="ali.karimov@student.uz", description="Student email address"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+998901234567", description="Student phone number"),
     *             @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="2000-03-15", description="Date of birth"),
     *             @OA\Property(property="passport_number", type="string", nullable=true, example="AA1234567", description="Passport number"),
     *             @OA\Property(property="passport_given_date", type="string", format="date", nullable=true, example="2015-03-20", description="Passport issue date"),
     *             @OA\Property(property="passport_given_by", type="string", nullable=true, example="Tashkent IIB", description="Passport issuing authority"),
     *             @OA\Property(property="address", type="string", nullable=true, example="Tashkent, Uzbekistan", description="Home address"),
     *             @OA\Property(property="active", type="boolean", example=true, description="Active status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Student created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student muvaffaqiyatli yaratildi"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="Ali"),
     *                 @OA\Property(property="student_code", type="string", example="STU001")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(StoreStudentRequest $request): JsonResponse
    {
        try {
            $student = $this->studentService->createStudent($request->validated());

            return $this->successResponse(
                new StudentResource($student),
                'Student muvaffaqiyatli yaratildi',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Student yaratishda xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update student
     *
     * @OA\Put(
     *     path="/api/v1/admin/students/{id}",
     *     tags={"Admin - Students"},
     *     summary="Update an existing student",
     *     description="Updates student details. All fields are optional. Requires validated request data.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Student ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", maxLength=255, nullable=true, example="Ali"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, nullable=true, example="Karimov"),
     *             @OA\Property(property="middle_name", type="string", maxLength=255, nullable=true, example="Rustamovich"),
     *             @OA\Property(property="student_code", type="string", maxLength=50, nullable=true, example="STU001"),
     *             @OA\Property(property="group_id", type="integer", nullable=true, example=1),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="ali.karimov@student.uz"),
     *             @OA\Property(property="phone", type="string", nullable=true, example="+998901234567"),
     *             @OA\Property(property="birth_date", type="string", format="date", nullable=true, example="2000-03-15"),
     *             @OA\Property(property="passport_number", type="string", nullable=true, example="AA1234567"),
     *             @OA\Property(property="address", type="string", nullable=true, example="Tashkent, Uzbekistan"),
     *             @OA\Property(property="active", type="boolean", nullable=true, example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student muvaffaqiyatli yangilandi"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Student not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(UpdateStudentRequest $request, $id): JsonResponse
    {
        try {
            $student = $this->studentService->updateStudent($id, $request->validated());

            return $this->successResponse(
                new StudentResource($student),
                'Student muvaffaqiyatli yangilandi'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Student yangilashda xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete student (soft delete)
     *
     * @OA\Delete(
     *     path="/api/v1/admin/students/{id}",
     *     tags={"Admin - Students"},
     *     summary="Delete a student",
     *     description="Soft deletes a student. The student will be marked as inactive (deactivated).",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Student ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Student deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Student o'chirildi (deactivated)"),
     *             @OA\Property(property="data", type="array", @OA\Items())
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Student not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->studentService->deleteStudent($id);

            return $this->successResponse([], 'Student o\'chirildi (deactivated)');

        } catch (\Exception $e) {
            return $this->errorResponse('Student o\'chirishda xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Upload student image
     *
     * @OA\Post(
     *     path="/api/v1/admin/students/{id}/image",
     *     tags={"Admin - Students"},
     *     summary="Upload student profile image",
     *     description="Uploads a profile image for the specified student. Max file size: 5MB. Allowed formats: JPEG, PNG, JPG.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Student ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"image"},
     *                 @OA\Property(
     *                     property="image",
     *                     type="string",
     *                     format="binary",
     *                     description="Image file (JPEG, PNG, JPG, max 5MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Image uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Rasm muvaffaqiyatli yuklandi"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="image_url", type="string", example="/storage/students/images/student_1.jpg"),
     *                 @OA\Property(property="image_path", type="string", example="students/images/student_1.jpg")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error (invalid file type or size)"),
     *     @OA\Response(response=404, description="Student not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function uploadImage(Request $request, $id): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
        ]);

        try {
            $result = $this->studentService->uploadStudentImage($id, $request->file('image'));

            return $this->successResponse($result, 'Rasm muvaffaqiyatli yuklandi');

        } catch (\Exception $e) {
            return $this->errorResponse('Rasm yuklashda xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get student statistics
     *
     * @OA\Get(
     *     path="/api/v1/admin/students/statistics",
     *     tags={"Admin - Students"},
     *     summary="Get student statistics",
     *     description="Returns statistical data about students including total count, by level, by specialty, and active status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_students", type="integer", example=1250),
     *                 @OA\Property(property="active_students", type="integer", example=1180),
     *                 @OA\Property(property="inactive_students", type="integer", example=70),
     *                 @OA\Property(
     *                     property="by_level",
     *                     type="object",
     *                     @OA\Property(property="1", type="integer", example=320),
     *                     @OA\Property(property="2", type="integer", example=310),
     *                     @OA\Property(property="3", type="integer", example=295),
     *                     @OA\Property(property="4", type="integer", example=255)
     *                 ),
     *                 @OA\Property(
     *                     property="by_specialty",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="specialty_name", type="string", example="Computer Science"),
     *                         @OA\Property(property="count", type="integer", example=240)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="by_gender",
     *                     type="object",
     *                     @OA\Property(property="male", type="integer", example=680),
     *                     @OA\Property(property="female", type="integer", example=570)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->studentService->getStudentStatistics();

        return $this->successResponse($stats);
    }
}
