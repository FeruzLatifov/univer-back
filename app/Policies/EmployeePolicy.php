<?php

namespace App\Policies;

use App\Models\EAdmin;
use App\Models\EEmployee;

/**
 * Employee Policy
 *
 * Authorization rules for employee (xodim) operations
 */
class EmployeePolicy
{
    /**
     * Determine if the given admin can view any employees.
     */
    public function viewAny(EAdmin $admin): bool
    {
        // Only HR, admin, and rector can view employees list
        $allowedRoles = ['admin', 'rector', 'hr'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if the given admin can view the employee.
     */
    public function view(EAdmin $admin, EEmployee $employee): bool
    {
        // Can view own employee record
        if ($admin->_employee && $admin->_employee == $employee->id) {
            return true;
        }

        // Or if has permission to view all employees
        $allowedRoles = ['admin', 'rector', 'hr'];
        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if the given admin can create employees.
     */
    public function create(EAdmin $admin): bool
    {
        // Only HR, admin, and rector can create employees
        $allowedRoles = ['admin', 'rector', 'hr'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if the given admin can update the employee.
     */
    public function update(EAdmin $admin, EEmployee $employee): bool
    {
        // Only HR, admin, and rector can update employees
        $allowedRoles = ['admin', 'rector', 'hr'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if the given admin can delete the employee.
     */
    public function delete(EAdmin $admin, EEmployee $employee): bool
    {
        // Only admin and rector can delete employees
        $allowedRoles = ['admin', 'rector'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if admin can update their own profile.
     */
    public function updateOwnProfile(EAdmin $admin): bool
    {
        // Staff can update their own profile
        return $admin->active && $admin->employee !== null;
    }

    /**
     * Determine if admin can upload their own avatar.
     */
    public function uploadOwnAvatar(EAdmin $admin): bool
    {
        // Staff can upload their own avatar
        return $admin->active && $admin->employee !== null;
    }
}
