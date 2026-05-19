<?php

namespace App\Http\Controllers\Api\V1\Teacher;

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
     * POST /api/v1/teacher/qr-attendance/start
     * Body: { schedule_id }
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id' => 'required|integer',
        ]);

        $data = $this->qr->start($this->teacherId($request), (int) $validated['schedule_id']);

        return $this->successResponse($data);
    }

    /**
     * GET /api/v1/teacher/qr-attendance/poll/{token}
     */
    public function poll(Request $request, string $token): JsonResponse
    {
        return $this->successResponse(
            $this->qr->poll($this->teacherId($request), $token)
        );
    }

    /**
     * POST /api/v1/teacher/qr-attendance/save
     * Body: { schedule_id, topic_id, load?, absences?: [{student_id, hours}] }
     */
    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'schedule_id'           => 'required|integer',
            'topic_id'              => 'required|integer',
            'load'                  => 'nullable|integer|min:1|max:6',
            'absences'              => 'nullable|array',
            'absences.*.student_id' => 'required|integer',
            'absences.*.hours'      => 'required|integer|min:1|max:6',
        ]);

        $absences = [];
        foreach ($validated['absences'] ?? [] as $row) {
            $absences[(int) $row['student_id']] = (int) $row['hours'];
        }

        $data = $this->qr->save(
            $this->teacherId($request),
            (int) $validated['schedule_id'],
            (int) $validated['topic_id'],
            $validated['load'] ?? null,
            $absences
        );

        return $this->successResponse($data, 'Davomat saqlandi');
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
