<?php

namespace App\Services\Admin;

use App\Models\EDepartment;
use App\Services\CacheService;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Admin Department Service
 *
 * BUSINESS LOGIC LAYER
 * Modular Monolith Architecture - Admin Module
 *
 * @package App\Services\Admin
 */
class DepartmentService
{
    /**
     * Get paginated list of departments with caching
     */
    public function getDepartmentsList(array $queryParams): array
    {
        $cacheKey = CacheService::key('departments:list', $queryParams);

        return CacheService::remember($cacheKey, function () use ($queryParams) {
            $perPage = $queryParams['per_page'] ?? 20;

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
                ->paginate($perPage);

            return [
                'data' => $departments->items(),
                'meta' => [
                    'current_page' => $departments->currentPage(),
                    'last_page' => $departments->lastPage(),
                    'per_page' => $departments->perPage(),
                    'total' => $departments->total(),
                ],
            ];
        }, 'departments');
    }

    /**
     * Get single department
     */
    public function getDepartment(int $departmentId): EDepartment
    {
        return QueryBuilder::for(EDepartment::class)
            ->allowedIncludes(['parent', 'children', 'specialties', 'groups'])
            ->findOrFail($departmentId);
    }

    /**
     * Create new department
     */
    public function createDepartment(array $data): EDepartment
    {
        $department = EDepartment::create($data);
        $department->load('parent');

        logger()->info('Department created', ['department_id' => $department->id]);

        return $department;
    }

    /**
     * Update department
     */
    public function updateDepartment(int $departmentId, array $data): EDepartment
    {
        $department = EDepartment::findOrFail($departmentId);
        $department->update($data);
        $department->load('parent');

        logger()->info('Department updated', ['department_id' => $department->id]);

        return $department;
    }

    /**
     * Soft delete department
     */
    public function deleteDepartment(int $departmentId): void
    {
        $department = EDepartment::findOrFail($departmentId);
        $department->update(['active' => false]);

        logger()->info('Department deactivated', ['department_id' => $department->id]);
    }
}
