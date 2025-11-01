<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SpecialtyResource;
use App\Models\ESpecialty;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Specialty Management Controller (Admin Panel)
 *
 * API Version: 1.0
 * Purpose: Mutaxassisliklarni boshqarish (CRUD)
 */
class SpecialtyController extends Controller
{
    /**
     * Get specialties list with filters
     *
     * @route GET /api/v1/admin/specialties
     */
    public function index(Request $request)
    {
        $specialties = QueryBuilder::for(ESpecialty::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('code'),
                AllowedFilter::partial('name'),
                AllowedFilter::exact('_department'),
                AllowedFilter::exact('_education_type'),
                AllowedFilter::exact('active'),
            ])
            ->allowedIncludes(['department', 'groups'])
            ->allowedSorts(['id', 'code', 'name', 'created_at'])
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => SpecialtyResource::collection($specialties->items()),
            'meta' => [
                'current_page' => $specialties->currentPage(),
                'last_page' => $specialties->lastPage(),
                'per_page' => $specialties->perPage(),
                'total' => $specialties->total(),
            ],
        ]);
    }

    /**
     * Get single specialty
     *
     * @route GET /api/v1/admin/specialties/{id}
     */
    public function show($id)
    {
        $specialty = QueryBuilder::for(ESpecialty::class)
            ->allowedIncludes(['department', 'groups'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new SpecialtyResource($specialty),
        ]);
    }

    /**
     * Create new specialty
     *
     * @route POST /api/v1/admin/specialties
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:e_specialty,code',
            'name' => 'required|string|max:500',
            '_department' => 'required|integer|exists:e_department,id',
            '_education_type' => 'required|string|max:50',
            'active' => 'sometimes|boolean',
        ]);

        $specialty = ESpecialty::create($validated);
        $specialty->load('department');

        return response()->json([
            'success' => true,
            'data' => new SpecialtyResource($specialty),
            'message' => 'Mutaxassislik muvaffaqiyatli yaratildi',
        ], 201);
    }

    /**
     * Update specialty
     *
     * @route PUT /api/v1/admin/specialties/{id}
     */
    public function update(Request $request, $id)
    {
        $specialty = ESpecialty::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:e_specialty,code,' . $id,
            'name' => 'sometimes|string|max:500',
            '_department' => 'sometimes|integer|exists:e_department,id',
            '_education_type' => 'sometimes|string|max:50',
            'active' => 'sometimes|boolean',
        ]);

        $specialty->update($validated);
        $specialty->load('department');

        return response()->json([
            'success' => true,
            'data' => new SpecialtyResource($specialty),
            'message' => 'Mutaxassislik muvaffaqiyatli yangilandi',
        ]);
    }

    /**
     * Delete specialty (soft delete)
     *
     * @route DELETE /api/v1/admin/specialties/{id}
     */
    public function destroy($id)
    {
        $specialty = ESpecialty::findOrFail($id);
        $specialty->update(['active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Mutaxassislik o\'chirildi',
        ]);
    }
}
