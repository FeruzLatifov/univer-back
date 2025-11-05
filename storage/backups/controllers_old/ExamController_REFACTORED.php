<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\ExamService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Exam Controller (REFACTORED)
 */
class ExamController extends Controller
{
    use ApiResponse;

    protected ExamService $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    /**
     * Get all exams
     *
     * @OA\Get(
     *     path="/api/v1/teacher/exams",
     *     tags={"Teacher - Exams"},
     *     summary="Get all exams for teacher",
     *     description="Returns a list of all exams. Can be filtered by subject, group, type, or status.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subject_id",
     *         in="query",
     *         description="Filter by subject ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="group_id",
     *         in="query",
     *         description="Filter by group ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by exam type",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"midterm", "final", "retake", "makeup"},
     *             example="final"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", example="scheduled")
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
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string", example="Final Exam"),
     *                     @OA\Property(property="exam_type", type="string", example="final"),
     *                     @OA\Property(property="exam_date", type="string", format="date-time"),
     *                     @OA\Property(property="duration", type="integer", example=120),
     *                     @OA\Property(property="location", type="string", example="Room 201"),
     *                     @OA\Property(property="subject", type="object"),
     *                     @OA\Property(property="group", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $filters = $request->only(['subject_id', 'group_id', 'type', 'status']);

        $exams = $this->examService->getExams($teacher->employee->id, $filters);

        return $this->successResponse($exams);
    }

    /**
     * Create exam
     *
     * @OA\Post(
     *     path="/api/v1/teacher/exams",
     *     tags={"Teacher - Exams"},
     *     summary="Create a new exam",
     *     description="Creates a new exam schedule",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subject_id", "group_id", "title", "exam_type", "exam_date"},
     *             @OA\Property(property="subject_id", type="integer", example=1),
     *             @OA\Property(property="group_id", type="integer", example=5),
     *             @OA\Property(property="title", type="string", maxLength=255, example="Mathematics Final Exam"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(
     *                 property="exam_type",
     *                 type="string",
     *                 enum={"midterm", "final", "retake", "makeup"},
     *                 example="final"
     *             ),
     *             @OA\Property(property="exam_date", type="string", format="date", example="2025-12-15"),
     *             @OA\Property(property="duration", type="integer", minimum=1, example=120, description="Duration in minutes"),
     *             @OA\Property(property="max_score", type="integer", minimum=1, example=100),
     *             @OA\Property(property="passing_score", type="integer", minimum=0, example=60),
     *             @OA\Property(property="location", type="string", nullable=true, example="Room 201"),
     *             @OA\Property(property="instructions", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Exam created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Exam created successfully"),
     *                 @OA\Property(property="exam", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'group_id' => 'required|exists:h_academic_group,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'exam_type' => 'required|in:midterm,final,retake,makeup',
            'exam_date' => 'required|date',
            'duration' => 'nullable|integer|min:1',
            'max_score' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0',
            'location' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $exam = $this->examService->createExam($teacher->employee->id, $request->all());

        return $this->successResponse([
            'message' => 'Exam created successfully',
            'exam' => $exam,
        ], 201);
    }

    /**
     * Get single exam
     *
     * @OA\Get(
     *     path="/api/v1/teacher/exams/{id}",
     *     tags={"Teacher - Exams"},
     *     summary="Get single exam details",
     *     description="Returns detailed information about a specific exam",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Exam ID",
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
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="exam_type", type="string"),
     *                 @OA\Property(property="exam_date", type="string", format="date-time"),
     *                 @OA\Property(property="duration", type="integer"),
     *                 @OA\Property(property="max_score", type="integer"),
     *                 @OA\Property(property="passing_score", type="integer"),
     *                 @OA\Property(property="location", type="string"),
     *                 @OA\Property(property="instructions", type="string"),
     *                 @OA\Property(property="subject", type="object"),
     *                 @OA\Property(property="group", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Exam not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $exam = $this->examService->getExam($id, $teacher->employee->id);

        return $this->successResponse($exam);
    }

    /**
     * Enter exam results
     *
     * @OA\Post(
     *     path="/api/v1/teacher/exams/{id}/results",
     *     tags={"Teacher - Exams"},
     *     summary="Enter or update exam results",
     *     description="Bulk entry of exam results for multiple students",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Exam ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"results"},
     *             @OA\Property(
     *                 property="results",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="student_id", type="integer", example=1),
     *                     @OA\Property(property="score", type="number", example=85.5),
     *                     @OA\Property(property="grade", type="string", enum={"A", "B", "C", "D", "F"}, example="B"),
     *                     @OA\Property(property="attended", type="boolean", example=true),
     *                     @OA\Property(property="notes", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Results entered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Results entered successfully"),
     *                 @OA\Property(property="created", type="integer", example=15),
     *                 @OA\Property(property="updated", type="integer", example=3),
     *                 @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function enterResults(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'results' => 'required|array',
            'results.*.student_id' => 'required|exists:e_student,id',
            'results.*.score' => 'required|numeric|min:0',
            'results.*.grade' => 'nullable|string|in:A,B,C,D,F',
            'results.*.attended' => 'boolean',
            'results.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $result = $this->examService->enterResults($id, $teacher->employee->id, $request->input('results'));

        return $this->successResponse([
            'message' => 'Results entered successfully',
            'created' => $result['created'],
            'updated' => $result['updated'],
            'errors' => $result['errors'],
        ]);
    }

    /**
     * Get exam statistics
     *
     * @OA\Get(
     *     path="/api/v1/teacher/exams/{id}/statistics",
     *     tags={"Teacher - Exams"},
     *     summary="Get exam statistics",
     *     description="Returns statistical data about exam performance",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Exam ID",
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
     *                 @OA\Property(property="total_students", type="integer", example=30),
     *                 @OA\Property(property="attended", type="integer", example=28),
     *                 @OA\Property(property="absent", type="integer", example=2),
     *                 @OA\Property(property="average_score", type="number", example=78.5),
     *                 @OA\Property(property="highest_score", type="number", example=98),
     *                 @OA\Property(property="lowest_score", type="number", example=45),
     *                 @OA\Property(property="pass_rate", type="number", example=85.7),
     *                 @OA\Property(property="grade_distribution", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Exam not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function statistics(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $stats = $this->examService->getStatistics($id, $teacher->employee->id);

        return $this->successResponse($stats);
    }
}
