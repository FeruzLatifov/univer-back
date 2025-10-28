<?php

namespace App\Policies;

use App\Models\EAdmin;
use App\Models\EStudent;

/**
 * Student Policy
 *
 * Authorization rules for student operations
 * Best Practice: Separate authorization logic from controllers
 */
class StudentPolicy
{
    /**
     * Determine if the given admin can view any students.
     */
    public function viewAny(EAdmin $admin): bool
    {
        // All authenticated staff can view students list
        return $admin->active;
    }

    /**
     * Determine if the given admin can view the student.
     */
    public function view(EAdmin $admin, EStudent $student): bool
    {
        // All authenticated staff can view student details
        return $admin->active;
    }

    /**
     * Determine if the given admin can create students.
     */
    public function create(EAdmin $admin): bool
    {
        // Only specific roles can create students
        $allowedRoles = ['admin', 'dean', 'rector'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if the given admin can update the student.
     */
    public function update(EAdmin $admin, EStudent $student): bool
    {
        // Only specific roles can update students
        $allowedRoles = ['admin', 'dean', 'rector'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if the given admin can delete the student.
     */
    public function delete(EAdmin $admin, EStudent $student): bool
    {
        // Only admin and rector can delete students
        $allowedRoles = ['admin', 'rector'];

        return $admin->active && in_array($admin->_role, $allowedRoles);
    }

    /**
     * Determine if the given admin can upload images for the student.
     */
    public function uploadImage(EAdmin $admin, EStudent $student): bool
    {
        // Same as update permission
        return $this->update($admin, $student);
    }

    /**
     * Determine if student can update their own profile.
     */
    public function updateOwnProfile(EStudent $student): bool
    {
        // Students can only update if they're active
        return $student->active;
    }

    /**
     * Determine if student can upload their own avatar.
     */
    public function uploadOwnAvatar(EStudent $student): bool
    {
        // Students can upload avatar if they're active
        return $student->active;
    }
}
