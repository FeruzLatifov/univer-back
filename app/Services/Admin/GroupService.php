<?php

namespace App\Services\Admin;

use App\Models\EGroup;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class GroupService
{
    public function getGroupsList(array $queryParams)
    {
        return QueryBuilder::for(EGroup::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('name'),
                AllowedFilter::partial('code'),
                AllowedFilter::exact('_department'),
                AllowedFilter::exact('_specialty'),
                AllowedFilter::exact('active'),
            ])
            ->allowedIncludes(['department', 'specialty', 'students'])
            ->allowedSorts(['id', 'name', 'code', 'created_at'])
            ->paginate($queryParams['per_page'] ?? 20);
    }

    public function getGroup(int $id)
    {
        return QueryBuilder::for(EGroup::class)
            ->allowedIncludes(['department', 'specialty', 'students'])
            ->findOrFail($id);
    }

    public function createGroup(array $data)
    {
        return EGroup::create($data);
    }

    public function updateGroup(int $id, array $data)
    {
        $group = EGroup::findOrFail($id);
        $group->update($data);
        return $group;
    }

    public function deleteGroup(int $id): void
    {
        EGroup::findOrFail($id)->update(['active' => false]);
    }
}
