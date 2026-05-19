<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Reference\ReferenceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lookup endpoints for cascading dropdowns / classifiers.
 * Yii2 parity: api/modules/ver1/tutor/controllers/ReferenceController.
 */
class ReferenceController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReferenceService $reference)
    {
    }

    public function countries(): JsonResponse
    {
        return $this->successResponse(['items' => $this->reference->countries()]);
    }

    public function provinces(): JsonResponse
    {
        return $this->successResponse(['items' => $this->reference->provinces()]);
    }

    public function districts(Request $request): JsonResponse
    {
        $validated = $request->validate(['province' => 'required|string|max:32']);

        return $this->successResponse(['items' => $this->reference->districts($validated['province'])]);
    }

    public function terrains(Request $request): JsonResponse
    {
        $validated = $request->validate(['district' => 'required|string|max:32']);

        return $this->successResponse(['items' => $this->reference->terrains($validated['district'])]);
    }

    public function studentLivingStatuses(): JsonResponse
    {
        return $this->successResponse(['items' => $this->reference->studentLivingStatuses()]);
    }

    public function accommodations(): JsonResponse
    {
        return $this->successResponse(['items' => $this->reference->accommodations()]);
    }

    public function studentRoommateTypes(): JsonResponse
    {
        return $this->successResponse(['items' => $this->reference->studentRoommateTypes()]);
    }

    public function studentStatuses(): JsonResponse
    {
        return $this->successResponse(['items' => $this->reference->studentStatuses()]);
    }

    public function educationYears(): JsonResponse
    {
        return $this->successResponse(['items' => $this->reference->educationYears()]);
    }

    public function specialties(): JsonResponse
    {
        return $this->successResponse(['items' => $this->reference->specialties()]);
    }

    public function subjects(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group'         => 'nullable|integer',
            'year_semester' => 'nullable|string|max:32',
        ]);

        return $this->successResponse([
            'items' => $this->reference->subjects(
                $validated['group'] ?? null,
                $validated['year_semester'] ?? null
            ),
        ]);
    }

    public function semesters(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'group'      => 'nullable|integer',
            'curriculum' => 'nullable|integer',
        ]);

        return $this->successResponse([
            'items' => $this->reference->semesters(
                $validated['group'] ?? null,
                $validated['curriculum'] ?? null
            ),
        ]);
    }
}
