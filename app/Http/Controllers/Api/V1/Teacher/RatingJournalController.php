<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\RatingJournalService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RatingJournalController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly RatingJournalService $ratings)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'education_year' => 'required|string|max:64',
            'group_id'       => 'nullable|integer',
            'semester'       => 'nullable|string|max:64',
            'subject_id'     => 'nullable|integer',
            'page'           => 'nullable|integer|min:1',
            'per_page'       => 'nullable|integer|min:1|max:100',
        ]);

        $data = $this->ratings->list($this->teacherId($request), $validated);

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
