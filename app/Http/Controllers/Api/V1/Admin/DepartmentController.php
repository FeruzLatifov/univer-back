<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DepartmentResource;
use App\Models\EDepartment;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Department Management Controller (Admin Panel)
 *
 * API Version: 1.0
 * Purpose: Fakultet va bo'limlarni boshqarish (CRUD)
 */
class DepartmentController extends Controller
{
    /**
     * Get departments list with filters
     *
     * @route GET /api/v1/admin/departments
     */
    public function index(Request $request)
    {
        // Generate cache key based on request params
        $cacheKey = CacheService::key(
            'departments:list',
            $request->query()
        );

        $result = CacheService::remember($cacheKey, function () use ($request) {
            $departments = QueryBuilder::for(EDepartment::class)
                ->allowedFilters([
                    AllowedFilter::exact('id'),
                    AllowedFilter::partial('name'),
                    AllowedFilter::partial('code'),
                    AllowedFilter::exact('_structure_type'),
                    AllowedFilter::exact('_parent'),
                    AllowedFilter::exact('active'),
                ])
                ->allowedIncludes(['parent', 'children', 'specialties', 'groups'])
                ->allowedSorts(['id', 'name', 'code', 'created_at'])
                ->paginate($request->input('per_page', 20));

            return [
                'data' => DepartmentResource::collection($departments->items()),
                'meta' => [
                    'current_page' => $departments->currentPage(),
                    'last_page' => $departments->lastPage(),
                    'per_page' => $departments->perPage(),
                    'total' => $departments->total(),
                ],
            ];
        }, 'departments');

        return response()->json([
            'success' => true,
            'data' => $result['data'],
            'meta' => $result['meta'],
        ]);
    }

    /**
     * Get single department
     *
     * @route GET /api/v1/admin/departments/{id}
     */
    public function show($id)
    {
        $department = QueryBuilder::for(EDepartment::class)
            ->allowedIncludes(['parent', 'children', 'specialties', 'groups'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new DepartmentResource($department),
        ]);
    }

    /**
     * Create new department
     *
     * @route POST /api/v1/admin/departments
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:500',
            'code' => 'required|string|max:50|unique:e_department,code',
            '_structure_type' => 'required|string|max:50',
            '_parent' => 'nullable|integer|exists:e_department,id',
            'active' => 'sometimes|boolean',
        ]);

        $department = EDepartment::create($validated);
        $department->load('parent');

        // ✅ Cache automatically invalidated by DepartmentObserver

        return response()->json([
            'success' => true,
            'data' => new DepartmentResource($department),
            'message' => 'Fakultet/Bo\'lim muvaffaqiyatli yaratildi',
        ], 201);
    }

    /**
     * Update department
     *
     * @route PUT /api/v1/admin/departments/{id}
     */
    public function update(Request $request, $id)
    {
        $department = EDepartment::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:500',
            'code' => 'sometimes|string|max:50|unique:e_department,code,' . $id,
            '_structure_type' => 'sometimes|string|max:50',
            '_parent' => 'nullable|integer|exists:e_department,id',
            'active' => 'sometimes|boolean',
        ]);

        $department->update($validated);
        $department->load('parent');

        // ✅ Cache automatically invalidated by DepartmentObserver

        return response()->json([
            'success' => true,
            'data' => new DepartmentResource($department),
            'message' => 'Fakultet/Bo\'lim muvaffaqiyatli yangilandi',
        ]);
    }

    /**
     * Delete department (soft delete)
     *
     * @route DELETE /api/v1/admin/departments/{id}
     */
    public function destroy($id)
    {
        $department = EDepartment::findOrFail($id);
        $department->update(['active' => false]);

        // ✅ Cache automatically invalidated by DepartmentObserver

        return response()->json([
            'success' => true,
            'message' => 'Fakultet/Bo\'lim o\'chirildi',
        ]);
    }
}
