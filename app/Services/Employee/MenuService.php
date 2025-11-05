<?php
namespace App\Services\Employee;

class MenuService
{
    public function getMenu($user): array
    {
        // Menu items based on user role/permissions
        return ['items' => []];
    }
}
