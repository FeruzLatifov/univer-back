<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Services\Teacher\QrAttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QrAttendanceController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly QrAttendanceService $qr)
    {
    }

    /**
     * POST /api/student/qr-attendance/check
     * Body: { token, code }
     */
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:128',
            'code'  => 'required|string|max:128',
        ]);

        $student = $request->user();
        $studentId = $student?->id;

        if (!$studentId) {
            abort(404, 'Student profile not found');
        }

        $data = $this->qr->checkIn((int) $studentId, $validated['token'], $validated['code']);

        return $this->successResponse($data, $data['message']);
    }
}
