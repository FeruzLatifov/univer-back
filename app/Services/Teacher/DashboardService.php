<?php

namespace App\Services\Teacher;

use App\Models\ESubjectSchedule;
use App\Models\EStudent;
use App\Models\EAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Teacher Dashboard Service
 *
 * BUSINESS LOGIC LAYER
 * ======================
 *
 * Modular Monolith Architecture - Teacher Module
 * Contains all business logic for teacher dashboard
 *
 * Controller → Service → Repository → Model
 *
 * @package App\Services\Teacher
 */
class DashboardService
{
    /**
     * Get complete dashboard data for teacher
     *
     * @param int $teacherId Teacher employee ID
     * @return array Dashboard data
     */
    public function getDashboardData(int $teacherId): array
    {
        $today = Carbon::today();
        $currentWeekDay = $today->dayOfWeek === 0 ? 7 : $today->dayOfWeek;

        return [
            'summary' => $this->getSummaryStats($teacherId, $today),
            'pending_attendance_classes' => $this->getPendingAttendanceClasses($teacherId, $today),
            'today_schedule' => $this->getTodaySchedule($teacherId, $today, $currentWeekDay),
            'quick_stats' => $this->getQuickStats($teacherId),
        ];
    }

    /**
     * Get summary statistics
     *
     * @param int $teacherId
     * @param Carbon $today
     * @return array
     */
    public function getSummaryStats(int $teacherId, Carbon $today): array
    {
        // Today's classes count
        $todayClassesCount = ESubjectSchedule::where('_employee', $teacherId)
            ->where('lesson_date', $today->toDateString())
            ->where('active', true)
            ->count();

        // Total students
        $totalStudents = $this->getTotalStudentsCount($teacherId);

        // Unique subjects
        $totalSubjects = ESubjectSchedule::where('_employee', $teacherId)
            ->where('active', true)
            ->distinct('_subject')
            ->count('_subject');

        // Unique groups
        $totalGroups = ESubjectSchedule::where('_employee', $teacherId)
            ->where('active', true)
            ->distinct('_group')
            ->count('_group');

        // Pending assignments (if table exists)
        $pendingAssignments = $this->getPendingAssignmentsCount($teacherId);

        // Pending attendance
        $pendingAttendance = $this->getPendingAttendanceCount($teacherId, $today);

        // Weekly classes
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();
        $weeklyClasses = ESubjectSchedule::where('_employee', $teacherId)
            ->whereBetween('lesson_date', [$weekStart, $weekEnd])
            ->where('active', true)
            ->count();

        return [
            'today_classes' => $todayClassesCount,
            'total_students' => $totalStudents,
            'total_subjects' => $totalSubjects,
            'total_groups' => $totalGroups,
            'pending_assignments' => $pendingAssignments,
            'pending_attendance' => $pendingAttendance,
            'weekly_classes' => $weeklyClasses,
        ];
    }

    /**
     * Get total students count for teacher
     *
     * @param int $teacherId
     * @return int
     */
    private function getTotalStudentsCount(int $teacherId): int
    {
        try {
            return EStudent::select('e_student.*')
                ->join('e_student_meta', 'e_student.id', '=', 'e_student_meta._student')
                ->join('e_subject_schedule', 'e_student_meta._group', '=', 'e_subject_schedule._group')
                ->where('e_subject_schedule._employee', $teacherId)
                ->where('e_student.active', true)
                ->distinct('e_student.id')
                ->count();
        } catch (\Exception $e) {
            Log::warning('[DashboardService] Failed to get students count', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get pending assignments count
     *
     * @param int $teacherId
     * @return int
     */
    private function getPendingAssignmentsCount(int $teacherId): int
    {
        try {
            return DB::table('e_assignment_submission')
                ->join('e_assignment', 'e_assignment_submission._assignment', '=', 'e_assignment.id')
                ->join('e_subject_schedule', 'e_assignment._subject_schedule', '=', 'e_subject_schedule.id')
                ->where('e_subject_schedule._employee', $teacherId)
                ->where('e_assignment_submission.status', 'submitted')
                ->whereNull('e_assignment_submission.grade')
                ->count();
        } catch (\Exception $e) {
            // Table might not exist, return 0
            return 0;
        }
    }

    /**
     * Get pending attendance count
     *
     * @param int $teacherId
     * @param Carbon $today
     * @return int
     */
    private function getPendingAttendanceCount(int $teacherId, Carbon $today): int
    {
        return ESubjectSchedule::where('_employee', $teacherId)
            ->where('lesson_date', '<', $today)
            ->where('lesson_date', '>=', $today->copy()->subDays(30)) // Last 30 days
            ->where('active', true)
            ->whereDoesntHave('attendanceControl')
            ->count();
    }

    /**
     * Get pending attendance classes list (last 5)
     *
     * @param int $teacherId
     * @param Carbon $today
     * @return array
     */
    public function getPendingAttendanceClasses(int $teacherId, Carbon $today): array
    {
        $classes = ESubjectSchedule::where('_employee', $teacherId)
            ->where('lesson_date', '<', $today)
            ->where('lesson_date', '>=', $today->copy()->subDays(30))
            ->where('active', true)
            ->whereDoesntHave('attendanceControl')
            ->with(['subject', 'group'])
            ->orderBy('lesson_date', 'desc')
            ->limit(5)
            ->get();

        return $classes->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'schedule_id' => $schedule->id,
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
                'lesson_pair' => $schedule->_lesson_pair,
            ];
        })->toArray();
    }

    /**
     * Get today's schedule
     *
     * @param int $teacherId
     * @param Carbon $today
     * @param int $currentWeekDay
     * @return array
     */
    public function getTodaySchedule(int $teacherId, Carbon $today, int $currentWeekDay): array
    {
        $schedule = ESubjectSchedule::where('_employee', $teacherId)
            ->where('lesson_date', $today->toDateString())
            ->where('active', true)
            ->with(['subject', 'group', 'lessonPair'])
            ->orderBy('_lesson_pair')
            ->get();

        $classes = $schedule->map(function ($schedule) {
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
        })->toArray();

        return [
            'date' => $today->toDateString(),
            'day_name' => $this->getDayName($currentWeekDay),
            'classes' => $classes,
        ];
    }

    /**
     * Get quick stats
     *
     * @param int $teacherId
     * @return array
     */
    public function getQuickStats(int $teacherId): array
    {
        return [
            'attendance_rate' => $this->getAttendanceRate($teacherId),
            'upcoming_exams' => $this->getUpcomingExamsCount($teacherId),
        ];
    }

    /**
     * Get attendance rate for teacher
     *
     * @param int $teacherId
     * @return float
     */
    private function getAttendanceRate(int $teacherId): float
    {
        try {
            $totalClasses = ESubjectSchedule::where('_employee', $teacherId)
                ->where('lesson_date', '<=', Carbon::today())
                ->where('lesson_date', '>=', Carbon::today()->subDays(30))
                ->where('active', true)
                ->count();

            if ($totalClasses === 0) {
                return 0.0;
            }

            $classesWithAttendance = ESubjectSchedule::where('_employee', $teacherId)
                ->where('lesson_date', '<=', Carbon::today())
                ->where('lesson_date', '>=', Carbon::today()->subDays(30))
                ->where('active', true)
                ->has('attendanceControl')
                ->count();

            return round(($classesWithAttendance / $totalClasses) * 100, 1);
        } catch (\Exception $e) {
            Log::warning('[DashboardService] Failed to calculate attendance rate', [
                'teacher_id' => $teacherId,
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    /**
     * Get upcoming exams count
     *
     * @param int $teacherId
     * @return int
     */
    private function getUpcomingExamsCount(int $teacherId): int
    {
        try {
            return DB::table('e_subject_schedule')
                ->where('_employee', $teacherId)
                ->where('_training_type', 11) // Exam type
                ->where('lesson_date', '>=', Carbon::today())
                ->where('lesson_date', '<=', Carbon::today()->addDays(14))
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent activities
     *
     * @param int $teacherId
     * @param int $limit
     * @return array
     */
    public function getRecentActivities(int $teacherId, int $limit = 10): array
    {
        $activities = EAttendance::whereHas('subjectSchedule', function($q) use ($teacherId) {
                $q->where('_employee', $teacherId);
            })
            ->with(['student', 'subjectSchedule.subject'])
            ->orderBy('lesson_date', 'desc')
            ->limit($limit)
            ->get();

        return $activities->map(function ($attendance) {
            $studentName = optional($attendance->student)->full_name ?? 'N/A';
            $subjectName = optional($attendance->subjectSchedule->subject)->name ?? 'N/A';

            return [
                'type' => 'attendance',
                'date' => $attendance->lesson_date,
                'description' => "Davomat: {$studentName} - {$subjectName}",
                'student' => optional($attendance->student)->full_name,
                'subject' => $subjectName,
            ];
        })->sortByDesc('date')->take($limit)->values()->toArray();
    }

    /**
     * Get day name in Uzbek
     *
     * @param int $dayNumber 1=Monday, 7=Sunday
     * @return string
     */
    private function getDayName(int $dayNumber): string
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

        return $days[$dayNumber] ?? 'Unknown';
    }
}
