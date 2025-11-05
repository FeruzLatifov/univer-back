<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\AttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Attendance Controller
 *
 * MODULAR MONOLITH - Teacher Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Teacher
 */
class AttendanceController extends Controller
{
    use ApiResponse;

    /**
     * Attendance Service (injected)
     */
    private AttendanceService $attendanceService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Get attendance list for a subject schedule
     *
     * @OA\Get(
     *     path="/api/v1/teacher/subject/{id}/attendance",
     *     tags={"Teacher - Attendance"},
     *     summary="Get attendance list for a subject schedule",
     *     description="Returns a list of students with their attendance status for a specific subject schedule and date",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         description="Attendance date (defaults to today)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-01-15")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Davomat ro'yxati"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="student_id", type="integer", example=1),
     *                     @OA\Property(property="student_name", type="string", example="John Doe"),
     *                     @OA\Property(property="student_code", type="string", example="STU001"),
     *                     @OA\Property(property="status", type="string", example="11", description="11=present, 12=absent, 13=late, 14=excused"),
     *                     @OA\Property(property="reason", type="string", nullable=true, example="Kasallik")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teacher profile or subject not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Teacher profile not found")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;
        $date = $request->input('date', now()->format('Y-m-d'));

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        try {
            // Delegate to service
            $attendanceList = $this->attendanceService->getAttendanceList($teacherId, $id, $date);

            return $this->successResponse($attendanceList, 'Davomat ro\'yxati');

        } catch (\Exception $e) {
            return $this->forbiddenResponse($e->getMessage());
        }
    }

    /**
     * Mark attendance for students
     *
     * @OA\Post(
     *     path="/api/v1/teacher/attendance/mark",
     *     tags={"Teacher - Attendance"},
     *     summary="Mark attendance for students",
     *     description="Records attendance status for multiple students in a single request. Statuses: 11=present, 12=absent, 13=late, 14=excused",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subject_schedule_id", "date", "attendance"},
     *             @OA\Property(
     *                 property="subject_schedule_id",
     *                 type="integer",
     *                 example=123,
     *                 description="Subject schedule ID (must exist)"
     *             ),
     *             @OA\Property(
     *                 property="date",
     *                 type="string",
     *                 format="date",
     *                 example="2025-01-15",
     *                 description="Attendance date"
     *             ),
     *             @OA\Property(
     *                 property="attendance",
     *                 type="array",
     *                 description="Array of student attendance records",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"student_id", "status"},
     *                     @OA\Property(property="student_id", type="integer", example=1, description="Student ID"),
     *                     @OA\Property(property="status", type="string", enum={"11", "12", "13", "14"}, example="11", description="Attendance status"),
     *                     @OA\Property(property="reason", type="string", nullable=true, maxLength=500, example="Kasallik", description="Reason for absence/late")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance marked successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Davomat muvaffaqiyatli belgilandi"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="marked_count", type="integer", example=25),
     *                 @OA\Property(property="date", type="string", example="2025-01-15")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The attendance field is required")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Teacher profile not found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function mark(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'subject_schedule_id' => 'required|exists:e_subject_schedule,id',
            'date' => 'required|date',
            'attendance' => 'required|array|min:1',
            'attendance.*.student_id' => 'required|exists:e_student,id',
            'attendance.*.status' => 'required|in:11,12,13,14', // present, absent, late, excused
            'attendance.*.reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Delegate to service
            $result = $this->attendanceService->markAttendance(
                $teacherId,
                $request->subject_schedule_id,
                $request->date,
                $request->attendance
            );

            return $this->successResponse($result, 'Davomat muvaffaqiyatli belgilandi');

        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Update single attendance record
     *
     * @OA\Put(
     *     path="/api/v1/teacher/attendance/{id}",
     *     tags={"Teacher - Attendance"},
     *     summary="Update single attendance record",
     *     description="Updates the status and reason for an existing attendance record",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Attendance record ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"11", "12", "13", "14"},
     *                 example="11",
     *                 description="Attendance status: 11=present, 12=absent, 13=late, 14=excused"
     *             ),
     *             @OA\Property(
     *                 property="reason",
     *                 type="string",
     *                 nullable=true,
     *                 maxLength=500,
     *                 example="Kasallik",
     *                 description="Reason for absence/late (optional)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Attendance updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Davomat yangilandi"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="status", type="string", example="11"),
     *                 @OA\Property(property="reason", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The status field is required")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Teacher profile or attendance record not found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
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
            'status' => 'required|in:11,12,13,14',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Delegate to service
            $result = $this->attendanceService->updateAttendance(
                $teacherId,
                $id,
                $request->status,
                $request->reason
            );

            return $this->successResponse($result, 'Davomat yangilandi');

        } catch (\Exception $e) {
            return $this->forbiddenResponse($e->getMessage());
        }
    }

    /**
     * Get attendance report for a subject
     *
     * @OA\Get(
     *     path="/api/v1/teacher/attendance/report",
     *     tags={"Teacher - Attendance"},
     *     summary="Get attendance report for a subject",
     *     description="Returns detailed attendance statistics and report for a specific subject within a date range",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subject_id",
     *         in="query",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         description="Start date for report",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         description="End date for report (must be after or equal to start_date)",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2025-01-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Davomat hisoboti"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="subject_name", type="string", example="Mathematics"),
     *                 @OA\Property(property="total_classes", type="integer", example=20),
     *                 @OA\Property(property="total_students", type="integer", example=30),
     *                 @OA\Property(
     *                     property="statistics",
     *                     type="object",
     *                     @OA\Property(property="present_count", type="integer", example=550),
     *                     @OA\Property(property="absent_count", type="integer", example=30),
     *                     @OA\Property(property="late_count", type="integer", example=15),
     *                     @OA\Property(property="excused_count", type="integer", example=5),
     *                     @OA\Property(property="attendance_rate", type="number", format="float", example=91.67)
     *                 ),
     *                 @OA\Property(
     *                     property="students",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="student_id", type="integer", example=1),
     *                         @OA\Property(property="student_name", type="string", example="John Doe"),
     *                         @OA\Property(property="present", type="integer", example=18),
     *                         @OA\Property(property="absent", type="integer", example=2),
     *                         @OA\Property(property="late", type="integer", example=0),
     *                         @OA\Property(property="excused", type="integer", example=0),
     *                         @OA\Property(property="percentage", type="number", example=90.0)
     *                     )
     *                 )
     *             )
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
     *     @OA\Response(response=404, description="Teacher profile not found"),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
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
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            // Delegate to service
            $report = $this->attendanceService->getAttendanceReport(
                $teacherId,
                $request->subject_id,
                $request->start_date,
                $request->end_date
            );

            return $this->successResponse($report, 'Davomat hisoboti');

        } catch (\Exception $e) {
            return $this->forbiddenResponse($e->getMessage());
        }
    }
}
