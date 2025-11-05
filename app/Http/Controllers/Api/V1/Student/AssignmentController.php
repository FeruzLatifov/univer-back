<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EAssignment;
use App\Models\EAssignmentSubmission;

class AssignmentController extends Controller
{
    /**
     * Get all assignments for student
     *
     * @OA\Get(
     *     path="/api/v1/student/assignments",
     *     tags={"Student - Assignments"},
     *     summary="Get all assignments for authenticated student",
     *     description="Returns a paginated list of all published assignments for the student's curriculum. Shows submission status and grades.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
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
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=45),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Homework Assignment 1"),
     *                         @OA\Property(property="description", type="string", example="Complete exercises 1-10"),
     *                         @OA\Property(property="subject", type="string", example="Mathematics"),
     *                         @OA\Property(property="deadline", type="string", format="date-time", example="2025-11-15 23:59:00"),
     *                         @OA\Property(property="max_grade", type="integer", example=100),
     *                         @OA\Property(property="status", type="string", enum={"pending", "submitted"}, example="submitted"),
     *                         @OA\Property(
     *                             property="submission",
     *                             type="object",
     *                             nullable=true,
     *                             @OA\Property(property="id", type="integer", example=15),
     *                             @OA\Property(property="submitted_at", type="string", format="date-time", example="2025-11-10 14:30:00"),
     *                             @OA\Property(property="grade", type="number", nullable=true, example=85.5)
     *                         )
     *                     )
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
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $student = $request->user();

        $assignments = EAssignment::whereHas('subject.curriculumSubjects', function ($query) use ($student) {
                $query->where('curriculum_id', $student->_curriculum);
            })
            ->where('status', 'published')
            ->with(['subject'])
            ->orderBy('deadline', 'desc')
            ->paginate(15);

        $assignments->getCollection()->transform(function ($assignment) use ($student) {
            $submission = $assignment->submissions()->where('_student', $student->id)->first();
            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'subject' => $assignment->subject->name ?? 'N/A',
                'deadline' => $assignment->deadline,
                'max_grade' => $assignment->max_grade,
                'status' => $submission ? 'submitted' : 'pending',
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'submitted_at' => $submission->submitted_at,
                    'grade' => $submission->grade,
                ] : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $assignments]);
    }

    /**
     * Submit assignment
     *
     * @OA\Post(
     *     path="/api/v1/student/assignments/{id}/submit",
     *     tags={"Student - Assignments"},
     *     summary="Submit an assignment",
     *     description="Allows student to submit or update their assignment submission with content and optional files",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Assignment ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"content"},
     *                 @OA\Property(
     *                     property="content",
     *                     type="string",
     *                     description="Submission content/answer",
     *                     example="Here is my solution to the assignment problems..."
     *                 ),
     *                 @OA\Property(
     *                     property="files",
     *                     type="array",
     *                     maxItems=5,
     *                     nullable=true,
     *                     description="Optional file attachments (max 5 files)",
     *                     @OA\Items(type="string", format="binary")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Assignment submitted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="_assignment", type="integer", example=1),
     *                 @OA\Property(property="_student", type="integer", example=123),
     *                 @OA\Property(property="content", type="string", example="Here is my solution..."),
     *                 @OA\Property(property="submitted_at", type="string", format="date-time", example="2025-11-10 14:30:00"),
     *                 @OA\Property(property="status", type="string", example="submitted")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Deadline passed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Deadline passed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
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
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="content",
     *                     type="array",
     *                     @OA\Items(type="string", example="The content field is required")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function submit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'files' => 'nullable|array|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $student = $request->user();
        $assignment = EAssignment::findOrFail($id);

        if ($assignment->deadline < now()) {
            return response()->json(['success' => false, 'message' => 'Deadline passed'], 400);
        }

        $submission = EAssignmentSubmission::updateOrCreate(
            ['_assignment' => $id, '_student' => $student->id],
            [
                'content' => $request->content,
                'submitted_at' => now(),
                'status' => 'submitted',
            ]
        );

        return response()->json(['success' => true, 'data' => $submission], 201);
    }
}
