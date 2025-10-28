<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EStudent;
use App\Models\EAssignment;
use App\Models\ESubjectTest;
use App\Models\EAttendance;
use App\Models\EGrade;
use App\Models\ECurriculumSubject;

class DashboardController extends Controller
{
    /**
     * Get student dashboard data
     */
    public function index(Request $request)
    {
        $student = $request->user();

        // Get active subjects (current semester)
        $activeSubjects = ECurriculumSubject::where('curriculum_id', $student->_curriculum)
            ->where('semester', $student->_semestr ?? 1)
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

        // Upcoming assignments (next 7 days)
        $upcomingAssignments = EAssignment::whereHas('subject.curriculumSubjects', function ($query) use ($student) {
                $query->where('curriculum_id', $student->_curriculum);
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

        // Available tests
        $availableTests = ESubjectTest::whereHas('subject.curriculumSubjects', function ($query) use ($student) {
                $query->where('curriculum_id', $student->_curriculum);
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

        // Attendance statistics (current month)
        $attendanceStats = EAttendance::where('_student', $student->id)
            ->whereMonth('_date', now()->month)
            ->whereYear('_date', now()->year)
            ->select('_attendance_type', DB::raw('count(*) as count'))
            ->groupBy('_attendance_type')
            ->get()
            ->pluck('count', '_attendance_type')
            ->toArray();

        $totalClasses = array_sum($attendanceStats);
        $presentCount = ($attendanceStats['present'] ?? 0) + ($attendanceStats['late'] ?? 0);
        $attendanceRate = $totalClasses > 0 ? round(($presentCount / $totalClasses) * 100, 1) : 0;

        // Grade statistics
        $grades = EGrade::where('_student', $student->id)
            ->where('_semester', $student->_semestr ?? 1)
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

        return response()->json([
            'success' => true,
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'group' => $student->group->name ?? 'N/A',
                    'course' => $student->_level ?? 1,
                    'semester' => $student->_semestr ?? 1,
                ],
                'subjects' => [
                    'active' => $activeSubjects,
                    'total' => $activeSubjects->count(),
                ],
                'assignments' => [
                    'upcoming' => $upcomingAssignments,
                    'total' => $upcomingAssignments->count(),
                ],
                'tests' => [
                    'available' => $availableTests,
                    'total' => $availableTests->count(),
                ],
                'attendance' => [
                    'stats' => [
                        'present' => $attendanceStats['present'] ?? 0,
                        'absent' => $attendanceStats['absent'] ?? 0,
                        'late' => $attendanceStats['late'] ?? 0,
                        'excused' => $attendanceStats['excused'] ?? 0,
                        'total' => $totalClasses,
                        'rate' => $attendanceRate,
                    ],
                ],
                'grades' => [
                    'recent' => $recentGrades,
                    'statistics' => [
                        'total' => $totalGrades,
                        'average' => round($avgGrade, 1),
                        'gpa' => $gpa,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get grade letter from points
     */
    private function getGradeLetter($points)
    {
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
