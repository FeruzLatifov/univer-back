<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\SocialActivityService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Social Activity (SAD) — read-only foundation.
 * Yii2 parity (subset): SocialActivityController actions
 *   directions, applications, application.
 *
 * Write/approve/calculate-system-scores will land in a follow-up phase.
 */
class SocialActivityController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SocialActivityService $social)
    {
    }

    public function directions(): JsonResponse
    {
        return $this->successResponse(['directions' => $this->social->directions()]);
    }

    public function applications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'nullable|integer',
            'status'     => 'nullable|integer',
            'group_id'   => 'nullable|integer',
        ]);

        return $this->successResponse(
            $this->social->applications($this->teacherId($request), $validated)
        );
    }

    public function application(Request $request, int $id): JsonResponse
    {
        return $this->successResponse(
            $this->social->application($this->teacherId($request), $id)
        );
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
