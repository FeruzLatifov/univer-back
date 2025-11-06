<?php

namespace Tests\Unit\Services\Teacher;

use App\Models\EAttendance;
use App\Models\EStudent;
use App\Models\ESubject;
use App\Models\ESubjectSchedule;
use App\Services\Teacher\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * AttendanceService Unit Tests
 * 
 * Tests the Teacher Attendance Service business logic
 * 
 * @group unit
 * @group teacher
 * @group attendance
 */
class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AttendanceService $attendanceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->attendanceService = new AttendanceService();
    }

    /**
     * Test getting attendance for a schedule
     */
    public function test_get_attendance_for_schedule(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $subject = ESubject::factory()->create();
        
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            '_subject' => $subject->id,
            'active' => true,
        ]);

        $result = $this->attendanceService->getAttendance(
            $teacher->id,
            $schedule->id,
            Carbon::today()
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('schedule', $result);
        $this->assertArrayHasKey('students', $result);
    }

    /**
     * Test marking student as present
     */
    public function test_mark_student_present(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);
        $student = EStudent::factory()->create();

        $attendanceData = [
            'student_id' => $student->id,
            'status' => EAttendance::STATUS_PRESENT,
            'lesson_date' => Carbon::today()->format('Y-m-d'),
        ];

        $result = $this->attendanceService->markAttendance(
            $teacher->id,
            $schedule->id,
            $attendanceData
        );

        $this->assertIsArray($result);
        $this->assertEquals(EAttendance::STATUS_PRESENT, $result['status']);
    }

    /**
     * Test marking student as absent
     */
    public function test_mark_student_absent(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);
        $student = EStudent::factory()->create();

        $attendanceData = [
            'student_id' => $student->id,
            'status' => EAttendance::STATUS_ABSENT,
            'lesson_date' => Carbon::today()->format('Y-m-d'),
            'reason' => 'Sick',
        ];

        $result = $this->attendanceService->markAttendance(
            $teacher->id,
            $schedule->id,
            $attendanceData
        );

        $this->assertIsArray($result);
        $this->assertEquals(EAttendance::STATUS_ABSENT, $result['status']);
        $this->assertEquals('Sick', $result['reason']);
    }

    /**
     * Test bulk attendance marking
     */
    public function test_bulk_attendance_marking(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);
        
        $students = EStudent::factory()->count(3)->create();
        
        $bulkData = [
            'lesson_date' => Carbon::today()->format('Y-m-d'),
            'attendances' => $students->map(function ($student, $index) {
                return [
                    'student_id' => $student->id,
                    'status' => $index === 0 ? EAttendance::STATUS_ABSENT : EAttendance::STATUS_PRESENT,
                ];
            })->toArray(),
        ];

        $result = $this->attendanceService->markBulkAttendance(
            $teacher->id,
            $schedule->id,
            $bulkData
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('marked', $result);
        $this->assertEquals(3, $result['total']);
    }

    /**
     * Test attendance update
     */
    public function test_update_attendance(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);
        $student = EStudent::factory()->create();

        // Create initial attendance
        $attendance = EAttendance::factory()->create([
            '_student' => $student->id,
            '_schedule' => $schedule->id,
            'status' => EAttendance::STATUS_PRESENT,
        ]);

        // Update to absent
        $updateData = [
            'status' => EAttendance::STATUS_ABSENT,
            'reason' => 'Late arrival',
        ];

        $result = $this->attendanceService->updateAttendance(
            $teacher->id,
            $attendance->id,
            $updateData
        );

        $this->assertEquals(EAttendance::STATUS_ABSENT, $result['status']);
        $this->assertEquals('Late arrival', $result['reason']);
    }

    /**
     * Test attendance statistics
     */
    public function test_attendance_statistics(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);

        $students = EStudent::factory()->count(5)->create();
        
        // Create attendance records
        foreach ($students as $index => $student) {
            EAttendance::factory()->create([
                '_student' => $student->id,
                '_schedule' => $schedule->id,
                'status' => $index < 3 ? EAttendance::STATUS_PRESENT : EAttendance::STATUS_ABSENT,
            ]);
        }

        $stats = $this->attendanceService->getAttendanceStatistics(
            $teacher->id,
            $schedule->id
        );

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_students', $stats);
        $this->assertArrayHasKey('present_count', $stats);
        $this->assertArrayHasKey('absent_count', $stats);
        $this->assertArrayHasKey('attendance_rate', $stats);
    }

    /**
     * Test attendance validation - future date not allowed
     */
    public function test_cannot_mark_attendance_for_future_date(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);
        $student = EStudent::factory()->create();

        $attendanceData = [
            'student_id' => $student->id,
            'status' => EAttendance::STATUS_PRESENT,
            'lesson_date' => Carbon::tomorrow()->format('Y-m-d'),
        ];

        $this->expectException(\Exception::class);

        $this->attendanceService->markAttendance(
            $teacher->id,
            $schedule->id,
            $attendanceData
        );
    }

    /**
     * Test unauthorized teacher cannot mark attendance
     */
    public function test_unauthorized_teacher_cannot_mark_attendance(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $otherTeacher = \App\Models\EEmployee::factory()->create();
        
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);
        $student = EStudent::factory()->create();

        $attendanceData = [
            'student_id' => $student->id,
            'status' => EAttendance::STATUS_PRESENT,
            'lesson_date' => Carbon::today()->format('Y-m-d'),
        ];

        $this->expectException(\Exception::class);

        $this->attendanceService->markAttendance(
            $otherTeacher->id, // Different teacher
            $schedule->id,
            $attendanceData
        );
    }

    /**
     * Test attendance report generation
     */
    public function test_generate_attendance_report(): void
    {
        $teacher = \App\Models\EEmployee::factory()->create();
        $schedule = ESubjectSchedule::factory()->create([
            '_employee' => $teacher->id,
            'active' => true,
        ]);

        $startDate = Carbon::today()->subDays(7);
        $endDate = Carbon::today();

        $report = $this->attendanceService->generateAttendanceReport(
            $teacher->id,
            $schedule->id,
            $startDate,
            $endDate
        );

        $this->assertIsArray($report);
        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('daily_records', $report);
    }
}
