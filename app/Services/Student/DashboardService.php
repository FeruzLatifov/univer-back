<?php

namespace App\Services\Student;

use App\Models\EStudent;
use App\Models\EAssignment;
use App\Models\ESubjectTest;
use App\Models\EAttendance;
use App\Models\EGrade;
use App\Models\ECurriculumSubject;
use Illuminate\Support\Facades\DB;

/**
 * Student Dashboard Service
 *
 * BUSINESS LOGIC LAYER
 * ======================
 *
 * Modular Monolith Architecture - Student Module
 * Contains all business logic for student dashboard
 *
 * Controller → Service → Repository → Model
 *
 * @package App\Services\Student
 */
class DashboardService
{
    /**
     * Get complete dashboard data for student
     *
     * @param EStudent $student
     * @return array
     */
    public function getDashboardData(EStudent $student): array
    {
        return [
            'student' => $this->getStudentInfo($student),
            'subjects' => $this->getActiveSubjects($student),
            'assignments' => $this->getUpcomingAssignments($student),
            'tests' => $this->getAvailableTests($student),
            'attendance' => $this->getAttendanceStats($student),
            'grades' => $this->getGradeStats($student),
        ];
    }

    /**
     * Get student basic information
     *
     * @param EStudent $student
     * @return array
     */
    private function getStudentInfo(EStudent $student): array
    {
        return [
            'id' => $student->id,
            'name' => $student->full_name,
            'group' => $student->meta->group->name ?? 'N/A',
            'course' => $student->meta->_level ?? 1,
            'semester' => $student->meta->_semester ?? 1,
        ];
    }

    /**
     * Get active subjects for current semester
     *
     * @param EStudent $student
     * @return array
     */
    private function getActiveSubjects(EStudent $student): array
    {
        $activeSubjects = ECurriculumSubject::where('curriculum_id', $student->meta->_curriculum ?? null)
            ->where('semester', $student->meta->_semester ?? 1)
            ->with(['subject'])
            ->get()
            ->map(function ($cs) {
                return [
                    'id' => $cs->_subject,
                    'name' => $cs->subject->name ?? 'N/A',
                    'code' => $cs->subject->code ?? '',
                    'credit' => $cs->credit,
                    'total_acload' => $cs->total_acload,
                ];
            });

        return [
            'active' => $activeSubjects->toArray(),
            'total' => $activeSubjects->count(),
        ];
    }

    /**
     * Get upcoming assignments (next 7 days)
     *
     * @param EStudent $student
     * @return array
     */
    private function getUpcomingAssignments(EStudent $student): array
    {
        $upcomingAssignments = EAssignment::whereHas('subject.curriculumSubjects', function ($query) use ($student) {
                $query->where('curriculum_id', $student->meta->_curriculum ?? null);
            })
            ->where('status', 'published')
            ->where('deadline', '>=', now())
            ->where('deadline', '<=', now()->addDays(7))
            ->with(['subject'])
            ->orderBy('deadline', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($assignment) use ($student) {
                // Check if student has submitted
                $submission = $assignment->submissions()
                    ->where('_student', $student->id)
                    ->first();

                return [
                    'id' => $assignment->id,
                    'title' => $assignment->title,
                    'subject' => $assignment->subject->name ?? 'N/A',
                    'deadline' => $assignment->deadline,
                    'status' => $submission ? 'submitted' : 'pending',
                    'grade' => $submission->grade ?? null,
                ];
            });

        return [
            'upcoming' => $upcomingAssignments->toArray(),
            'total' => $upcomingAssignments->count(),
        ];
    }

    /**
     * Get available tests
     *
     * @param EStudent $student
     * @return array
     */
    private function getAvailableTests(EStudent $student): array
    {
        $availableTests = ESubjectTest::whereHas('subject.curriculumSubjects', function ($query) use ($student) {
                $query->where('curriculum_id', $student->meta->_curriculum ?? null);
            })
            ->where('status', 'published')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->with(['subject'])
            ->limit(5)
            ->get()
            ->map(function ($test) use ($student) {
                // Check attempts
                $attemptsCount = $test->attempts()
                    ->where('_student', $student->id)
                    ->count();

                $bestAttempt = $test->attempts()
                    ->where('_student', $student->id)
                    ->orderBy('score', 'desc')
                    ->first();

                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'subject' => $test->subject->name ?? 'N/A',
                    'duration' => $test->duration,
                    'end_time' => $test->end_time,
                    'attempts_count' => $attemptsCount,
                    'max_attempts' => $test->max_attempts,
                    'best_score' => $bestAttempt->score ?? null,
                ];
            });

        return [
            'available' => $availableTests->toArray(),
            'total' => $availableTests->count(),
        ];
    }

    /**
     * Get attendance statistics (current month)
     *
     * @param EStudent $student
     * @return array
     */
    private function getAttendanceStats(EStudent $student): array
    {
        $attendanceStats = EAttendance::where('_student', $student->id)
            ->whereMonth('lesson_date', now()->month)
            ->whereYear('lesson_date', now()->year)
            ->select('_attendance_type', DB::raw('count(*) as count'))
            ->groupBy('_attendance_type')
            ->get()
            ->pluck('count', '_attendance_type')
            ->toArray();

        $totalClasses = array_sum($attendanceStats);
        $presentCount = ($attendanceStats[EAttendance::STATUS_PRESENT] ?? 0) +
                       ($attendanceStats[EAttendance::STATUS_LATE] ?? 0);
        $attendanceRate = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 1) : 0;

        return [
            'stats' => [
                'present' => $attendanceStats[EAttendance::STATUS_PRESENT] ?? 0,
                'absent' => $attendanceStats[EAttendance::STATUS_ABSENT] ?? 0,
                'late' => $attendanceStats[EAttendance::STATUS_LATE] ?? 0,
                'excused' => $attendanceStats[EAttendance::STATUS_EXCUSED] ?? 0,
                'total' => $totalClasses,
                'rate' => $attendanceRate,
            ],
        ];
    }

    /**
     * Get grade statistics
     *
     * @param EStudent $student
     * @return array
     */
    private function getGradeStats(EStudent $student): array
    {
        $grades = EGrade::where('_student', $student->id)
            ->where('_semester', $student->meta->_semester ?? 1)
            ->with('subject')
            ->get();

        $totalGrades = $grades->count();
        $avgGrade = $grades->avg('total_point');
        $gpa = $totalGrades > 0 ? round($avgGrade / 20, 2) : 0; // Assuming 100 point system

        // Recent grades
        $recentGrades = $grades->sortByDesc('updated_at')
            ->take(5)
            ->map(function ($grade) {
                return [
                    'id' => $grade->id,
                    'subject' => $grade->subject->name ?? 'N/A',
                    'midterm' => $grade->midterm_point,
                    'final' => $grade->final_point,
                    'total' => $grade->total_point,
                    'grade_letter' => $this->getGradeLetter($grade->total_point),
                ];
            })
            ->values();

        return [
            'recent' => $recentGrades->toArray(),
            'statistics' => [
                'total' => $totalGrades,
                'average' => round($avgGrade, 1),
                'gpa' => $gpa,
            ],
        ];
    }

    /**
     * Convert points to grade letter
     *
     * @param float|null $points
     * @return string
     */
    private function getGradeLetter($points): string
    {
        if (!$points) return 'F';

        if ($points >= 90) return 'A';
        if ($points >= 85) return 'A-';
        if ($points >= 80) return 'B+';
        if ($points >= 75) return 'B';
        if ($points >= 70) return 'B-';
        if ($points >= 65) return 'C+';
        if ($points >= 60) return 'C';
        if ($points >= 55) return 'C-';
        if ($points >= 50) return 'D+';
        if ($points >= 45) return 'D';
        return 'F';
    }
}
