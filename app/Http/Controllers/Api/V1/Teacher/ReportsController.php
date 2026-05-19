<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\ReportsService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReportsService $reportsService)
    {
    }

    public function overview(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => 'nullable|integer|exists:e_subject,id',
        ]);

        $teacher = $request->user();
        $teacherId = $teacher->employee->id ?? null;

        if (!$teacherId) {
            return $this->errorResponse('Teacher profile not found', 404);
        }

        $subjectId = $request->query('subject_id') !== null
            ? (int) $request->query('subject_id')
            : null;

        $data = $this->reportsService->getOverviewReport($teacherId, $subjectId);

        return $this->successResponse($data, 'Hisobot ma\'lumotlari');
    }
}
