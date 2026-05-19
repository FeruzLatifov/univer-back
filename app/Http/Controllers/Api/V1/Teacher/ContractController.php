<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\ContractService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ContractService $contracts)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'group_id'       => 'nullable|integer',
            'education_year' => 'nullable|string|max:16',
            'status'         => 'nullable|string|max:32',
        ]);

        $data = $this->contracts->list($this->teacherId($request), $request->only([
            'group_id', 'education_year', 'status',
        ]));

        return $this->successResponse($data);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $data = $this->contracts->view($this->teacherId($request), $id);

        return $this->successResponse($data);
    }

    public function debtors(Request $request): JsonResponse
    {
        $request->validate([
            'group_id'       => 'nullable|integer',
            'education_year' => 'nullable|string|max:16',
        ]);

        $data = $this->contracts->debtors($this->teacherId($request), $request->only([
            'group_id', 'education_year',
        ]));

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
