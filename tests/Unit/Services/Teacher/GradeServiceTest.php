<?php

namespace Tests\Unit\Services\Teacher;

use App\Models\EGrade;
use App\Models\EStudent;
use App\Models\ESubject;
use App\Models\ESubjectSchedule;
use App\Services\Teacher\GradeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * GradeService Unit Tests
 * 
 * Tests the Teacher Grade Service business logic
 * 
 * @group unit
 * @group teacher
 * @group grades
 */
class GradeServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected GradeService $gradeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gradeService = new GradeService();
    }

    /**
     * Test that unauthorized teacher cannot access grades
     */
    public function test_unauthorized_teacher_cannot_access_grades(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $subject = ESubject::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Sizda bu fanga kirish huquqi yo\'q');

        $this->gradeService->getGrades($teacher->id, $subject->id);
    }

    /**
     * Test that authorized teacher can access grades
     */
    public function test_authorized_teacher_can_access_grades(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $subject = ESubject::factory()->create();
        
        // Create schedule for teacher
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            '_subject' => $subject->id,
            'active' => true,
        ]);

        $result = $this->gradeService->getGrades($teacher->id, $subject->id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('subject', $result);
        $this->assertArrayHasKey('students', $result);
    }

    /**
     * Test grade creation
     */
    public function test_create_grade_successfully(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $subject = ESubject::factory()->create();
        $student = EStudent::factory()->create();
        
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            '_subject' => $subject->id,
            'active' => true,
        ]);

        $gradeData = [
            'student_id' => $student->id,
            'grade_type' => 'current',
            'grade' => 85,
            'comment' => 'Good work',
        ];

        $result = $this->gradeService->createGrade(
            $teacher->id,
            $subject->id,
            $gradeData
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertEquals(85, $result['grade']);
    }

    /**
     * Test grade update
     */
    public function test_update_grade_successfully(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $subject = ESubject::factory()->create();
        $student = EStudent::factory()->create();
        
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            '_subject' => $subject->id,
            'active' => true,
        ]);

        // Create initial grade
        $grade = EGrade::factory()->create([
            '_student' => $student->id,
            '_subject' => $subject->id,
            '_grade_type' => EGrade::TYPE_CURRENT,
            'grade' => 75,
        ]);

        // Update grade
        $updateData = [
            'grade' => 90,
            'comment' => 'Excellent improvement',
        ];

        $result = $this->gradeService->updateGrade(
            $teacher->id,
            $grade->id,
            $updateData
        );

        $this->assertIsArray($result);
        $this->assertEquals(90, $result['grade']);
        $this->assertEquals('Excellent improvement', $result['comment']);
    }

    /**
     * Test grade validation - invalid grade value
     */
    public function test_create_grade_validates_grade_value(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $subject = ESubject::factory()->create();
        $student = EStudent::factory()->create();
        
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            '_subject' => $subject->id,
            'active' => true,
        ]);

        // Invalid grade (over 100)
        $gradeData = [
            'student_id' => $student->id,
            'grade_type' => 'current',
            'grade' => 150, // Invalid
            'comment' => 'Test',
        ];

        $this->expectException(\Exception::class);

        $this->gradeService->createGrade(
            $teacher->id,
            $subject->id,
            $gradeData
        );
    }

    /**
     * Test filtering grades by type
     */
    public function test_filter_grades_by_type(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $subject = ESubject::factory()->create();
        
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            '_subject' => $subject->id,
            'active' => true,
        ]);

        // Test with specific grade type
        $result = $this->gradeService->getGrades(
            $teacher->id,
            $subject->id,
            'midterm'
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('students', $result);
    }

    /**
     * Test grade statistics calculation
     */
    public function test_grade_statistics_calculation(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $subject = ESubject::factory()->create();
        
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            '_subject' => $subject->id,
            'active' => true,
        ]);

        // Create multiple grades for statistics
        $students = EStudent::factory()->count(5)->create();
        
        foreach ($students as $student) {
            EGrade::factory()->create([
                '_student' => $student->id,
                '_subject' => $subject->id,
                '_grade_type' => EGrade::TYPE_CURRENT,
                'grade' => rand(70, 100),
            ]);
        }

        $stats = $this->gradeService->getGradeStatistics($teacher->id, $subject->id);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('average', $stats);
        $this->assertArrayHasKey('highest', $stats);
        $this->assertArrayHasKey('lowest', $stats);
        $this->assertArrayHasKey('total_students', $stats);
    }

    /**
     * Test bulk grade creation
     */
    public function test_bulk_grade_creation(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $subject = ESubject::factory()->create();
        
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            '_subject' => $subject->id,
            'active' => true,
        ]);

        $students = EStudent::factory()->count(3)->create();
        
        $bulkData = $students->map(function ($student) {
            return [
                'student_id' => $student->id,
                'grade' => rand(70, 100),
                'grade_type' => 'current',
            ];
        })->toArray();

        $result = $this->gradeService->createBulkGrades(
            $teacher->id,
            $subject->id,
            $bulkData
        );

        $this->assertIsArray($result);
        $this->assertCount(3, $result['created']);
    }
}
