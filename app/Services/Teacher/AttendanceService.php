<?php

namespace App\Services\Teacher;

use App\Models\EAttendance;
use App\Models\ESubjectSchedule;
use App\Models\EStudent;
use Illuminate\Support\Facades\DB;

/**
 * Teacher Attendance Service
 *
 * BUSINESS LOGIC LAYER
 * ======================
 *
 * Modular Monolith Architecture - Teacher Module
 * Contains all business logic for teacher attendance management
 *
 * Controller → Service → Repository → Model
 *
 * @package App\Services\Teacher
 */
class AttendanceService
{
    /**
     * Get attendance list for a subject schedule
     *
     * @param int $teacherId Teacher employee ID
     * @param int $subjectId Subject ID
     * @param string $date Date (Y-m-d format)
     * @return array
     * @throws \Exception
     */
    public function getAttendanceList(int $teacherId, int $subjectId, string $date): array
    {
        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $subjectId)
            ->where('_employee', $teacherId)
            ->where('active', true)
            ->with(['group', 'subject'])
            ->first();

        if (!$schedule) {
            throw new \Exception('Sizda bu fanga kirish huquqi yo\'q');
        }

        // Get students from group (_group is in e_student_meta table)
        $students = EStudent::whereHas('meta', function($q) use ($schedule) {
                $q->where('_group', $schedule->_group);
            })
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

        return [
            'subject' => [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
            ],
            'group' => [
                'id' => $schedule->group->id,
                'name' => $schedule->group->name,
            ],
            'date' => $date,
            'students' => $studentList->toArray(),
        ];
    }

    /**
     * Mark attendance for students
     *
     * @param int $teacherId Teacher employee ID
     * @param int $scheduleId Subject schedule ID
     * @param string $date Lesson date (Y-m-d)
     * @param array $attendanceData Array of attendance records
     * @return array
     * @throws \Exception
     */
    public function markAttendance(int $teacherId, int $scheduleId, string $date, array $attendanceData): array
    {
        // Verify teacher has access to this schedule
        $schedule = ESubjectSchedule::where('id', $scheduleId)
            ->where('_employee', $teacherId)
            ->where('active', true)
            ->first();

        if (!$schedule) {
            throw new \Exception('Sizda bu darsga kirish huquqi yo\'q');
        }

        DB::beginTransaction();

        try {
            $markedCount = 0;

            foreach ($attendanceData as $attendance) {
                // Update or create attendance record
                EAttendance::updateOrCreate(
                    [
                        '_student' => $attendance['student_id'],
                        '_subject_schedule' => $scheduleId,
                        'lesson_date' => $date,
                    ],
                    [
                        '_attendance_type' => $attendance['status'],
                        'reason' => $attendance['reason'] ?? null,
                        'active' => true,
                    ]
                );

                $markedCount++;
            }

            DB::commit();

            return [
                'marked_count' => $markedCount,
                'date' => $date,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Davomat belgilashda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Update single attendance record
     *
     * @param int $teacherId Teacher employee ID
     * @param int $attendanceId Attendance record ID
     * @param string $status Attendance status
     * @param string|null $reason Reason for status
     * @return array
     * @throws \Exception
     */
    public function updateAttendance(int $teacherId, int $attendanceId, string $status, ?string $reason = null): array
    {
        $attendance = EAttendance::findOrFail($attendanceId);

        // Verify teacher has access
        $schedule = ESubjectSchedule::where('id', $attendance->_subject_schedule)
            ->where('_employee', $teacherId)
            ->first();

        if (!$schedule) {
            throw new \Exception('Sizda bu davomatni o\'zgartirish huquqi yo\'q');
        }

        $attendance->update([
            '_attendance_type' => $status,
            'reason' => $reason,
        ]);

        return [
            'id' => $attendance->id,
            'status' => $attendance->_attendance_type,
            'status_name' => $attendance->status_name,
            'reason' => $attendance->reason,
        ];
    }

    /**
     * Get attendance report for a subject
     *
     * @param int $teacherId Teacher employee ID
     * @param int $subjectId Subject ID
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array
     * @throws \Exception
     */
    public function getAttendanceReport(int $teacherId, int $subjectId, string $startDate, string $endDate): array
    {
        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $subjectId)
            ->where('_employee', $teacherId)
            ->where('active', true)
            ->with(['subject', 'group'])
            ->first();

        if (!$schedule) {
            throw new \Exception('Sizda bu fanga kirish huquqi yo\'q');
        }

        // Get students (_group is in e_student_meta table)
        $students = EStudent::whereHas('meta', function($q) use ($schedule) {
                $q->where('_group', $schedule->_group);
            })
            ->where('active', true)
            ->get();

        // Get attendance records for date range
        $attendances = EAttendance::where('_subject_schedule', $schedule->id)
            ->whereBetween('lesson_date', [$startDate, $endDate])
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

        return [
            'subject' => $schedule->subject->name,
            'group' => $schedule->group->name,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'total_classes' => $totalClasses,
            'students' => $studentReport->toArray(),
        ];
    }
}
