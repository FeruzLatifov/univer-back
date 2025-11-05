<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\GradeService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Grade Controller
 *
 * MODULAR MONOLITH - Teacher Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Teacher
 */
class GradeController extends Controller
{
    use ApiResponse;

    /**
     * Grade Service (injected)
     */
    private GradeService $gradeService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(GradeService $gradeService)
    {
        $this->gradeService = $gradeService;
    }

    /**
     * Get grades for a subject
     *
     * @OA\Get(
     *     path="/api/v1/teacher/subject/{id}/grades",
     *     tags={"Teacher - Grades"},
     *     summary="Get grades for a subject",
     *     description="Returns a list of all grades for students in the specified subject. Can be filtered by grade type.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by grade type",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"current", "midterm", "final", "overall"},
     *             example="midterm"
     *         )
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
     *                     @OA\Property(property="student_id", type="integer", example=123),
     *                     @OA\Property(property="student_name", type="string", example="John Doe"),
     *                     @OA\Property(property="grade_type", type="string", example="midterm"),
     *                     @OA\Property(property="grade", type="number", example=85),
     *                     @OA\Property(property="max_grade", type="integer", example=100),
     *                     @OA\Property(property="comment", type="string", example="Good work"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Baholar ro'yxati")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teacher profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Teacher profile not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Teacher doesn't have access to this subject",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     *
     * GET /api/v1/teacher/subject/{id}/grades
     *
     * ✅ CLEAN ARCHITECTURE
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;
        $gradeType = $request->input('type');

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        try {
            // Delegate to service
            $grades = $this->gradeService->getGrades($teacherId, $id, $gradeType);

            return $this->successResponse($grades, 'Baholar ro\'yxati');

        } catch (\Exception $e) {
            return $this->forbiddenResponse($e->getMessage());
        }
    }

    /**
     * Enter/update grade for a student
     *
     * @OA\Post(
     *     path="/api/v1/teacher/grade",
     *     tags={"Teacher - Grades"},
     *     summary="Enter or update a grade for a student",
     *     description="Creates a new grade entry for a student in a specific subject",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subject_id", "student_id", "grade_type", "grade", "max_grade"},
     *             @OA\Property(
     *                 property="subject_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Subject ID (must exist in e_subject)"
     *             ),
     *             @OA\Property(
     *                 property="student_id",
     *                 type="integer",
     *                 example=123,
     *                 description="Student ID (must exist in e_student)"
     *             ),
     *             @OA\Property(
     *                 property="grade_type",
     *                 type="string",
     *                 enum={"current", "midterm", "final", "overall"},
     *                 example="midterm",
     *                 description="Type of grade"
     *             ),
     *             @OA\Property(
     *                 property="grade",
     *                 type="number",
     *                 format="float",
     *                 minimum=0,
     *                 example=85,
     *                 description="Grade value (must be >= 0)"
     *             ),
     *             @OA\Property(
     *                 property="max_grade",
     *                 type="integer",
     *                 minimum=1,
     *                 example=100,
     *                 description="Maximum possible grade (must be >= 1)"
     *             ),
     *             @OA\Property(
     *                 property="comment",
     *                 type="string",
     *                 maxLength=500,
     *                 nullable=true,
     *                 example="Good work! Keep improving.",
     *                 description="Optional teacher comment"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Grade stored successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="subject_id", type="integer", example=1),
     *                 @OA\Property(property="student_id", type="integer", example=123),
     *                 @OA\Property(property="grade_type", type="string", example="midterm"),
     *                 @OA\Property(property="grade", type="number", example=85),
     *                 @OA\Property(property="max_grade", type="integer", example=100),
     *                 @OA\Property(property="comment", type="string", example="Good work! Keep improving.")
     *             ),
     *             @OA\Property(property="message", type="string", example="Baho muvaffaqiyatli saqlandi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teacher profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Teacher profile not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The subject_id field is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Error message")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     *
     * POST /api/v1/teacher/grade
     *
     * ✅ CLEAN ARCHITECTURE
     *
     * Body:
     * {
     *   "subject_id": 1,
     *   "student_id": 123,
     *   "grade_type": "midterm",
     *   "grade": 85,
     *   "max_grade": 100,
     *   "comment": "Yaxshi ishladi"
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'student_id' => 'required|exists:e_student,id',
            'grade_type' => 'required|in:current,midterm,final,overall',
            'grade' => 'required|numeric|min:0',
            'max_grade' => 'required|integer|min:1',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Delegate to service
            $result = $this->gradeService->storeGrade(
                $teacherId,
                $request->subject_id,
                $request->student_id,
                $request->grade_type,
                $request->grade,
                $request->max_grade,
                $request->comment
            );

            return $this->successResponse($result, 'Baho muvaffaqiyatli saqlandi');

        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Update existing grade
     *
     * @OA\Put(
     *     path="/api/v1/teacher/grade/{id}",
     *     tags={"Teacher - Grades"},
     *     summary="Update an existing grade",
     *     description="Updates the grade value and/or comment for an existing grade entry",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Grade ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"grade", "max_grade"},
     *             @OA\Property(
     *                 property="grade",
     *                 type="number",
     *                 format="float",
     *                 minimum=0,
     *                 example=90,
     *                 description="Updated grade value (must be >= 0)"
     *             ),
     *             @OA\Property(
     *                 property="max_grade",
     *                 type="integer",
     *                 minimum=1,
     *                 example=100,
     *                 description="Maximum possible grade (must be >= 1)"
     *             ),
     *             @OA\Property(
     *                 property="comment",
     *                 type="string",
     *                 maxLength=500,
     *                 nullable=true,
     *                 example="Excellent improvement!",
     *                 description="Optional teacher comment"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Grade updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="grade", type="number", example=90),
     *                 @OA\Property(property="max_grade", type="integer", example=100),
     *                 @OA\Property(property="comment", type="string", example="Excellent improvement!")
     *             ),
     *             @OA\Property(property="message", type="string", example="Baho yangilandi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teacher profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Teacher profile not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The grade field is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Teacher doesn't have permission to update this grade",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     *
     * PUT /api/v1/teacher/grade/{id}
     *
     * ✅ CLEAN ARCHITECTURE
     *
     * @param Request $request
     * @param int $id Grade ID
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'grade' => 'required|numeric|min:0',
            'max_grade' => 'required|integer|min:1',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Delegate to service
            $result = $this->gradeService->updateGrade(
                $teacherId,
                $id,
                $request->grade,
                $request->max_grade,
                $request->comment
            );

            return $this->successResponse($result, 'Baho yangilandi');

        } catch (\Exception $e) {
            return $this->forbiddenResponse($e->getMessage());
        }
    }

    /**
     * Get grade statistics and report
     *
     * @OA\Get(
     *     path="/api/v1/teacher/grade/report",
     *     tags={"Teacher - Grades"},
     *     summary="Get grade statistics and report",
     *     description="Returns statistical analysis of grades including averages, distribution, and performance metrics for a subject",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subject_id",
     *         in="query",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="grade_type",
     *         in="query",
     *         description="Filter by grade type",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"current", "midterm", "final", "overall"},
     *             example="midterm"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="subject_id", type="integer", example=1),
     *                 @OA\Property(property="subject_name", type="string", example="Mathematics"),
     *                 @OA\Property(property="grade_type", type="string", example="midterm"),
     *                 @OA\Property(property="total_students", type="integer", example=30),
     *                 @OA\Property(property="graded_students", type="integer", example=28),
     *                 @OA\Property(property="average_grade", type="number", example=78.5),
     *                 @OA\Property(property="highest_grade", type="number", example=95),
     *                 @OA\Property(property="lowest_grade", type="number", example=45),
     *                 @OA\Property(property="pass_rate", type="number", example=85.7),
     *                 @OA\Property(
     *                     property="distribution",
     *                     type="object",
     *                     @OA\Property(property="excellent", type="integer", example=8),
     *                     @OA\Property(property="good", type="integer", example=12),
     *                     @OA\Property(property="satisfactory", type="integer", example=6),
     *                     @OA\Property(property="poor", type="integer", example=2)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Baholar hisoboti")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teacher profile not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Teacher profile not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The subject_id field is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Teacher doesn't have access to this subject",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Access denied")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     *
     * GET /api/v1/teacher/grade/report
     *
     * ✅ CLEAN ARCHITECTURE
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function report(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'grade_type' => 'nullable|in:current,midterm,final,overall',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Delegate to service
            $report = $this->gradeService->getGradeReport(
                $teacherId,
                $request->subject_id,
                $request->grade_type
            );

            return $this->successResponse($report, 'Baholar hisoboti');

        } catch (\Exception $e) {
            return $this->forbiddenResponse($e->getMessage());
        }
    }
}
