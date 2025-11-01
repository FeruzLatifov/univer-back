<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\EAttendance;
use App\Models\ESubjectSchedule;
use App\Models\EStudent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Attendance Controller
 *
 * Manages attendance marking and reports
 */
class AttendanceController extends Controller
{
    use ApiResponse;

    /**
     * Get attendance list for a subject schedule
     *
     * GET /api/v1/teacher/subject/{id}/attendance
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();
        $date = $request->input('date', now()->format('Y-m-d'));

        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with(['group', 'subject'])
            ->first();

        if (!$schedule) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        // Get students from group
        $students = EStudent::where('_group', $schedule->_group)
            ->where('active', true)
            ->get();

        // Get attendance records for the date
        $attendanceRecords = EAttendance::where('_subject_schedule', $schedule->id)
            ->where('lesson_date', $date)
            ->get()
            ->keyBy('_student');

        // Build student list with attendance status
        $studentList = $students->map(function ($student) use ($attendanceRecords) {
            $attendance = $attendanceRecords->get($student->id);

            return [
                'id' => $student->id,
                'student_id' => $student->student_id_number,
                'full_name' => $student->full_name,
                'photo' => $student->image,
                'attendance_status' => $attendance ? $attendance->_attendance_type : null,
                'attendance_status_name' => $attendance ? $attendance->status_name : 'Belgilanmagan',
                'reason' => $attendance ? $attendance->reason : null,
            ];
        });

        return $this->successResponse([
            'subject' => [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
            ],
            'group' => [
                'id' => $schedule->group->id,
                'name' => $schedule->group->name,
            ],
            'date' => $date,
            'students' => $studentList,
        ], 'Davomat ro\'yxati');
    }

    /**
     * Mark attendance for students
     *
     * POST /api/v1/teacher/attendance/mark
     *
     * Body:
     * {
     *   "subject_schedule_id": 123,
     *   "date": "2025-01-15",
     *   "attendance": [
     *     {"student_id": 1, "status": "11"},
     *     {"student_id": 2, "status": "12", "reason": "Kasallik"}
     *   ]
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function mark(Request $request): JsonResponse
    {
        $teacher = $request->user();

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

        // Verify teacher has access to this schedule
        $schedule = ESubjectSchedule::where('id', $request->subject_schedule_id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->first();

        if (!$schedule) {
            return $this->forbiddenResponse('Sizda bu darsga kirish huquqi yo\'q');
        }

        try {
            DB::beginTransaction();

            $markedCount = 0;

            foreach ($request->attendance as $attendanceData) {
                // Update or create attendance record
                EAttendance::updateOrCreate(
                    [
                        '_student' => $attendanceData['student_id'],
                        '_subject_schedule' => $request->subject_schedule_id,
                        'lesson_date' => $request->date,
                    ],
                    [
                        '_attendance_type' => $attendanceData['status'],
                        'reason' => $attendanceData['reason'] ?? null,
                        'active' => true,
                    ]
                );

                $markedCount++;
            }

            DB::commit();

            return $this->successResponse([
                'marked_count' => $markedCount,
                'date' => $request->date,
            ], 'Davomat muvaffaqiyatli belgilandi');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Davomat belgilashda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Update single attendance record
     *
     * PUT /api/v1/teacher/attendance/{id}
     *
     * @param Request $request
     * @param int $id Attendance ID
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:11,12,13,14',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $attendance = EAttendance::findOrFail($id);

        // Verify teacher has access
        $schedule = ESubjectSchedule::where('id', $attendance->_subject_schedule)
            ->where('_employee', $teacher->employee->id)
            ->first();

        if (!$schedule) {
            return $this->forbiddenResponse('Sizda bu davomatni o\'zgartirish huquqi yo\'q');
        }

        $attendance->update([
            '_attendance_type' => $request->status,
            'reason' => $request->reason,
        ]);

        return $this->successResponse([
            'id' => $attendance->id,
            'status' => $attendance->_attendance_type,
            'status_name' => $attendance->status_name,
            'reason' => $attendance->reason,
        ], 'Davomat yangilandi');
    }

    /**
     * Get attendance report for a subject
     *
     * GET /api/v1/teacher/attendance/report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function report(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $request->subject_id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with(['subject', 'group'])
            ->first();

        if (!$schedule) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        // Get students
        $students = EStudent::where('_group', $schedule->_group)
            ->where('active', true)
            ->get();

        // Get attendance records for date range
        $attendances = EAttendance::where('_subject_schedule', $schedule->id)
            ->whereBetween('lesson_date', [$request->start_date, $request->end_date])
            ->get();

        // Build report
        $totalClasses = $attendances->pluck('lesson_date')->unique()->count();

        $studentReport = $students->map(function ($student) use ($attendances, $totalClasses) {
            $studentAttendances = $attendances->where('_student', $student->id);

            $present = $studentAttendances->where('_attendance_type', EAttendance::STATUS_PRESENT)->count();
            $absent = $studentAttendances->where('_attendance_type', EAttendance::STATUS_ABSENT)->count();
            $late = $studentAttendances->where('_attendance_type', EAttendance::STATUS_LATE)->count();
            $excused = $studentAttendances->where('_attendance_type', EAttendance::STATUS_EXCUSED)->count();

            $attendanceRate = $totalClasses > 0 ? round(($present / $totalClasses) * 100, 1) : 0;

            return [
                'student_id' => $student->student_id_number,
                'full_name' => $student->full_name,
                'total_classes' => $totalClasses,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'excused' => $excused,
                'attendance_rate' => $attendanceRate,
            ];
        });

        return $this->successResponse([
            'subject' => $schedule->subject->name,
            'group' => $schedule->group->name,
            'period' => [
                'start' => $request->start_date,
                'end' => $request->end_date,
            ],
            'total_classes' => $totalClasses,
            'students' => $studentReport,
        ], 'Davomat hisoboti');
    }
}
