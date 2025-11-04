<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ESubjectSchedule;
use App\Models\EStudent;
use App\Models\EAssignment;
use App\Models\EAttendance;
use App\Models\EAttendanceControl;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Teacher Dashboard Controller
 *
 * Provides dashboard statistics and quick overview for teachers
 */
class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Get teacher dashboard data
     *
     * GET /api/v1/teacher/dashboard
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        $today = Carbon::today();
        $currentWeekDay = $today->dayOfWeek === 0 ? 7 : $today->dayOfWeek; // Convert Sunday=0 to 7

        // Get today's schedule using lesson_date
        $todaySchedule = ESubjectSchedule::where('_employee', $teacherId)
            ->where('lesson_date', $today->toDateString())
            ->where('active', true)
            ->with(['subject', 'group', 'lessonPair'])
            ->orderBy('_lesson_pair')
            ->get();

        // Get total students count across all groups
        $totalStudents = EStudent::select('e_student.*')
            ->join('e_student_meta', 'e_student.id', '=', 'e_student_meta._student')
            ->join('e_subject_schedule', 'e_student_meta._group', '=', 'e_subject_schedule._group')
            ->where('e_subject_schedule._employee', $teacherId)
            ->where('e_student.active', true)
            ->distinct('e_student.id')
            ->count();

        // Get unique subjects taught
        $subjectsQuery = ESubjectSchedule::where('_employee', $teacherId)
            ->where('active', true)
            ->select('_subject')
            ->distinct()
            ->count();

        // Get unique groups taught
        $groupsQuery = ESubjectSchedule::where('_employee', $teacherId)
            ->where('active', true)
            ->select('_group')
            ->distinct()
            ->count();

        // Get pending assignments (assignments that need grading)
        $pendingAssignments = 0;
        try {
            $pendingAssignments = DB::table('e_assignment_submission')
                ->join('e_assignment', 'e_assignment_submission._assignment', '=', 'e_assignment.id')
                ->join('e_subject_schedule', 'e_assignment._subject_schedule', '=', 'e_subject_schedule.id')
                ->where('e_subject_schedule._employee', $teacherId)
                ->where('e_assignment_submission.status', 'submitted')
                ->whereNull('e_assignment_submission.grade')
                ->count();
        } catch (\Exception $e) {
            // Table might not exist or structure different, skip
        }

        // ⚠️ Get pending attendance classes (classes without attendance marked)
        // These are past classes where attendance has not been taken yet
        $pendingAttendanceCount = ESubjectSchedule::where('_employee', $teacherId)
            ->where('lesson_date', '<', $today)
            ->where('lesson_date', '>=', $today->copy()->subDays(30)) // Last 30 days
            ->where('active', true)
            ->whereDoesntHave('attendanceControl')
            ->count();

        // Get list of pending attendance classes (last 5)
        $pendingAttendanceClasses = ESubjectSchedule::where('_employee', $teacherId)
            ->where('lesson_date', '<', $today)
            ->where('lesson_date', '>=', $today->copy()->subDays(30))
            ->where('active', true)
            ->whereDoesntHave('attendanceControl')
            ->with(['subject', 'group'])
            ->orderBy('lesson_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'lesson_date' => $schedule->lesson_date,
                    'days_ago' => Carbon::parse($schedule->lesson_date)->diffInDays(Carbon::now()),
                    'subject' => [
                        'id' => optional($schedule->subject)->id,
                        'name' => optional($schedule->subject)->name,
                        'code' => optional($schedule->subject)->code,
                    ],
                    'group' => [
                        'id' => optional($schedule->group)->id,
                        'name' => optional($schedule->group)->name,
                    ],
                    'training_type' => $schedule->_training_type,
                ];
            });

        // Get this week's classes count
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();

        $weeklyClasses = ESubjectSchedule::where('_employee', $teacherId)
            ->whereBetween('lesson_date', [$weekStart, $weekEnd])
            ->where('active', true)
            ->count();

        // Format today's schedule
        $todayClasses = $todaySchedule->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'subject' => [
                    'id' => $schedule->subject->id ?? null,
                    'name' => $schedule->subject->name ?? 'Unknown',
                    'code' => $schedule->subject->code ?? null,
                ],
                'group' => [
                    'id' => $schedule->group->id ?? null,
                    'name' => $schedule->group->name ?? 'Unknown',
                ],
                'time' => [
                    'start' => optional($schedule->lessonPair)->start_time,
                    'end' => optional($schedule->lessonPair)->end_time,
                    'pair_number' => optional($schedule->lessonPair)->number,
                ],
                'training_type' => $schedule->_training_type,
                'auditorium' => $schedule->_auditorium,
            ];
        });

        // Prepare dashboard data
        $dashboard = [
            'summary' => [
                'today_classes' => $todaySchedule->count(),
                'total_students' => $totalStudents,
                'total_subjects' => $subjectsQuery,
                'total_groups' => $groupsQuery,
                'pending_assignments' => $pendingAssignments,
                'pending_attendance' => $pendingAttendanceCount,
                'weekly_classes' => $weeklyClasses,
            ],
            'pending_attendance_classes' => $pendingAttendanceClasses,
            'today_schedule' => [
                'date' => $today->toDateString(),
                'day_name' => $this->getDayName($currentWeekDay),
                'classes' => $todayClasses,
            ],
            'quick_stats' => [
                'attendance_rate' => $this->getAttendanceRate($teacherId),
                'upcoming_exams' => $this->getUpcomingExamsCount($teacherId),
            ],
        ];

        return $this->successResponse($dashboard, 'Dashboard ma\'lumotlari');
    }

    /**
     * Get recent activities
     *
     * GET /api/v1/teacher/dashboard/activities
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function activities(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;
        $limit = $request->input('limit', 10);

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Get recent attendance records
        $recentAttendance = EAttendance::where('_employee', $teacherId)
            ->with(['student', 'subject'])
            ->orderBy('lesson_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($attendance) {
                $studentName = optional($attendance->student)->full_name ?? 'N/A';
                $subjectName = optional($attendance->subject)->name ?? 'N/A';

                return [
                    'type' => 'attendance',
                    'date' => $attendance->lesson_date,
                    'description' => "Davomat: {$studentName} - {$subjectName}",
                    'student' => optional($attendance->student)->full_name,
                ];
            });

        // Combine and sort by date
        $activities = $recentAttendance->sortByDesc('date')->take($limit)->values();

        return $this->successResponse($activities, 'So\'nggi faoliyatlar');
    }

    /**
     * Get teacher statistics
     *
     * GET /api/v1/teacher/dashboard/stats
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        // Get stats for current semester/year
        $stats = [
            'subjects' => [
                'total' => ESubjectSchedule::where('_employee', $teacherId)
                    ->where('active', true)
                    ->distinct('_subject')
                    ->count('_subject'),
            ],
            'groups' => [
                'total' => ESubjectSchedule::where('_employee', $teacherId)
                    ->where('active', true)
                    ->distinct('_group')
                    ->count('_group'),
            ],
            'students' => [
                'total' => $this->getTotalStudents($teacherId),
            ],
            'workload' => [
                'weekly_hours' => $this->getWeeklyHours($teacherId),
                'total_classes' => ESubjectSchedule::where('_employee', $teacherId)
                    ->where('active', true)
                    ->count(),
            ],
        ];

        return $this->successResponse($stats, 'Statistik ma\'lumotlar');
    }

    /**
     * Helper: Get attendance rate
     */
    private function getAttendanceRate(int $teacherId): float
    {
        try {
            $totalAttendance = EAttendance::where('_employee', $teacherId)
                ->where('lesson_date', '>=', Carbon::now()->subMonth())
                ->count();

            if ($totalAttendance === 0) {
                return 0;
            }

            $presentCount = EAttendance::where('_employee', $teacherId)
                ->where('lesson_date', '>=', Carbon::now()->subMonth())
                ->where(function ($query) {
                    $query->where('absent_off', 0)
                          ->orWhereNull('absent_off');
                })
                ->count();

            return round(($presentCount / $totalAttendance) * 100, 1);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Helper: Get upcoming exams count
     */
    private function getUpcomingExamsCount(int $teacherId): int
    {
        try {
            return DB::table('e_subject_exam_schedule')
                ->where('_employee', $teacherId)
                ->where('exam_date', '>=', Carbon::today())
                ->where('exam_date', '<=', Carbon::today()->addDays(30))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Helper: Get total students
     */
    private function getTotalStudents(int $teacherId): int
    {
        return EStudent::select('e_student.*')
            ->join('e_student_meta', 'e_student.id', '=', 'e_student_meta._student')
            ->join('e_subject_schedule', 'e_student_meta._group', '=', 'e_subject_schedule._group')
            ->where('e_subject_schedule._employee', $teacherId)
            ->where('e_student.active', true)
            ->distinct('e_student.id')
            ->count();
    }

    /**
     * Helper: Get weekly hours
     */
    private function getWeeklyHours(int $teacherId): int
    {
        // Assuming each class is 2 hours (1 pair = 2 academic hours)
        $classesPerWeek = ESubjectSchedule::where('_employee', $teacherId)
            ->where('active', true)
            ->distinct('_lesson_pair', '_group', '_subject')
            ->count();

        return $classesPerWeek * 2; // 2 academic hours per class
    }

    /**
     * Helper: Get day name in Uzbek
     */
    private function getDayName(int $day): string
    {
        $days = [
            1 => 'Dushanba',
            2 => 'Seshanba',
            3 => 'Chorshanba',
            4 => 'Payshanba',
            5 => 'Juma',
            6 => 'Shanba',
            7 => 'Yakshanba',
        ];

        return $days[$day] ?? '';
    }
}
