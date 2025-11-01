<?php

namespace App\Policies;

use App\Models\EAdmin;
use App\Models\EGroup;

/**
 * Group Policy
 *
 * Authorization rules for group (guruh) operations
 */
class GroupPolicy
{
    /**
     * Determine if the given admin can view any groups.
     */
    public function viewAny(EAdmin $admin): bool
    {
        // All authenticated staff can view groups
        return $admin->active;
    }

    /**
     * Determine if the given admin can view the group.
     */
    public function view(EAdmin $admin, EGroup $group): bool
    {
        // All authenticated staff can view group details
        return $admin->active;
    }

    /**
     * Determine if the given admin can create groups.
     */
    public function create(EAdmin $admin): bool
    {
        // Only admin, dean, and rector can create groups
        $allowedRoles = ['admin', 'dean', 'rector'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if the given admin can update the group.
     */
    public function update(EAdmin $admin, EGroup $group): bool
    {
        // Only admin, dean, and rector can update groups
        $allowedRoles = ['admin', 'dean', 'rector'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if the given admin can delete the group.
     */
    public function delete(EAdmin $admin, EGroup $group): bool
    {
        // Only admin and rector can delete groups
        $allowedRoles = ['admin', 'rector'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }
}
