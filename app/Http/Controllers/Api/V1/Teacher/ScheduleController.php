<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\ScheduleService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Teacher Schedule Controller
 *
 * MODULAR MONOLITH - Teacher Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Teacher
 */
class ScheduleController extends Controller
{
    use ApiResponse;

    /**
     * Schedule Service (injected)
     */
    private ScheduleService $scheduleService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(ScheduleService $scheduleService)
    {
        $this->scheduleService = $scheduleService;
    }

    /**
     * Get teacher's weekly schedule
     *
     * @OA\Get(
     *     path="/api/v1/teacher/schedule",
     *     tags={"Teacher - Schedule"},
     *     summary="Get teacher's weekly schedule",
     *     description="Returns the complete weekly schedule for the authenticated teacher with all lessons organized by day",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="semester",
     *         in="query",
     *         description="Semester ID (optional, defaults to current semester)",
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
     *                 @OA\Property(
     *                     property="monday",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="subject_name", type="string", example="Mathematics"),
     *                         @OA\Property(property="group_name", type="string", example="CS-101"),
     *                         @OA\Property(property="room", type="string", example="Room 301"),
     *                         @OA\Property(property="start_time", type="string", example="09:00"),
     *                         @OA\Property(property="end_time", type="string", example="10:30"),
     *                         @OA\Property(property="lesson_type", type="string", example="lecture")
     *                     )
     *                 ),
     *                 @OA\Property(property="tuesday", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="wednesday", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="thursday", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="friday", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="saturday", type="array", @OA\Items(type="object"))
     *             ),
     *             @OA\Property(property="message", type="string", example="Haftalik jadval")
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
     *
     * GET /api/v1/teacher/schedule
     *
     * ✅ CLEAN ARCHITECTURE
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;
        $semester = $request->input('semester');

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Delegate to service
        $weeklySchedule = $this->scheduleService->getWeeklySchedule($teacherId, $semester);

        return $this->successResponse($weeklySchedule, 'Haftalik jadval');
    }

    /**
     * Get schedule for a specific day
     *
     * @OA\Get(
     *     path="/api/v1/teacher/schedule/day/{day}",
     *     tags={"Teacher - Schedule"},
     *     summary="Get schedule for a specific day",
     *     description="Returns all lessons scheduled for a specific day of the week (1=Monday, 6=Saturday)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="day",
     *         in="path",
     *         description="Day of week (1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday)",
     *         required=true,
     *         @OA\Schema(type="integer", minimum=1, maximum=6, example=1)
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
     *                     @OA\Property(property="subject_name", type="string", example="Mathematics"),
     *                     @OA\Property(property="subject_code", type="string", example="MATH101"),
     *                     @OA\Property(property="group_name", type="string", example="CS-101"),
     *                     @OA\Property(property="room", type="string", example="Room 301"),
     *                     @OA\Property(property="building", type="string", example="Main Building"),
     *                     @OA\Property(property="start_time", type="string", example="09:00"),
     *                     @OA\Property(property="end_time", type="string", example="10:30"),
     *                     @OA\Property(property="lesson_type", type="string", example="lecture"),
     *                     @OA\Property(property="pair_number", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Kunlik jadval")
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
     *         description="Validation error - Invalid day",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Kun 1-6 oralig'ida bo'lishi kerak")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     *
     * GET /api/v1/teacher/schedule/day/{day}
     *
     * ✅ CLEAN ARCHITECTURE
     *
     * @param Request $request
     * @param int $day Day of week (1-6)
     * @return JsonResponse
     */
    public function day(Request $request, int $day): JsonResponse
    {
        // Validate day
        if (!$this->scheduleService->isValidDay($day)) {
            return $this->validationErrorResponse(['day' => 'Kun 1-6 oralig\'ida bo\'lishi kerak']);
        }

        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Delegate to service
        $daySchedule = $this->scheduleService->getDaySchedule($teacherId, $day);

        return $this->successResponse($daySchedule, 'Kunlik jadval');
    }

    /**
     * Get teacher's workload summary
     *
     * @OA\Get(
     *     path="/api/v1/teacher/workload",
     *     tags={"Teacher - Schedule"},
     *     summary="Get teacher's workload summary",
     *     description="Returns a comprehensive summary of the teacher's workload including total hours, number of subjects, groups, and distribution across different lesson types",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="semester",
     *         in="query",
     *         description="Semester ID (optional, defaults to current semester)",
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
     *                 @OA\Property(property="total_hours", type="number", example=18.5),
     *                 @OA\Property(property="total_subjects", type="integer", example=3),
     *                 @OA\Property(property="total_groups", type="integer", example=5),
     *                 @OA\Property(property="total_lessons", type="integer", example=12),
     *                 @OA\Property(
     *                     property="by_type",
     *                     type="object",
     *                     @OA\Property(property="lecture", type="integer", example=6),
     *                     @OA\Property(property="practice", type="integer", example=4),
     *                     @OA\Property(property="lab", type="integer", example=2)
     *                 ),
     *                 @OA\Property(
     *                     property="subjects",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="name", type="string", example="Mathematics"),
     *                         @OA\Property(property="hours", type="number", example=6),
     *                         @OA\Property(property="groups_count", type="integer", example=2)
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Dars yuklama")
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
     *
     * GET /api/v1/teacher/workload
     *
     * ✅ CLEAN ARCHITECTURE
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function workload(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;
        $semester = $request->input('semester');

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Delegate to service
        $workload = $this->scheduleService->getWorkload($teacherId, $semester);

        return $this->successResponse($workload, 'Dars yuklama');
    }

    /**
     * Get groups taught by teacher
     *
     * @OA\Get(
     *     path="/api/v1/teacher/groups",
     *     tags={"Teacher - Schedule"},
     *     summary="Get groups taught by teacher",
     *     description="Returns a list of all academic groups that the authenticated teacher is assigned to teach",
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
     *                     @OA\Property(property="course", type="integer", example=1),
     *                     @OA\Property(property="students_count", type="integer", example=30),
     *                     @OA\Property(property="faculty", type="string", example="Computer Science"),
     *                     @OA\Property(property="department", type="string", example="Software Engineering"),
     *                     @OA\Property(
     *                         property="subjects",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="name", type="string", example="Mathematics")
     *                         )
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Guruhlar ro'yxati")
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
     *
     * GET /api/v1/teacher/groups
     *
     * ✅ CLEAN ARCHITECTURE
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function groups(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Delegate to service
        $groups = $this->scheduleService->getTeacherGroups($teacherId);

        return $this->successResponse($groups, 'Guruhlar ro\'yxati');
    }

    /**
     * Get today's schedule
     *
     * @OA\Get(
     *     path="/api/v1/teacher/schedule/today",
     *     tags={"Teacher - Schedule"},
     *     summary="Get today's schedule",
     *     description="Returns all lessons scheduled for the current day, sorted by time. Useful for quick access to today's teaching schedule.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="current_day", type="string", example="Monday"),
     *                 @OA\Property(property="date", type="string", format="date", example="2025-11-05"),
     *                 @OA\Property(
     *                     property="lessons",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="subject_name", type="string", example="Mathematics"),
     *                         @OA\Property(property="subject_code", type="string", example="MATH101"),
     *                         @OA\Property(property="group_name", type="string", example="CS-101"),
     *                         @OA\Property(property="room", type="string", example="Room 301"),
     *                         @OA\Property(property="building", type="string", example="Main Building"),
     *                         @OA\Property(property="start_time", type="string", example="09:00"),
     *                         @OA\Property(property="end_time", type="string", example="10:30"),
     *                         @OA\Property(property="lesson_type", type="string", example="lecture"),
     *                         @OA\Property(property="pair_number", type="integer", example=1),
     *                         @OA\Property(property="is_current", type="boolean", example=false),
     *                         @OA\Property(property="is_upcoming", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(property="total_lessons", type="integer", example=4)
     *             ),
     *             @OA\Property(property="message", type="string", example="Bugungi jadval")
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
     *
     * GET /api/v1/teacher/schedule/today
     *
     * ✅ CLEAN ARCHITECTURE
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Delegate to service
        $schedule = $this->scheduleService->getTodaySchedule($teacherId);

        return $this->successResponse($schedule, 'Bugungi jadval');
    }
}
