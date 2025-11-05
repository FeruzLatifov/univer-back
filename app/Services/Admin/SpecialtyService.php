<?php

namespace App\Services\Admin;

use App\Models\ESpecialty;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class SpecialtyService
{
    public function getSpecialtiesList(array $queryParams)
    {
        return QueryBuilder::for(ESpecialty::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('code'),
                AllowedFilter::exact('_department'),
                AllowedFilter::exact('active'),
            ])
            ->allowedIncludes(['department', 'groups', 'students'])
            ->allowedSorts(['id', 'name', 'code', 'created_at'])
            ->paginate($queryParams['per_page'] ?? 20);
    }

    public function getSpecialty(int $id)
    {
        return QueryBuilder::for(ESpecialty::class)
            ->allowedIncludes(['department', 'groups', 'students'])
            ->findOrFail($id);
    }

    public function createSpecialty(array $data)
    {
        return ESpecialty::create($data);
    }

    public function updateSpecialty(int $id, array $data)
    {
        $specialty = ESpecialty::findOrFail($id);
        $specialty->update($data);
        return $specialty;
    }

    public function deleteSpecialty(int $id): void
    {
        ESpecialty::findOrFail($id)->update(['active' => false]);
    }
}
