<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\SubjectService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Teacher Subject Controller (REFACTORED)
 */
class SubjectController extends Controller
{
    use ApiResponse;

    protected SubjectService $subjectService;

    public function __construct(SubjectService $subjectService)
    {
        $this->subjectService = $subjectService;
    }

    /**
     * Get teacher's subjects
     *
     * @OA\Get(
     *     path="/api/v1/teacher/subjects",
     *     tags={"Teacher - Subjects"},
     *     summary="Get all subjects taught by teacher",
     *     description="Returns a list of all subjects assigned to the authenticated teacher",
     *     security={{"bearerAuth":{}}},
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
     *                     @OA\Property(property="name", type="string", example="Mathematics"),
     *                     @OA\Property(property="code", type="string", example="MATH101"),
     *                     @OA\Property(property="credit", type="integer", example=3),
     *                     @OA\Property(property="groups", type="array", @OA\Items(type="object"))
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

        $subjects = $this->subjectService->getTeacherSubjects($teacher->employee->id);

        return $this->successResponse($subjects);
    }

    /**
     * Get subject students
     *
     * @OA\Get(
     *     path="/api/v1/teacher/subjects/{id}/students",
     *     tags={"Teacher - Subjects"},
     *     summary="Get students enrolled in a subject",
     *     description="Returns a list of students enrolled in the specified subject",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="group_id",
     *         in="query",
     *         description="Filter by group ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
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
     *                     @OA\Property(property="full_name", type="string"),
     *                     @OA\Property(property="student_id", type="string"),
     *                     @OA\Property(property="group_name", type="string"),
     *                     @OA\Property(property="email", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Subject not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function students(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();
        $groupId = $request->input('group_id');

        $students = $this->subjectService->getSubjectStudents($id, $teacher->employee->id, $groupId);

        return $this->successResponse($students);
    }

    /**
     * Get subject details
     *
     * @OA\Get(
     *     path="/api/v1/teacher/subjects/{id}",
     *     tags={"Teacher - Subjects"},
     *     summary="Get subject details",
     *     description="Returns detailed information about a specific subject including topics, schedule, and enrolled groups",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subject ID",
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
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="code", type="string"),
     *                 @OA\Property(property="credit", type="integer"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="groups", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="topics", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="schedule", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Subject not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $subject = $this->subjectService->getSubjectDetails($id, $teacher->employee->id);

        return $this->successResponse($subject);
    }
}
