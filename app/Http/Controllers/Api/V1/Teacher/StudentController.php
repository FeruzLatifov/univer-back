<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\StudentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly StudentService $students)
    {
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $data = $this->students->profile($this->teacherId($request), $id);

        return $this->successResponse($data);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'phone'                   => 'nullable|string|max:32',
            'email'                   => 'nullable|email|max:128',
            'home_address'            => 'nullable|string|max:500',
            'current_address'         => 'nullable|string|max:1024',
            '_country'                => 'nullable|string|max:64',
            '_province'               => 'nullable|string|max:64',
            '_district'               => 'nullable|string|max:64',
            '_terrain'                => 'nullable|string|max:64',
            '_current_province'       => 'nullable|string|max:64',
            '_current_district'       => 'nullable|string|max:64',
            '_current_terrain'        => 'nullable|string|max:64',
            '_student_living_status'  => 'nullable|string|max:64',
            '_student_roommate_type'  => 'nullable|string|max:64',
            'roommate_count'          => 'nullable|integer|min:0|max:100',
            'geo_location'            => 'nullable|string|max:1024',
            'parent_phone'            => 'nullable|string|max:32',
            'person_phone'            => 'nullable|string|max:32',
        ]);

        $this->students->update($this->teacherId($request), $id, $validated);

        $data = $this->students->profile($this->teacherId($request), $id);

        return $this->successResponse($data, 'Talaba ma\'lumotlari yangilandi');
    }

    public function history(Request $request, int $id): JsonResponse
    {
        $history = $this->students->history($this->teacherId($request), $id);

        return $this->successResponse([
            'student_id' => $id,
            'history'    => $history,
        ]);
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
