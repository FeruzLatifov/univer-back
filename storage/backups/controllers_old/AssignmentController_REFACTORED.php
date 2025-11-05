<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\AssignmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Assignment Controller (REFACTORED)
 *
 * Thin controller - delegates to AssignmentService
 */
class AssignmentController extends Controller
{
    use ApiResponse;

    protected AssignmentService $assignmentService;

    public function __construct(AssignmentService $assignmentService)
    {
        $this->assignmentService = $assignmentService;
    }

    /**
     * Get all assignments for teacher
     *
     * @OA\Get(
     *     path="/api/v1/teacher/assignments",
     *     tags={"Teacher - Assignments"},
     *     summary="Get all assignments for authenticated teacher",
     *     description="Returns a list of all assignments created by the authenticated teacher. Can be filtered by subject, group, or status.",
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
     *         description="Filter by academic group ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by assignment status",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"draft", "published", "closed"},
     *             example="published"
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
     *                     @OA\Property(property="title", type="string", example="Homework Assignment 1"),
     *                     @OA\Property(property="description", type="string", example="Complete exercises 1-10"),
     *                     @OA\Property(property="deadline", type="string", format="date-time", example="2025-11-15 23:59:00"),
     *                     @OA\Property(property="max_score", type="integer", example=100),
     *                     @OA\Property(property="status", type="string", example="published"),
     *                     @OA\Property(property="subject_name", type="string", example="Mathematics"),
     *                     @OA\Property(property="group_name", type="string", example="CS-101"),
     *                     @OA\Property(property="submissions_count", type="integer", example=15)
     *                 )
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
        $teacher = $request->user();
        $filters = $request->only(['subject_id', 'group_id', 'status']);

        $assignments = $this->assignmentService->getAssignments(
            $teacher->employee->id,
            $filters
        );

        return $this->successResponse($assignments);
    }

    /**
     * Get single assignment details
     *
     * @OA\Get(
     *     path="/api/v1/teacher/assignments/{id}",
     *     tags={"Teacher - Assignments"},
     *     summary="Get single assignment details",
     *     description="Returns detailed information about a specific assignment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
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
     *                 @OA\Property(property="title", type="string", example="Homework Assignment 1"),
     *                 @OA\Property(property="description", type="string", example="Complete exercises 1-10"),
     *                 @OA\Property(property="instructions", type="string", example="Follow the guidelines in the textbook"),
     *                 @OA\Property(property="deadline", type="string", format="date-time", example="2025-11-15 23:59:00"),
     *                 @OA\Property(property="max_score", type="integer", example=100),
     *                 @OA\Property(property="min_score", type="integer", example=50),
     *                 @OA\Property(property="allow_late_submission", type="boolean", example=true),
     *                 @OA\Property(property="status", type="string", example="published"),
     *                 @OA\Property(property="subject", type="object"),
     *                 @OA\Property(property="group", type="object"),
     *                 @OA\Property(property="attachments", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Assignment not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Assignment not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = $this->assignmentService->getAssignment($id, $teacher->employee->id);

        return $this->successResponse($assignment);
    }

    /**
     * Create new assignment
     *
     * @OA\Post(
     *     path="/api/v1/teacher/assignments",
     *     tags={"Teacher - Assignments"},
     *     summary="Create a new assignment",
     *     description="Creates a new assignment with optional file attachments",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"subject_id", "group_id", "title", "deadline", "max_score"},
     *                 @OA\Property(property="subject_id", type="integer", example=1, description="Subject ID (must exist)"),
     *                 @OA\Property(property="group_id", type="integer", example=5, description="Academic group ID (must exist)"),
     *                 @OA\Property(property="topic_id", type="integer", nullable=true, example=2, description="Topic ID (optional)"),
     *                 @OA\Property(property="title", type="string", maxLength=255, example="Homework Assignment 1", description="Assignment title"),
     *                 @OA\Property(property="description", type="string", nullable=true, example="Complete exercises 1-10", description="Assignment description"),
     *                 @OA\Property(property="instructions", type="string", nullable=true, example="Follow the guidelines in textbook", description="Detailed instructions"),
     *                 @OA\Property(property="deadline", type="string", format="date-time", example="2025-11-15 23:59:00", description="Deadline (must be in future)"),
     *                 @OA\Property(property="max_score", type="integer", minimum=1, example=100, description="Maximum score"),
     *                 @OA\Property(property="min_score", type="integer", minimum=0, nullable=true, example=50, description="Minimum passing score"),
     *                 @OA\Property(property="allow_late_submission", type="boolean", example=true, description="Allow late submissions"),
     *                 @OA\Property(property="publish_immediately", type="boolean", example=false, description="Publish immediately after creation"),
     *                 @OA\Property(
     *                     property="attachments[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="File attachments (max 10MB each)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assignment created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Assignment created successfully"),
     *                 @OA\Property(property="assignment", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The title field is required")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'group_id' => 'required|exists:h_academic_group,id',
            'topic_id' => 'nullable|exists:e_subject_topic,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'deadline' => 'required|date|after:now',
            'max_score' => 'required|integer|min:1',
            'min_score' => 'nullable|integer|min:0',
            'allow_late_submission' => 'boolean',
            'publish_immediately' => 'boolean',
            'attachments.*' => 'file|max:10240', // 10MB max per file
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $assignment = $this->assignmentService->createAssignment(
            $teacher->employee->id,
            array_merge(
                $request->all(),
                ['attachments' => $request->file('attachments', [])]
            )
        );

        return $this->successResponse([
            'message' => 'Assignment created successfully',
            'assignment' => $assignment,
        ], 201);
    }

    /**
     * Update assignment
     *
     * @OA\Put(
     *     path="/api/v1/teacher/assignments/{id}",
     *     tags={"Teacher - Assignments"},
     *     summary="Update an existing assignment",
     *     description="Updates assignment details. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="subject_id", type="integer", nullable=true, example=1),
     *                 @OA\Property(property="group_id", type="integer", nullable=true, example=5),
     *                 @OA\Property(property="topic_id", type="integer", nullable=true, example=2),
     *                 @OA\Property(property="title", type="string", maxLength=255, nullable=true, example="Updated Assignment Title"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="instructions", type="string", nullable=true),
     *                 @OA\Property(property="deadline", type="string", format="date-time", nullable=true, example="2025-11-20 23:59:00"),
     *                 @OA\Property(property="max_score", type="integer", minimum=1, nullable=true, example=100),
     *                 @OA\Property(property="min_score", type="integer", minimum=0, nullable=true, example=50),
     *                 @OA\Property(property="allow_late_submission", type="boolean", nullable=true),
     *                 @OA\Property(property="remove_existing", type="boolean", example=false, description="Remove existing attachments"),
     *                 @OA\Property(
     *                     property="attachments[]",
     *                     type="array",
     *                     @OA\Items(type="string", format="binary"),
     *                     description="New file attachments (max 10MB each)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Assignment updated successfully"),
     *                 @OA\Property(property="assignment", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     * @OA\Post(
     *     path="/api/v1/teacher/assignments/{id}",
     *     tags={"Teacher - Assignments"},
     *     summary="Update assignment (via POST with _method=PUT)",
     *     description="Alternative update endpoint for browsers that don't support PUT with multipart/form-data",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="_method", type="string", example="PUT", description="HTTP method override")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Assignment updated successfully"),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'nullable|exists:e_subject,id',
            'group_id' => 'nullable|exists:h_academic_group,id',
            'topic_id' => 'nullable|exists:e_subject_topic,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'deadline' => 'nullable|date',
            'max_score' => 'nullable|integer|min:1',
            'min_score' => 'nullable|integer|min:0',
            'allow_late_submission' => 'boolean',
            'attachments.*' => 'file|max:10240',
            'remove_existing' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $assignment = $this->assignmentService->updateAssignment(
            $id,
            $teacher->employee->id,
            array_merge(
                $request->all(),
                ['attachments' => $request->file('attachments')]
            )
        );

        return $this->successResponse([
            'message' => 'Assignment updated successfully',
            'assignment' => $assignment,
        ]);
    }

    /**
     * Delete assignment
     *
     * @OA\Delete(
     *     path="/api/v1/teacher/assignments/{id}",
     *     tags={"Teacher - Assignments"},
     *     summary="Delete an assignment",
     *     description="Permanently deletes an assignment. Cannot be undone.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Assignment deleted successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $this->assignmentService->deleteAssignment($id, $teacher->employee->id);

        return $this->successResponse(['message' => 'Assignment deleted successfully']);
    }

    /**
     * Publish assignment
     *
     * @OA\Post(
     *     path="/api/v1/teacher/assignments/{id}/publish",
     *     tags={"Teacher - Assignments"},
     *     summary="Publish an assignment",
     *     description="Makes the assignment visible to students",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment published successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Assignment published successfully"),
     *                 @OA\Property(property="assignment", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = $this->assignmentService->publishAssignment($id, $teacher->employee->id);

        return $this->successResponse([
            'message' => 'Assignment published successfully',
            'assignment' => $assignment,
        ]);
    }

    /**
     * Unpublish assignment
     *
     * @OA\Post(
     *     path="/api/v1/teacher/assignments/{id}/unpublish",
     *     tags={"Teacher - Assignments"},
     *     summary="Unpublish an assignment",
     *     description="Hides the assignment from students (returns to draft status)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Assignment unpublished successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Assignment unpublished successfully"),
     *                 @OA\Property(property="assignment", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function unpublish(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = $this->assignmentService->unpublishAssignment($id, $teacher->employee->id);

        return $this->successResponse([
            'message' => 'Assignment unpublished successfully',
            'assignment' => $assignment,
        ]);
    }

    /**
     * Get assignment submissions
     *
     * @OA\Get(
     *     path="/api/v1/teacher/assignments/{id}/submissions",
     *     tags={"Teacher - Assignments"},
     *     summary="Get all submissions for an assignment",
     *     description="Returns a list of student submissions for the specified assignment",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by submission status",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "graded", "late"},
     *             example="pending"
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
     *                     @OA\Property(property="student_name", type="string", example="John Doe"),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time"),
     *                     @OA\Property(property="status", type="string", example="pending"),
     *                     @OA\Property(property="score", type="number", nullable=true),
     *                     @OA\Property(property="is_late", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function submissions(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();
        $filters = $request->only(['status']);

        $submissions = $this->assignmentService->getSubmissions(
            $id,
            $teacher->employee->id,
            $filters
        );

        return $this->successResponse($submissions);
    }

    /**
     * Get submission detail
     *
     * @OA\Get(
     *     path="/api/v1/teacher/submissions/{id}",
     *     tags={"Teacher - Assignments"},
     *     summary="Get detailed information about a submission",
     *     description="Returns complete submission details including files and student info",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Submission ID",
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
     *                 @OA\Property(property="assignment_title", type="string"),
     *                 @OA\Property(property="student", type="object"),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time"),
     *                 @OA\Property(property="content", type="string"),
     *                 @OA\Property(property="attachments", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="score", type="number", nullable=true),
     *                 @OA\Property(property="feedback", type="string", nullable=true),
     *                 @OA\Property(property="graded_at", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Submission not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function submissionDetail(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $submission = $this->assignmentService->getSubmissionDetail($id, $teacher->employee->id);

        return $this->successResponse($submission);
    }

    /**
     * Grade submission
     *
     * @OA\Post(
     *     path="/api/v1/teacher/submissions/{id}/grade",
     *     tags={"Teacher - Assignments"},
     *     summary="Grade a student submission",
     *     description="Assigns a score and optional feedback to a submission",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Submission ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"score"},
     *             @OA\Property(
     *                 property="score",
     *                 type="number",
     *                 format="float",
     *                 minimum=0,
     *                 example=85.5,
     *                 description="Score (must be between 0 and assignment max_score)"
     *             ),
     *             @OA\Property(
     *                 property="feedback",
     *                 type="string",
     *                 nullable=true,
     *                 example="Good work! Consider adding more examples.",
     *                 description="Teacher feedback for the student"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Submission graded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Submission graded successfully"),
     *                 @OA\Property(property="submission", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Submission not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function gradeSubmission(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'score' => 'required|numeric|min:0',
            'feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $submission = $this->assignmentService->gradeSubmission(
            $id,
            $teacher->employee->id,
            $request->only(['score', 'feedback'])
        );

        return $this->successResponse([
            'message' => 'Submission graded successfully',
            'submission' => $submission,
        ]);
    }

    /**
     * Download submission file
     *
     * @OA\Get(
     *     path="/api/v1/teacher/submissions/{id}/files/{fileIndex}",
     *     tags={"Teacher - Assignments"},
     *     summary="Download a submission attachment file",
     *     description="Downloads a specific file from a student submission",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Submission ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="fileIndex",
     *         in="path",
     *         description="File index (0-based)",
     *         required=false,
     *         @OA\Schema(type="integer", default=0, example=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, description="File or submission not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function downloadSubmissionFile(Request $request, int $id, int $fileIndex = 0)
    {
        $teacher = $request->user();

        $submission = $this->assignmentService->getSubmissionDetail($id, $teacher->employee->id);

        if (!isset($submission['attachments'][$fileIndex])) {
            return $this->errorResponse('File not found', 404);
        }

        $file = $submission['attachments'][$fileIndex];

        return response()->download(storage_path('app/public/' . $file['path']), $file['name']);
    }

    /**
     * Get assignment statistics
     *
     * @OA\Get(
     *     path="/api/v1/teacher/assignments/{id}/statistics",
     *     tags={"Teacher - Assignments"},
     *     summary="Get assignment statistics",
     *     description="Returns statistical data about submissions, scores, and completion rates",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
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
     *                 @OA\Property(property="submitted", type="integer", example=25),
     *                 @OA\Property(property="pending", type="integer", example=5),
     *                 @OA\Property(property="graded", type="integer", example=20),
     *                 @OA\Property(property="average_score", type="number", example=78.5),
     *                 @OA\Property(property="highest_score", type="number", example=95),
     *                 @OA\Property(property="lowest_score", type="number", example=45),
     *                 @OA\Property(property="completion_rate", type="number", example=83.3)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function statistics(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $stats = $this->assignmentService->getStatistics($id, $teacher->employee->id);

        return $this->successResponse($stats);
    }

    /**
     * Get assignment activities
     *
     * @OA\Get(
     *     path="/api/v1/teacher/assignments/{id}/activities",
     *     tags={"Teacher - Assignments"},
     *     summary="Get assignment activity log",
     *     description="Returns recent activities related to the assignment (submissions, grades, etc.)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of activities to return",
     *         required=false,
     *         @OA\Schema(type="integer", default=20, example=20)
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
     *                     @OA\Property(property="type", type="string", example="submission"),
     *                     @OA\Property(property="description", type="string", example="John Doe submitted assignment"),
     *                     @OA\Property(property="user_name", type="string", example="John Doe"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Assignment not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function activities(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();
        $limit = $request->input('limit', 20);

        $activities = $this->assignmentService->getActivities($id, $teacher->employee->id, $limit);

        return $this->successResponse($activities);
    }

    /**
     * Get teacher's subjects
     *
     * @OA\Get(
     *     path="/api/v1/teacher/assignments/my-subjects",
     *     tags={"Teacher - Assignments"},
     *     summary="Get teacher's assigned subjects",
     *     description="Returns a list of all subjects that the teacher is assigned to teach",
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
     *                     @OA\Property(property="credit", type="integer", example=3)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function mySubjects(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $subjects = $this->assignmentService->getTeacherSubjects($teacher->employee->id);

        return $this->successResponse($subjects);
    }

    /**
     * Get teacher's groups
     *
     * @OA\Get(
     *     path="/api/v1/teacher/assignments/my-groups",
     *     tags={"Teacher - Assignments"},
     *     summary="Get teacher's assigned groups",
     *     description="Returns a list of all academic groups that the teacher teaches",
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
     *                     @OA\Property(property="id", type="integer", example=5),
     *                     @OA\Property(property="name", type="string", example="CS-101"),
     *                     @OA\Property(property="code", type="string", example="CS101"),
     *                     @OA\Property(property="level", type="integer", example=1),
     *                     @OA\Property(property="students_count", type="integer", example=30)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function myGroups(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $groups = $this->assignmentService->getTeacherGroups($teacher->employee->id);

        return $this->successResponse($groups);
    }
}
