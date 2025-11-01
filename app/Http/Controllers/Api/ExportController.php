<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StudentExportService;
use App\Services\ReportExportService;
use Illuminate\Http\Request;

/**
 * ExportController
 *
 * Handles all export requests (PDF & Excel)
 */
class ExportController extends Controller
{
    protected StudentExportService $studentExportService;
    protected ReportExportService $reportExportService;

    public function __construct(
        StudentExportService $studentExportService,
        ReportExportService $reportExportService
    ) {
        $this->studentExportService = $studentExportService;
        $this->reportExportService = $reportExportService;
    }

    // ==================== STUDENTS ====================

    /**
     * Export students list
     * Format: pdf or excel
     */
    public function exportStudentsList(Request $request)
    {
        $format = $request->get('format', 'pdf');
        $filters = $request->only(['group_id', 'specialty_id', 'course', 'status', 'search']);

        if ($format === 'excel') {
            return $this->studentExportService->exportStudentListExcel($filters);
        }

        return $this->studentExportService->exportStudentListPDF($filters);
    }

    /**
     * Export student attendance
     */
    public function exportStudentAttendance(int $studentId, Request $request)
    {
        $filters = $request->only(['date_from', 'date_to']);

        return $this->studentExportService->exportStudentAttendancePDF($studentId, $filters);
    }

    /**
     * Export student grades
     */
    public function exportStudentGrades(int $studentId, Request $request)
    {
        $filters = $request->only(['semester']);

        return $this->studentExportService->exportStudentGradesPDF($studentId, $filters);
    }

    /**
     * Export group students
     */
    public function exportGroupStudents(int $groupId, Request $request)
    {
        $format = $request->get('format', 'pdf');

        if ($format === 'excel') {
            return $this->studentExportService->exportGroupStudentsExcel($groupId);
        }

        return $this->studentExportService->exportGroupStudentsPDF($groupId);
    }

    // ==================== REPORTS ====================

    /**
     * Export attendance summary report
     */
    public function exportAttendanceSummary(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'group_id']);

        return $this->reportExportService->exportAttendanceSummaryPDF($filters);
    }

    /**
     * Export grades summary report
     */
    public function exportGradesSummary(Request $request)
    {
        $filters = $request->only(['semester', 'subject_id', 'group_id']);

        return $this->reportExportService->exportGradesSummaryPDF($filters);
    }

    /**
     * Export teacher workload report
     */
    public function exportTeacherWorkload(int $teacherId)
    {
        return $this->reportExportService->exportTeacherWorkloadPDF($teacherId);
    }

    /**
     * Export students performance
     */
    public function exportStudentsPerformance(Request $request)
    {
        $filters = $request->only(['group_id', 'semester']);

        return $this->reportExportService->exportStudentsPerformanceExcel($filters);
    }

    /**
     * Export monthly statistics
     */
    public function exportMonthlyStats(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('m'));

        return $this->reportExportService->exportMonthlyStatsPDF($year, $month);
    }
}
