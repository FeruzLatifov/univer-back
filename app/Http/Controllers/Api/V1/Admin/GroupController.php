<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreGroupRequest;
use App\Http\Requests\Api\V1\UpdateGroupRequest;
use App\Http\Resources\Api\V1\GroupResource;
use App\Models\EGroup;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Group Management Controller (Admin Panel)
 *
 * API Version: 1.0
 * Purpose: Guruhlarni boshqarish (CRUD)
 */
class GroupController extends Controller
{
    /**
     * Get groups list with filters
     *
     * @route GET /api/v1/admin/groups
     */
    public function index(Request $request)
    {
        $groups = QueryBuilder::for(EGroup::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('code'),
                AllowedFilter::exact('_department'),
                AllowedFilter::exact('_specialty'),
                AllowedFilter::exact('_education_type'),
                AllowedFilter::exact('_education_form'),
                AllowedFilter::exact('_education_year'),
                AllowedFilter::exact('_level'),
                AllowedFilter::exact('active'),
            ])
            ->allowedIncludes([
                'specialty',
                'department',
                'students',
            ])
            ->allowedSorts([
                'id',
                'name',
                'code',
                'created_at',
                'updated_at',
            ])
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => GroupResource::collection($groups->items()),
            'meta' => [
                'current_page' => $groups->currentPage(),
                'last_page' => $groups->lastPage(),
                'per_page' => $groups->perPage(),
                'total' => $groups->total(),
            ],
        ]);
    }

    /**
     * Get single group
     *
     * @route GET /api/v1/admin/groups/{id}
     */
    public function show($id)
    {
        $group = QueryBuilder::for(EGroup::class)
            ->allowedIncludes([
                'specialty',
                'department',
                'students',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new GroupResource($group),
        ]);
    }

    /**
     * Create new group
     *
     * @route POST /api/v1/admin/groups
     */
    public function store(StoreGroupRequest $request)
    {
        $validated = $request->validated();
        $group = EGroup::create($validated);
        $group->load('specialty', 'department');

        return response()->json([
            'success' => true,
            'data' => new GroupResource($group),
            'message' => 'Guruh muvaffaqiyatli yaratildi',
        ], 201);
    }

    /**
     * Update group
     *
     * @route PUT /api/v1/admin/groups/{id}
     */
    public function update(UpdateGroupRequest $request, $id)
    {
        $group = EGroup::findOrFail($id);
        $validated = $request->validated();
        $group->update($validated);
        $group->load('specialty', 'department');

        return response()->json([
            'success' => true,
            'data' => new GroupResource($group),
            'message' => 'Guruh muvaffaqiyatli yangilandi',
        ]);
    }

    /**
     * Delete group (soft delete - set active = false)
     *
     * @route DELETE /api/v1/admin/groups/{id}
     */
    public function destroy($id)
    {
        $group = EGroup::findOrFail($id);
        $group->update(['active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Guruh o\'chirildi (deactivated)',
        ]);
    }
}
