<?php
namespace App\Services\Employee;

class TeacherLoadService
{
    public function getTeacherLoad(int $teacherId): array
    {
        return ['load_info' => [], 'subjects' => []];
    }
}
