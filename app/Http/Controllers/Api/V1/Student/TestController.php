<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESubjectTest;
use App\Models\EStudentTestAttempt;

class TestController extends Controller
{
    /**
     * Get available tests
     *
     * @OA\Get(
     *     path="/api/v1/student/tests",
     *     tags={"Student - Tests"},
     *     summary="Get available tests for student",
     *     description="Returns a list of all published and active tests available to the student, including attempt information and best scores",
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
     *                     @OA\Property(property="title", type="string", example="Midterm Exam - Mathematics"),
     *                     @OA\Property(property="subject", type="string", example="Mathematics"),
     *                     @OA\Property(property="duration", type="integer", example=90, description="Test duration in minutes"),
     *                     @OA\Property(property="start_time", type="string", format="date-time", example="2025-11-10 09:00:00"),
     *                     @OA\Property(property="end_time", type="string", format="date-time", example="2025-11-10 12:00:00"),
     *                     @OA\Property(property="max_attempts", type="integer", example=3),
     *                     @OA\Property(property="attempts_count", type="integer", example=1, description="Number of attempts taken"),
     *                     @OA\Property(property="best_score", type="number", nullable=true, example=85.5, description="Best score achieved"),
     *                     @OA\Property(property="can_attempt", type="boolean", example=true, description="Whether student can take the test")
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

        $tests = ESubjectTest::whereHas('subject.curriculumSubjects', function ($query) use ($student) {
                $query->where('curriculum_id', $student->_curriculum);
            })
            ->where('status', 'published')
            ->where('end_time', '>=', now())
            ->with(['subject'])
            ->get()
            ->map(function ($test) use ($student) {
                $attempts = $test->attempts()->where('_student', $student->id)->count();
                $bestScore = $test->attempts()->where('_student', $student->id)->max('score');

                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'subject' => $test->subject->name ?? 'N/A',
                    'duration' => $test->duration,
                    'start_time' => $test->start_time,
                    'end_time' => $test->end_time,
                    'max_attempts' => $test->max_attempts,
                    'attempts_count' => $attempts,
                    'best_score' => $bestScore,
                    'can_attempt' => $attempts < $test->max_attempts && $test->start_time <= now(),
                ];
            });

        return response()->json(['success' => true, 'data' => $tests]);
    }

    /**
     * Get test results history
     *
     * @OA\Get(
     *     path="/api/v1/student/test-results",
     *     tags={"Student - Tests"},
     *     summary="Get student test results history",
     *     description="Returns a paginated list of all test attempts with scores and completion details",
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
     *                 @OA\Property(property="total", type="integer", example=25),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="_student", type="integer", example=123),
     *                         @OA\Property(
     *                             property="test",
     *                             type="object",
     *                             @OA\Property(property="id", type="integer", example=5),
     *                             @OA\Property(property="title", type="string", example="Midterm Exam"),
     *                             @OA\Property(
     *                                 property="subject",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=1),
     *                                 @OA\Property(property="name", type="string", example="Mathematics")
     *                             )
     *                         ),
     *                         @OA\Property(property="score", type="number", example=85.5),
     *                         @OA\Property(property="started_at", type="string", format="date-time", example="2025-11-05 10:00:00"),
     *                         @OA\Property(property="finished_at", type="string", format="date-time", example="2025-11-05 11:25:00"),
     *                         @OA\Property(property="status", type="string", enum={"in_progress", "completed", "timeout"}, example="completed")
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
    public function results(Request $request)
    {
        $student = $request->user();

        $results = EStudentTestAttempt::where('_student', $student->id)
            ->with(['test.subject'])
            ->orderBy('finished_at', 'desc')
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $results]);
    }
}
