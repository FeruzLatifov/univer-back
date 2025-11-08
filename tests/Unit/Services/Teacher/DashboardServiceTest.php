<?php

namespace Tests\Unit\Services\Teacher;

use App\Models\EAttendance;
use App\Models\EGrade;
use App\Models\EStudent;
use App\Models\ESubjectSchedule;
use App\Services\Teacher\DashboardService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * DashboardService Unit Tests
 * 
 * Tests the Teacher Dashboard Service business logic
 * 
 * @group unit
 * @group teacher
 * @group dashboard
 */
class DashboardServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected DashboardService $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dashboardService = new DashboardService();
    }

    /**
     * Test that getDashboardData returns expected structure
     */
    public function test_get_dashboard_data_returns_correct_structure(): void
    {
        // Create a teacher (employee)
        $teacher = \App\Models\EEmployee::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($teacher->id);

        // Assert structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('pending_attendance_classes', $result);
        $this->assertArrayHasKey('today_schedule', $result);
        $this->assertArrayHasKey('quick_stats', $result);
    }

    /**
     * Test summary stats structure
     */
    public function test_summary_stats_structure(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($teacher->id);
        $summary = $result['summary'];

        // Assert summary structure
        $this->assertArrayHasKey('total_students', $summary);
        $this->assertArrayHasKey('total_subjects', $summary);
        $this->assertArrayHasKey('pending_grades', $summary);
        $this->assertArrayHasKey('attendance_rate', $summary);
    }

    /**
     * Test that dashboard data handles empty schedules
     */
    public function test_dashboard_handles_teacher_with_no_schedules(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($teacher->id);

        // Should return empty arrays but valid structure
        $this->assertIsArray($result['today_schedule']);
        $this->assertEmpty($result['today_schedule']);
        $this->assertEquals(0, $result['summary']['total_subjects']);
    }

    /**
     * Test pending attendance classes calculation
     */
    public function test_pending_attendance_classes_calculation(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        
        // Create a schedule for today
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);

        $result = $this->dashboardService->getDashboardData($teacher->id);

        $this->assertIsArray($result['pending_attendance_classes']);
    }

    /**
     * Test quick stats calculation
     */
    public function test_quick_stats_returns_numeric_values(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($teacher->id);
        $quickStats = $result['quick_stats'];

        // All quick stats should be numeric
        foreach ($quickStats as $stat) {
            if (isset($stat['value'])) {
                $this->assertIsNumeric($stat['value']);
            }
        }
    }

    /**
     * Test that service handles invalid teacher ID gracefully
     */
    public function test_handles_invalid_teacher_id(): void
    {
        $invalidTeacherId = 99999;
        
        $result = $this->dashboardService->getDashboardData($invalidTeacherId);

        // Should return valid structure with empty/zero values
        $this->assertIsArray($result);
        $this->assertArrayHasKey('summary', $result);
    }

    /**
     * Test attendance rate calculation
     */
    public function test_attendance_rate_calculation(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);

        $result = $this->dashboardService->getDashboardData($teacher->id);

        $this->assertArrayHasKey('attendance_rate', $result['summary']);
        $attendanceRate = $result['summary']['attendance_rate'];
        
        // Attendance rate should be between 0 and 100
        $this->assertGreaterThanOrEqual(0, $attendanceRate);
        $this->assertLessThanOrEqual(100, $attendanceRate);
    }
}
