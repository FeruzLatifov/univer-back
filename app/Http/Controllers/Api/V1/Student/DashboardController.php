<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Student Dashboard Controller
 *
 * MODULAR MONOLITH - Student Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Student
 */
class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Dashboard Service (injected)
     */
    private DashboardService $dashboardService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get student dashboard data
     *
     * @OA\Get(
     *     path="/api/v1/student/dashboard",
     *     tags={"Student - Dashboard"},
     *     summary="Get student dashboard data",
     *     description="Returns comprehensive dashboard data including upcoming assignments, attendance summary, grade statistics, schedule, and announcements",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="upcoming_assignments",
     *                     type="array",
     *                     description="List of upcoming assignments",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Homework 1"),
     *                         @OA\Property(property="subject", type="string", example="Mathematics"),
     *                         @OA\Property(property="deadline", type="string", format="date-time", example="2025-11-15 23:59:00")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="attendance_summary",
     *                     type="object",
     *                     @OA\Property(property="present", type="integer", example=120),
     *                     @OA\Property(property="absent", type="integer", example=10),
     *                     @OA\Property(property="rate", type="number", example=92.3)
     *                 ),
     *                 @OA\Property(
     *                     property="grade_statistics",
     *                     type="object",
     *                     @OA\Property(property="gpa", type="number", example=3.85),
     *                     @OA\Property(property="total_subjects", type="integer", example=8),
     *                     @OA\Property(property="average_score", type="number", example=85.5)
     *                 ),
     *                 @OA\Property(
     *                     property="today_schedule",
     *                     type="array",
     *                     description="Today's class schedule",
     *                     @OA\Items(
     *                         @OA\Property(property="subject", type="string", example="Physics"),
     *                         @OA\Property(property="time", type="string", example="09:00-10:30"),
     *                         @OA\Property(property="room", type="string", example="Room 201")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="announcements",
     *                     type="array",
     *                     description="Recent announcements",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=5),
     *                         @OA\Property(property="title", type="string", example="Exam Schedule"),
     *                         @OA\Property(property="date", type="string", format="date", example="2025-11-05")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Student dashboard")
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
     *         description="Student not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Student not found")
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
    public function index(Request $request): JsonResponse
    {
        $student = $request->user();

        if (!$student) {
            return $this->errorResponse('Student not found', 404);
        }

        // Delegate to service
        $dashboardData = $this->dashboardService->getDashboardData($student);

        return $this->successResponse($dashboardData, 'Student dashboard');
    }
}
