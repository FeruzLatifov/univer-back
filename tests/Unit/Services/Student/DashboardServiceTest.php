<?php

namespace Tests\Unit\Services\Student;

use App\Models\EStudent;
use App\Models\ESubjectSchedule;
use App\Services\Student\DashboardService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Student DashboardService Unit Tests
 * 
 * Tests the Student Dashboard Service business logic
 * 
 * @group unit
 * @group student
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
        $student = EStudent::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($student);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('student_info', $result);
        $this->assertArrayHasKey('academic_summary', $result);
        $this->assertArrayHasKey('upcoming_classes', $result);
        $this->assertArrayHasKey('recent_grades', $result);
    }

    /**
     * Test academic summary structure
     */
    public function test_academic_summary_structure(): void
    {
        $student = EStudent::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($student);
        $summary = $result['academic_summary'];

        $this->assertArrayHasKey('gpa', $summary);
        $this->assertArrayHasKey('total_credits', $summary);
        $this->assertArrayHasKey('attendance_rate', $summary);
        $this->assertArrayHasKey('completed_assignments', $summary);
    }

    /**
     * Test student info contains required fields
     */
    public function test_student_info_contains_required_fields(): void
    {
        $student = EStudent::factory()->create([
            'full_name' => 'Test Student',
            'student_id_number' => 'ST12345',
        ]);
        
        $result = $this->dashboardService->getDashboardData($student);
        $studentInfo = $result['student_info'];

        $this->assertEquals('Test Student', $studentInfo['name']);
        $this->assertEquals('ST12345', $studentInfo['student_id']);
    }

    /**
     * Test upcoming classes returns array
     */
    public function test_upcoming_classes_returns_array(): void
    {
        $student = EStudent::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($student);

        $this->assertIsArray($result['upcoming_classes']);
    }

    /**
     * Test recent grades returns array
     */
    public function test_recent_grades_returns_array(): void
    {
        $student = EStudent::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($student);

        $this->assertIsArray($result['recent_grades']);
    }

    /**
     * Test GPA calculation is within valid range
     */
    public function test_gpa_is_within_valid_range(): void
    {
        $student = EStudent::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($student->id);
        $gpa = $result['academic_summary']['gpa'];

        $this->assertGreaterThanOrEqual(0, $gpa);
        $this->assertLessThanOrEqual(4.0, $gpa);
    }

    /**
     * Test attendance rate is percentage
     */
    public function test_attendance_rate_is_percentage(): void
    {
        $student = EStudent::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($student->id);
        $attendanceRate = $result['academic_summary']['attendance_rate'];

        $this->assertGreaterThanOrEqual(0, $attendanceRate);
        $this->assertLessThanOrEqual(100, $attendanceRate);
    }

    /**
     * Test handles student with no data
     */
    public function test_handles_student_with_no_academic_data(): void
    {
        $student = EStudent::factory()->create();
        
        $result = $this->dashboardService->getDashboardData($student->id);

        // Should return valid structure with zero/empty values
        $this->assertEquals(0, $result['academic_summary']['gpa']);
        $this->assertEquals(0, $result['academic_summary']['total_credits']);
        $this->assertEmpty($result['recent_grades']);
    }
}
