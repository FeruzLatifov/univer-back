<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\CalendarPlanService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarPlanController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CalendarPlanService $plan)
    {
    }

    public function show(Request $request, int $subjectId): JsonResponse
    {
        $request->validate([
            'group_id' => 'nullable|integer',
        ]);

        $groupId = $request->query('group_id') !== null
            ? (int) $request->query('group_id')
            : null;

        $data = $this->plan->listForSubject($this->teacherId($request), $subjectId, $groupId);

        return $this->successResponse($data);
    }

    public function update(Request $request, int $subjectId): JsonResponse
    {
        $validated = $request->validate([
            'group_id'              => 'nullable|integer',
            'items'                 => 'required|array',
            'items.*.topic_id'      => 'required|integer',
            'items.*.planned_date'  => 'nullable|date_format:Y-m-d',
            'items.*.actual_date'   => 'nullable|date_format:Y-m-d',
            'items.*.hours'         => 'nullable|integer|min:1|max:200',
            'items.*.notes'         => 'nullable|string|max:1000',
        ]);

        $groupId = $validated['group_id'] ?? null;

        $count = $this->plan->upsert(
            $this->teacherId($request),
            $subjectId,
            $groupId !== null ? (int) $groupId : null,
            $validated['items']
        );

        $data = $this->plan->listForSubject(
            $this->teacherId($request),
            $subjectId,
            $groupId !== null ? (int) $groupId : null
        );

        return $this->successResponse($data, "{$count} ta mavzu yangilandi");
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
