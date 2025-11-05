<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Teacher Dashboard Controller
 *
 * MODULAR MONOLITH - Teacher Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Teacher
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
     * Get teacher dashboard data
     *
     * @OA\Get(
     *     path="/api/v1/teacher/dashboard",
     *     tags={"Teacher - Dashboard"},
     *     summary="Get teacher dashboard data",
     *     description="Returns comprehensive dashboard data including statistics, upcoming classes, recent activities, and notifications",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Dashboard ma'lumotlari"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="statistics",
     *                     type="object",
     *                     @OA\Property(property="total_subjects", type="integer", example=5),
     *                     @OA\Property(property="total_groups", type="integer", example=8),
     *                     @OA\Property(property="total_students", type="integer", example=150),
     *                     @OA\Property(property="pending_assignments", type="integer", example=12),
     *                     @OA\Property(property="todays_classes", type="integer", example=3)
     *                 ),
     *                 @OA\Property(
     *                     property="upcoming_classes",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="subject_name", type="string", example="Mathematics"),
     *                         @OA\Property(property="group_name", type="string", example="CS-101"),
     *                         @OA\Property(property="time", type="string", example="09:00 - 10:30"),
     *                         @OA\Property(property="room", type="string", example="Room 305"),
     *                         @OA\Property(property="date", type="string", format="date", example="2025-01-15")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="recent_submissions",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="student_name", type="string", example="John Doe"),
     *                         @OA\Property(property="assignment_title", type="string", example="Homework 1"),
     *                         @OA\Property(property="submitted_at", type="string", format="date-time")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="notifications",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="type", type="string", example="new_submission"),
     *                         @OA\Property(property="message", type="string", example="New submission received"),
     *                         @OA\Property(property="created_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
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
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // Get teacher ID from authenticated user
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Delegate to service (business logic layer)
        $dashboard = $this->dashboardService->getDashboardData($teacherId);

        return $this->successResponse($dashboard, 'Dashboard ma\'lumotlari');
    }

    /**
     * Get recent activities
     *
     * @OA\Get(
     *     path="/api/v1/teacher/dashboard/activities",
     *     tags={"Teacher - Dashboard"},
     *     summary="Get recent activities",
     *     description="Returns a list of recent activities related to the teacher (submissions, assignments, grades, etc.)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of activities to return (default 10)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, example=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="So'nggi faoliyatlar"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="type", type="string", example="submission", description="Activity type: submission, assignment, grade, attendance"),
     *                     @OA\Property(property="title", type="string", example="New submission received"),
     *                     @OA\Property(property="description", type="string", example="John Doe submitted Homework 1"),
     *                     @OA\Property(property="subject_name", type="string", nullable=true, example="Mathematics"),
     *                     @OA\Property(property="student_name", type="string", nullable=true, example="John Doe"),
     *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-01-15 14:30:00"),
     *                     @OA\Property(property="icon", type="string", example="file-text"),
     *                     @OA\Property(property="color", type="string", example="blue")
     *                 )
     *             )
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
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function activities(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;
        $limit = $request->input('limit', 10);

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Delegate to service
        $activities = $this->dashboardService->getRecentActivities($teacherId, $limit);

        return $this->successResponse($activities, 'So\'nggi faoliyatlar');
    }

    /**
     * Get teacher statistics
     *
     * @OA\Get(
     *     path="/api/v1/teacher/dashboard/stats",
     *     tags={"Teacher - Dashboard"},
     *     summary="Get teacher statistics",
     *     description="Returns comprehensive statistical data about subjects, students, assignments, and attendance",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistik ma'lumotlar"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_subjects", type="integer", example=5, description="Total number of subjects taught"),
     *                 @OA\Property(property="total_groups", type="integer", example=8, description="Total number of groups"),
     *                 @OA\Property(property="total_students", type="integer", example=150, description="Total number of students"),
     *                 @OA\Property(property="total_classes", type="integer", example=120, description="Total classes this semester"),
     *                 @OA\Property(property="classes_today", type="integer", example=3, description="Classes scheduled for today"),
     *                 @OA\Property(
     *                     property="assignments",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=25),
     *                     @OA\Property(property="published", type="integer", example=20),
     *                     @OA\Property(property="draft", type="integer", example=5),
     *                     @OA\Property(property="pending_grading", type="integer", example=45)
     *                 ),
     *                 @OA\Property(
     *                     property="attendance",
     *                     type="object",
     *                     @OA\Property(property="average_rate", type="number", format="float", example=88.5, description="Average attendance rate percentage"),
     *                     @OA\Property(property="total_marked", type="integer", example=2400, description="Total attendance records marked"),
     *                     @OA\Property(property="present_count", type="integer", example=2124),
     *                     @OA\Property(property="absent_count", type="integer", example=276)
     *                 ),
     *                 @OA\Property(
     *                     property="submissions",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=380),
     *                     @OA\Property(property="graded", type="integer", example=310),
     *                     @OA\Property(property="pending", type="integer", example=70),
     *                     @OA\Property(property="average_score", type="number", format="float", example=76.3)
     *                 ),
     *                 @OA\Property(
     *                     property="performance",
     *                     type="object",
     *                     @OA\Property(property="excellent", type="integer", example=85, description="Students with excellent performance"),
     *                     @OA\Property(property="good", type="integer", example=45, description="Students with good performance"),
     *                     @OA\Property(property="average", type="integer", example=15, description="Students with average performance"),
     *                     @OA\Property(property="poor", type="integer", example=5, description="Students with poor performance")
     *                 )
     *             )
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
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function stats(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Delegate to service
        $stats = $this->dashboardService->getSummaryStats($teacherId, \Carbon\Carbon::today());

        return $this->successResponse($stats, 'Statistik ma\'lumotlar');
    }
}
