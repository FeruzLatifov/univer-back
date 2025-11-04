<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dashboard Controller
 *
 * Provides dashboard statistics for different user roles
 */
class DashboardController extends Controller
{
    /**
     * Get dashboard index/stats
     *
     * @route GET /api/v1/employee/dashboard
     * @authenticated
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('admin-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $roleCode = $user->role->code ?? null;

            // Return role-specific dashboard data
            switch ($roleCode) {
                case 'teacher':
                case 'department':
                    return $this->getTeacherDashboard($user);
                case 'academic_board':
                    return $this->getAcademicDashboard($user);
                case 'dean':
                case 'super_admin':
                case 'minadmin':
                    return $this->getAdminDashboard($user);
                default:
                    return $this->getDefaultDashboard($user);
            }
        } catch (\Exception $e) {
            Log::error('[DashboardController] Failed to get dashboard', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    protected function getTeacherDashboard($user): JsonResponse
    {
        $stats = [
            'attendance_journals' => $this->getTeacherAttendanceJournals($user),
            'my_lessons' => $this->getTeacherLessons($user),
            'training_list' => $this->getTeacherSubjects($user),
            'midterm_exams' => $this->getTeacherExams($user, 'midterm'),
            'final_exams' => $this->getTeacherExams($user, 'final'),
            'other_exams' => $this->getTeacherExams($user, 'other'),
            'recent_activity' => $this->getRecentActivity($user),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'role' => 'teacher',
                'stats' => $stats,
                'user' => [
                    'name' => $user->full_name ?? $user->login,
                    'role_name' => $user->role->name ?? 'O\'qituvchi',
                ],
            ],
        ]);
    }

    protected function getAcademicDashboard($user): JsonResponse
    {
        $stats = [
            'total_students' => $this->getTotalStudents(),
            'total_groups' => $this->getTotalGroups(),
            'total_subjects' => $this->getTotalSubjects(),
            'attendance_rate' => $this->getAttendanceRate(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'role' => 'academic',
                'stats' => $stats,
            ],
        ]);
    }

    protected function getAdminDashboard($user): JsonResponse
    {
        $stats = [
            'total_students' => $this->getTotalStudents(),
            'total_teachers' => $this->getTotalTeachers(),
            'total_employees' => $this->getTotalEmployees(),
            'total_faculties' => $this->getTotalFaculties(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'role' => 'admin',
                'stats' => $stats,
            ],
        ]);
    }

    protected function getDefaultDashboard($user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'role' => 'default',
                'message' => 'Welcome to HEMIS',
                'user' => [
                    'name' => $user->full_name ?? $user->login,
                    'role_name' => $user->role->name ?? 'User',
                ],
            ],
        ]);
    }

    // Helpers (mock implementations)
    protected function getTeacherAttendanceJournals($user): int { return 12; }
    protected function getTeacherLessons($user): int { return 24; }
    protected function getTeacherSubjects($user): int { return 15; }
    protected function getTeacherExams($user, string $type): int { return match($type) { 'midterm' => 8, 'final' => 5, 'other' => 3, default => 0, }; }
    protected function getRecentActivity($user): array { return [ ['type' => 'attendance','message' => 'Yangi davomat jurnali kiritildi','timestamp' => now()->subHours(2)->toIso8601String(),], ['type' => 'grade','message' => 'Oraliq nazorat baholari saqlandi','timestamp' => now()->subHours(5)->toIso8601String(),], ]; }
    protected function getTotalStudents(): int { return DB::table('e_student')->where('status', '!=', 10)->count(); }
    protected function getTotalTeachers(): int { return DB::table('e_admin')->where('_role', 2)->where('status', 'enable')->count(); }
    protected function getTotalEmployees(): int { return DB::table('e_employee')->where('active', true)->count(); }
    protected function getTotalGroups(): int { return DB::table('e_group')->where('active', true)->count(); }
    protected function getTotalSubjects(): int { return DB::table('e_subject')->where('active', true)->count(); }
    protected function getTotalFaculties(): int { return DB::table('e_structure')->where('_structure_type', 11)->where('active', true)->count(); }
    protected function getAttendanceRate(): float { return 87.5; }
}


