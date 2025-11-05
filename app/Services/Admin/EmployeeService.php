<?php

namespace App\Services\Admin;

use App\Models\EEmployee;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class EmployeeService
{
    public function getEmployeesList(array $queryParams)
    {
        return QueryBuilder::for(EEmployee::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('first_name'),
                AllowedFilter::partial('second_name'),
                AllowedFilter::exact('_employment_form'),
                AllowedFilter::exact('_staff_position'),
                AllowedFilter::exact('active'),
            ])
            ->allowedIncludes(['department', 'staffPosition', 'employmentForm'])
            ->allowedSorts(['id', 'second_name', 'first_name', 'created_at'])
            ->paginate($queryParams['per_page'] ?? 20);
    }

    public function getEmployee(int $id)
    {
        return QueryBuilder::for(EEmployee::class)
            ->allowedIncludes(['department', 'staffPosition', 'employmentForm'])
            ->findOrFail($id);
    }

    public function createEmployee(array $data)
    {
        return EEmployee::create($data);
    }

    public function updateEmployee(int $id, array $data)
    {
        $employee = EEmployee::findOrFail($id);
        $employee->update($data);
        return $employee;
    }

    public function deleteEmployee(int $id): void
    {
        EEmployee::findOrFail($id)->update(['active' => false]);
    }
}
