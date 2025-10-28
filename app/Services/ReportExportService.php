<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\Grade;
use Illuminate\Support\Facades\DB;

/**
 * ReportExportService
 *
 * Export various reports to PDF and Excel
 */
class ReportExportService extends ExportService
{
    /**
     * Export attendance summary report
     */
    public function exportAttendanceSummaryPDF(array $filters = [])
    {
        $query = Attendance::with(['student.group', 'subject']);

        if (isset($filters['date_from'])) {
            $query->where('date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('date', '<=', $filters['date_to']);
        }
        if (isset($filters['group_id'])) {
            $query->whereHas('student', fn($q) => $q->where('group_id', $filters['group_id']));
        }

        $attendances = $query->get();

        $summary = [
            'total' => $attendances->count(),
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
        ];

        $summary['attendance_rate'] = $summary['total'] > 0
            ? round(($summary['present'] / $summary['total']) * 100, 2)
            : 0;

        $data = [
            'title' => 'Davomat umumiy hisoboti',
            'date' => $this->formatDate(now()),
            'period' => [
                'from' => $filters['date_from'] ?? null,
                'to' => $filters['date_to'] ?? null,
            ],
            'summary' => $summary,
            'filters' => $filters,
        ];

        $filename = 'attendance_summary_' . $this->getTimestamp() . '.pdf';

        return $this->downloadPDF('exports.reports.attendance-summary', $data, $filename);
    }

    /**
     * Export grades summary report
     */
    public function exportGradesSummaryPDF(array $filters = [])
    {
        $query = Grade::with(['student.group', 'subject', 'teacher']);

        if (isset($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }
        if (isset($filters['subject_id'])) {
            $query->where('subject_id', $filters['subject_id']);
        }
        if (isset($filters['group_id'])) {
            $query->whereHas('student', fn($q) => $q->where('group_id', $filters['group_id']));
        }

        $grades = $query->get();

        $summary = [
            'total' => $grades->count(),
            'average' => round($grades->avg('score'), 2),
            'passed' => $grades->where('score', '>=', 60)->count(),
            'failed' => $grades->where('score', '<', 60)->count(),
            'excellence' => $grades->where('score', '>=', 90)->count(),
        ];

        $summary['pass_rate'] = $summary['total'] > 0
            ? round(($summary['passed'] / $summary['total']) * 100, 2)
            : 0;

        // Grade distribution
        $distribution = [
            'A' => $grades->where('score', '>=', 90)->count(),
            'B' => $grades->whereBetween('score', [80, 89])->count(),
            'C' => $grades->whereBetween('score', [70, 79])->count(),
            'D' => $grades->whereBetween('score', [60, 69])->count(),
            'F' => $grades->where('score', '<', 60)->count(),
        ];

        $data = [
            'title' => 'Baholar umumiy hisoboti',
            'date' => $this->formatDate(now()),
            'summary' => $summary,
            'distribution' => $distribution,
            'filters' => $filters,
        ];

        $filename = 'grades_summary_' . $this->getTimestamp() . '.pdf';

        return $this->downloadPDF('exports.reports.grades-summary', $data, $filename);
    }

    /**
     * Export teacher workload report
     */
    public function exportTeacherWorkloadPDF(int $teacherId)
    {
        $teacher = Teacher::with(['subjects', 'groups', 'schedules'])->findOrFail($teacherId);

        $totalHours = DB::table('schedules')
            ->where('teacher_id', $teacherId)
            ->sum('hours');

        $subjectsCount = $teacher->subjects->count();
        $groupsCount = $teacher->groups->count();

        $data = [
            'title' => 'O\'qituvchi ish yuki hisoboti',
            'date' => $this->formatDate(now()),
            'teacher' => $teacher,
            'total_hours' => $totalHours,
            'subjects_count' => $subjectsCount,
            'groups_count' => $groupsCount,
            'subjects' => $teacher->subjects,
            'groups' => $teacher->groups,
        ];

        $filename = 'teacher_workload_' . $teacherId . '_' . $this->getTimestamp() . '.pdf';

        return $this->downloadPDF('exports.reports.teacher-workload', $data, $filename);
    }

    /**
     * Export students performance report (Excel)
     */
    public function exportStudentsPerformanceExcel(array $filters = [])
    {
        $query = Student::with(['group', 'grades']);

        if (isset($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }
        if (isset($filters['semester'])) {
            $query->whereHas('grades', fn($q) => $q->where('semester', $filters['semester']));
        }

        $students = $query->get();

        $headers = [
            'ID',
            'Ism',
            'Familiya',
            'Guruh',
            'O\'rtacha baho',
            'GPA',
            'O\'tgan fanlar',
            'O\'ta olmagan fanlar',
            'Davomat %',
            'Holati',
        ];

        $data = $students->map(function($student) use ($filters) {
            $grades = $student->grades;
            if (isset($filters['semester'])) {
                $grades = $grades->where('semester', $filters['semester']);
            }

            $average = $grades->avg('score') ?? 0;
            $passed = $grades->where('score', '>=', 60)->count();
            $failed = $grades->where('score', '<', 60)->count();
            $gpa = $this->calculateGPA($average);

            return [
                $student->id,
                $student->firstname,
                $student->lastname,
                $student->group->name ?? '-',
                round($average, 2),
                $gpa,
                $passed,
                $failed,
                '-', // Attendance % (would need separate calculation)
                $this->getStatusLabel($student->status),
            ];
        })->toArray();

        $filename = 'students_performance_' . $this->getTimestamp() . '.csv';

        return $this->generateExcel($data, $headers, $filename);
    }

    /**
     * Export monthly statistics report
     */
    public function exportMonthlyStatsPDF(int $year, int $month)
    {
        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $stats = [
            'total_students' => Student::where('status', 'active')->count(),
            'total_teachers' => Teacher::where('status', 'active')->count(),
            'new_students' => Student::whereBetween('created_at', [$startDate, $endDate])->count(),
            'total_attendances' => Attendance::whereBetween('date', [$startDate, $endDate])->count(),
            'total_grades' => Grade::whereBetween('created_at', [$startDate, $endDate])->count(),
            'average_grade' => Grade::whereBetween('created_at', [$startDate, $endDate])->avg('score'),
        ];

        $data = [
            'title' => 'Oylik statistika hisoboti',
            'date' => $this->formatDate(now()),
            'period' => [
                'month' => $month,
                'year' => $year,
                'from' => $this->formatDate($startDate),
                'to' => $this->formatDate($endDate),
            ],
            'stats' => $stats,
        ];

        $filename = 'monthly_stats_' . $year . '_' . $month . '.pdf';

        return $this->downloadPDF('exports.reports.monthly-stats', $data, $filename);
    }

    // ==================== HELPER METHODS ====================

    private function calculateGPA(float $average): float
    {
        if ($average >= 90) return 4.0;
        if ($average >= 80) return 3.0;
        if ($average >= 70) return 2.0;
        if ($average >= 60) return 1.0;
        return 0.0;
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'active' => 'Faol',
            'academic_leave' => 'Akademik ta\'til',
            'expelled' => 'Chetlatilgan',
            'graduated' => 'Bitirgan',
        ];
        return $labels[$status] ?? $status;
    }
}
