<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\GroupService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly GroupService $groups)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'education_year' => 'nullable|string|max:16',
            'group_id'       => 'nullable|integer',
            'faculty_id'     => 'nullable|integer',
        ]);

        $teacherId = $this->teacherId($request);

        $data = $this->groups->list($teacherId, [
            'education_year' => $request->query('education_year'),
            'group_id'       => $request->query('group_id'),
            'faculty_id'     => $request->query('faculty_id'),
        ]);

        return $this->successResponse($data);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'education_year' => 'nullable|string|max:16',
        ]);

        $data = $this->groups->view($this->teacherId($request), $id, $request->query('education_year'));

        return $this->successResponse($data);
    }

    public function students(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'education_year' => 'nullable|string|max:16',
            'status'         => 'nullable|string|max:32',
        ]);

        $data = $this->groups->students(
            $this->teacherId($request),
            $id,
            $request->query('education_year'),
            $request->query('status')
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
