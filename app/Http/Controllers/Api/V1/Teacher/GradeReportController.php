<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\GradeReportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GradeReportController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly GradeReportService $reports)
    {
    }

    public function gpa(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group_id' => 'nullable|integer',
            'semester' => 'nullable|string|max:64',
        ]);

        return $this->successResponse($this->reports->gpa($this->teacherId($request), $validated));
    }

    public function debtors(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'education_year' => 'required|string|max:64',
            'group_id'       => 'nullable|integer',
            'semester'       => 'nullable|string|max:64',
            'curriculum'     => 'nullable|integer',
            'subject_id'     => 'nullable|integer',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:100',
        ]);

        return $this->successResponse($this->reports->debtors($this->teacherId($request), $validated));
    }

    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group_id' => 'nullable|integer',
            'semester' => 'nullable|string|max:64',
        ]);

        return $this->successResponse($this->reports->summary($this->teacherId($request), $validated));
    }

    public function student(Request $request, int $studentId): JsonResponse
    {
        $validated = $request->validate([
            'semester' => 'nullable|string|max:64',
        ]);

        $data = $this->reports->studentGrades(
            $this->teacherId($request),
            $studentId,
            $validated['semester'] ?? null
        );

        return $this->successResponse($data);
    }

    private function teacherId(Request $request): int
    {
        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            abort(404, 'Teacher profile not found');
        }

        return $teacherId;
    }
}
