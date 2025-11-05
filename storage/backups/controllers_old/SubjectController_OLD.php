<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ESubject;
use App\Models\ESubjectSchedule;
use App\Models\EStudent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Teacher Subject Controller
 *
 * Manages teacher's subjects and related operations
 */
class SubjectController extends Controller
{
    use ApiResponse;

    /**
     * Get all subjects taught by the authenticated teacher
     *
     * GET /api/v1/teacher/subjects
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();

        // Get subjects taught by this teacher through schedule
        $subjects = ESubject::query()
            ->select('e_subject.*')
            ->join('e_subject_schedule', 'e_subject.id', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject.active', true)
            ->where('e_subject_schedule.active', true)
            ->with(['department'])
            ->distinct()
            ->get();

        // Add additional info for each subject
        $subjectsWithInfo = $subjects->map(function ($subject) use ($teacher) {
            // Get schedules for this subject taught by teacher
            $schedules = ESubjectSchedule::where('_subject', $subject->id)
                ->where('_employee', $teacher->employee->id)
                ->where('active', true)
                ->with(['group', 'lessonPair'])
                ->get();

            // Get groups taught
            $groups = $schedules->pluck('group')->unique('id')->values();

            // Count total students
            $totalStudents = 0;
            foreach ($groups as $group) {
                $totalStudents += EStudent::where('_group', $group->id)
                    ->where('active', true)
                    ->count();
            }

            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'credit' => $subject->credit,
                'department' => $subject->department ? $subject->department->name : null,
                'groups' => $groups->map(fn($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                    'semester' => $g->_semester,
                ]),
                'total_students' => $totalStudents,
                'schedule_count' => $schedules->count(),
                'schedules' => $schedules->map(fn($s) => [
                    'id' => $s->id,
                    'day' => $s->week,
                    'day_name' => $s->day_name,
                    'time' => $s->lessonPair ? $s->lessonPair->time_range : null,
                    'group' => $s->group ? $s->group->name : null,
                    'training_type' => $s->_training_type,
                ]),
            ];
        });

        return $this->successResponse($subjectsWithInfo, 'Fanlar ro\'yxati');
    }

    /**
     * Get students list for a specific subject
     *
     * GET /api/v1/teacher/subject/{id}/students
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function students(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        // Get groups for this subject taught by teacher
        $groupIds = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->pluck('_group')
            ->unique();

        // Get students from these groups
        $students = EStudent::whereIn('_group', $groupIds)
            ->where('active', true)
            ->with(['group', 'meta'])
            ->get();

        // Add performance info for each student
        $studentsWithPerformance = $students->map(function ($student) use ($id) {
            // Get grades for this subject
            $grades = $student->grades()
                ->where('_subject', $id)
                ->get();

            // Calculate attendance rate
            $totalClasses = \App\Models\EAttendance::whereHas('subjectSchedule', function ($q) use ($id) {
                $q->where('_subject', $id);
            })->where('_student', $student->id)->count();

            $presentClasses = \App\Models\EAttendance::whereHas('subjectSchedule', function ($q) use ($id) {
                $q->where('_subject', $id);
            })
            ->where('_student', $student->id)
            ->where('_attendance_type', \App\Models\EAttendance::STATUS_PRESENT)
            ->count();

            $attendanceRate = $totalClasses > 0 ? round(($presentClasses / $totalClasses) * 100, 1) : 0;

            return [
                'id' => $student->id,
                'student_id' => $student->student_id_number,
                'full_name' => $student->full_name,
                'group' => $student->group ? $student->group->name : null,
                'photo' => $student->image,
                'phone' => $student->phone,
                'attendance_rate' => $attendanceRate,
                'total_classes' => $totalClasses,
                'present_classes' => $presentClasses,
                'grades' => $grades->map(fn($g) => [
                    'type' => $g->type_name,
                    'grade' => $g->grade,
                    'max_grade' => $g->max_grade,
                    'percentage' => $g->percentage,
                    'letter_grade' => $g->letter_grade,
                ]),
            ];
        });

        return $this->successResponse(
            $studentsWithPerformance,
            'Talabalar ro\'yxati'
        );
    }

    /**
     * Get subject details
     *
     * GET /api/v1/teacher/subject/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        $subject = ESubject::with(['department', 'topics' => function ($q) {
            $q->active()->ordered();
        }])->findOrFail($id);

        // Get schedules
        $schedules = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with(['group', 'lessonPair'])
            ->get();

        return $this->successResponse([
            'id' => $subject->id,
            'name' => $subject->name,
            'code' => $subject->code,
            'credit' => $subject->credit,
            'department' => $subject->department ? $subject->department->name : null,
            'topics_count' => $subject->topics->count(),
            'topics' => $subject->topics->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'hours' => $t->hours,
                'order' => $t->order_number,
            ]),
            'schedules' => $schedules->map(fn($s) => [
                'id' => $s->id,
                'day' => $s->week,
                'day_name' => $s->day_name,
                'time' => $s->lessonPair ? $s->lessonPair->time_range : null,
                'group' => $s->group ? $s->group->name : null,
            ]),
        ], 'Fan ma\'lumotlari');
    }
}
