<?php
namespace App\Services\Employee;

class DashboardService
{
    public function getDashboardData($user): array
    {
        return [
            'user_info' => ['name' => $user->name, 'role' => $user->role],
            'stats' => ['pending_tasks' => 0, 'completed_tasks' => 0],
        ];
    }
}
