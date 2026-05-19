<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\TutorVisitService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorVisitController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TutorVisitService $visits)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'student_id' => 'nullable|integer',
            'search'     => 'nullable|string|max:255',
            'page'       => 'nullable|integer|min:1',
            'per_page'   => 'nullable|integer|min:1|max:100',
        ]);

        $paginator = $this->visits->listForTeacher($this->teacherId($request), [
            'student_id' => $request->query('student_id'),
            'search'     => $request->query('search'),
            'page'       => $request->query('page'),
            'per_page'   => $request->query('per_page'),
        ]);

        return $this->successResponse([
            'items' => $paginator->items(),
            '_meta' => [
                'currentPage' => $paginator->currentPage(),
                'perPage'     => $paginator->perPage(),
                'totalCount'  => $paginator->total(),
                'pageCount'   => $paginator->lastPage(),
            ],
        ]);
    }

    public function forStudent(Request $request, int $studentId): JsonResponse
    {
        $visits = $this->visits->listForStudent($this->teacherId($request), $studentId);

        return $this->successResponse([
            'student_id' => $studentId,
            'visits'     => $visits,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            '_student'               => 'required|integer|exists:e_student,id',
            '_student_living_status' => 'nullable|string|max:64',
            '_accommodation'         => 'nullable|string|max:64',
            '_current_province'      => 'nullable|string|max:64',
            '_current_district'      => 'nullable|string|max:64',
            '_current_terrain'       => 'nullable|string|max:64',
            'current_address'        => 'nullable|string|max:255',
            'geolocation'            => 'nullable|string|max:255',
            'roommate_count'         => 'nullable|integer|min:0|max:100',
            'comment'                => 'nullable|string|max:2000',
        ]);

        $studentId = (int) $validated['_student'];
        unset($validated['_student']);

        $visit = $this->visits->create($this->teacherId($request), $studentId, $validated);

        return $this->successResponse(
            $visit->toArray(),
            'Tashrif ma\'lumotlari muvaffaqiyatli saqlandi',
            201
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
