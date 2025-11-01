<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Group;
use Illuminate\Support\Collection;

/**
 * StudentExportService
 *
 * Export student data to PDF and Excel
 */
class StudentExportService extends ExportService
{
    /**
     * Export students list to PDF
     */
    public function exportStudentListPDF(array $filters = [])
    {
        $students = $this->getStudents($filters);

        $data = [
            'title' => 'Talabalar ro\'yxati',
            'date' => $this->formatDate(now()),
            'students' => $students,
            'filters' => $filters,
            'total' => $students->count(),
        ];

        $filename = 'students_' . $this->getTimestamp() . '.pdf';

        return $this->downloadPDF('exports.students.list', $data, $filename);
    }

    /**
     * Export students list to Excel
     */
    public function exportStudentListExcel(array $filters = [])
    {
        $students = $this->getStudents($filters);

        $headers = [
            'ID',
            'Ism',
            'Familiya',
            'Guruh',
            'Kurs',
            'Telefon',
            'Email',
            'Holati',
            'Ro\'yxatga olingan',
        ];

        $data = $students->map(function($student) {
            return [
                $student->id,
                $student->firstname,
                $student->lastname,
                $student->group->name ?? '-',
                $student->group->course ?? '-',
                $student->phone ?? '-',
                $student->email ?? '-',
                $this->getStatusLabel($student->status),
                $this->formatDate($student->created_at),
            ];
        })->toArray();

        $filename = 'students_' . $this->getTimestamp() . '.csv';

        return $this->generateExcel($data, $headers, $filename);
    }

    /**
     * Export student attendance to PDF
     */
    public function exportStudentAttendancePDF(int $studentId, array $filters = [])
    {
        $student = Student::with(['group', 'attendances' => function($query) use ($filters) {
            if (isset($filters['date_from'])) {
                $query->where('date', '>=', $filters['date_from']);
            }
            if (isset($filters['date_to'])) {
                $query->where('date', '<=', $filters['date_to']);
            }
        }])->findOrFail($studentId);

        $attendances = $student->attendances;

        $stats = [
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'excused' => $attendances->where('status', 'excused')->count(),
            'total' => $attendances->count(),
        ];

        $data = [
            'title' => 'Davomat hisoboti',
            'student' => $student,
            'attendances' => $attendances,
            'stats' => $stats,
            'date' => $this->formatDate(now()),
            'filters' => $filters,
        ];

        $filename = 'attendance_' . $student->id . '_' . $this->getTimestamp() . '.pdf';

        return $this->downloadPDF('exports.students.attendance', $data, $filename);
    }

    /**
     * Export student grades to PDF
     */
    public function exportStudentGradesPDF(int $studentId, array $filters = [])
    {
        $student = Student::with(['group', 'grades' => function($query) use ($filters) {
            $query->with(['subject', 'teacher']);

            if (isset($filters['semester'])) {
                $query->where('semester', $filters['semester']);
            }
        }])->findOrFail($studentId);

        $grades = $student->grades;

        $stats = [
            'average' => $grades->avg('score'),
            'total_subjects' => $grades->unique('subject_id')->count(),
            'passed' => $grades->where('score', '>=', 60)->count(),
            'failed' => $grades->where('score', '<', 60)->count(),
        ];

        $data = [
            'title' => 'Baholar hisoboti',
            'student' => $student,
            'grades' => $grades,
            'stats' => $stats,
            'date' => $this->formatDate(now()),
            'filters' => $filters,
        ];

        $filename = 'grades_' . $student->id . '_' . $this->getTimestamp() . '.pdf';

        return $this->downloadPDF('exports.students.grades', $data, $filename);
    }

    /**
     * Export group students to PDF
     */
    public function exportGroupStudentsPDF(int $groupId)
    {
        $group = Group::with(['students', 'specialty', 'department'])->findOrFail($groupId);

        $data = [
            'title' => 'Guruh talabalari ro\'yxati',
            'group' => $group,
            'students' => $group->students,
            'date' => $this->formatDate(now()),
            'total' => $group->students->count(),
        ];

        $filename = 'group_' . $group->id . '_students_' . $this->getTimestamp() . '.pdf';

        return $this->downloadPDF('exports.groups.students', $data, $filename);
    }

    /**
     * Export group students to Excel
     */
    public function exportGroupStudentsExcel(int $groupId)
    {
        $group = Group::with(['students'])->findOrFail($groupId);

        $headers = [
            'ID',
            'Ism',
            'Familiya',
            'Telefon',
            'Email',
            'Jinsi',
            'Tug\'ilgan sana',
            'Holati',
        ];

        $data = $group->students->map(function($student) {
            return [
                $student->id,
                $student->firstname,
                $student->lastname,
                $student->phone ?? '-',
                $student->email ?? '-',
                $student->gender === 'male' ? 'Erkak' : 'Ayol',
                $this->formatDate($student->birth_date),
                $this->getStatusLabel($student->status),
            ];
        })->toArray();

        $filename = 'group_' . $group->id . '_students_' . $this->getTimestamp() . '.csv';

        return $this->generateExcel($data, $headers, $filename);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get students with filters
     */
    private function getStudents(array $filters = []): Collection
    {
        $query = Student::with(['group', 'specialty']);

        if (isset($filters['group_id'])) {
            $query->where('group_id', $filters['group_id']);
        }

        if (isset($filters['specialty_id'])) {
            $query->where('specialty_id', $filters['specialty_id']);
        }

        if (isset($filters['course'])) {
            $query->whereHas('group', function($q) use ($filters) {
                $q->where('course', $filters['course']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('firstname', 'ILIKE', "%{$filters['search']}%")
                  ->orWhere('lastname', 'ILIKE', "%{$filters['search']}%");
            });
        }

        return $query->get();
    }

    /**
     * Get status label
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'active' => 'Faol',
            'academic_leave' => 'Akademik ta\'til',
            'expelled' => 'Chetlatilgan',
            'graduated' => 'Bitirgan',
            'transferred' => 'Ko\'chirilgan',
        ];

        return $labels[$status] ?? $status;
    }
}
