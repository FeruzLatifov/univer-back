<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ESubjectSchedule;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Teacher Schedule Controller
 *
 * Manages teacher's class schedule
 */
class ScheduleController extends Controller
{
    use ApiResponse;

    /**
     * Get teacher's weekly schedule
     *
     * GET /api/v1/teacher/schedule
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $semester = $request->input('semester'); // Optional filter

        $query = ESubjectSchedule::where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with(['subject', 'group', 'lessonPair']);

        if ($semester) {
            $query->where('_semester', $semester);
        }

        $schedules = $query->get();

        // Group by day of week
        $weeklySchedule = [];
        for ($day = 1; $day <= 6; $day++) {
            $daySchedules = $schedules->where('week', $day);

            $weeklySchedule[] = [
                'day' => $day,
                'day_name' => $this->getDayName($day),
                'classes' => $daySchedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'subject' => [
                            'id' => $schedule->subject->id,
                            'name' => $schedule->subject->name,
                            'code' => $schedule->subject->code,
                        ],
                        'group' => [
                            'id' => $schedule->group->id,
                            'name' => $schedule->group->name,
                        ],
                        'time' => [
                            'pair_number' => $schedule->lessonPair->number,
                            'start' => $schedule->lessonPair->start_time,
                            'end' => $schedule->lessonPair->end_time,
                            'range' => $schedule->lessonPair->time_range,
                        ],
                        'training_type' => $schedule->_training_type,
                        'semester' => $schedule->_semester,
                    ];
                })->sortBy('time.pair_number')->values(),
            ];
        }

        return $this->successResponse($weeklySchedule, 'Haftalik jadval');
    }

    /**
     * Get schedule for a specific day
     *
     * GET /api/v1/teacher/schedule/day/{day}
     *
     * @param Request $request
     * @param int $day Day of week (1-6)
     * @return JsonResponse
     */
    public function day(Request $request, int $day): JsonResponse
    {
        if ($day < 1 || $day > 6) {
            return $this->validationErrorResponse(['day' => 'Kun 1-6 oralig\'ida bo\'lishi kerak']);
        }

        $teacher = $request->user();

        $schedules = ESubjectSchedule::where('_employee', $teacher->employee->id)
            ->where('week', $day)
            ->where('active', true)
            ->with(['subject', 'group', 'lessonPair'])
            ->get()
            ->sortBy('lessonPair.number');

        $daySchedule = [
            'day' => $day,
            'day_name' => $this->getDayName($day),
            'classes' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'subject' => [
                        'id' => $schedule->subject->id,
                        'name' => $schedule->subject->name,
                        'code' => $schedule->subject->code,
                    ],
                    'group' => [
                        'id' => $schedule->group->id,
                        'name' => $schedule->group->name,
                    ],
                    'time' => [
                        'pair_number' => $schedule->lessonPair->number,
                        'start' => $schedule->lessonPair->start_time,
                        'end' => $schedule->lessonPair->end_time,
                        'range' => $schedule->lessonPair->time_range,
                    ],
                    'training_type' => $schedule->_training_type,
                ];
            })->values(),
        ];

        return $this->successResponse($daySchedule, 'Kunlik jadval');
    }

    /**
     * Get teacher's workload summary
     *
     * GET /api/v1/teacher/workload
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function workload(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $semester = $request->input('semester');

        $query = ESubjectSchedule::where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with(['subject']);

        if ($semester) {
            $query->where('_semester', $semester);
        }

        $schedules = $query->get();

        // Calculate workload
        $totalClasses = $schedules->count();
        $uniqueSubjects = $schedules->pluck('_subject')->unique()->count();
        $uniqueGroups = $schedules->pluck('_group')->unique()->count();

        // Group by subject for detailed breakdown
        $subjectWorkload = $schedules->groupBy('_subject')->map(function ($schedules, $subjectId) {
            $subject = $schedules->first()->subject;
            return [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'subject_code' => $subject->code,
                'credit' => $subject->credit,
                'classes_per_week' => $schedules->count(),
                'groups' => $schedules->pluck('_group')->unique()->count(),
            ];
        })->values();

        return $this->successResponse([
            'summary' => [
                'total_classes_per_week' => $totalClasses,
                'total_subjects' => $uniqueSubjects,
                'total_groups' => $uniqueGroups,
            ],
            'subjects' => $subjectWorkload,
        ], 'Dars yuklama');
    }

    /**
     * Get groups taught by teacher
     *
     * GET /api/v1/teacher/groups
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function groups(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $groups = \App\Models\EGroup::query()
            ->select('e_group.*')
            ->join('e_subject_schedule', 'e_group.id', '=', 'e_subject_schedule._group')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_schedule.active', true)
            ->distinct()
            ->with(['specialty'])
            ->get();

        // Add student count and subjects for each group
        $groupsWithInfo = $groups->map(function ($group) use ($teacher) {
            $studentCount = \App\Models\EStudent::where('_group', $group->id)
                ->where('active', true)
                ->count();

            $subjects = ESubjectSchedule::where('_group', $group->id)
                ->where('_employee', $teacher->employee->id)
                ->where('active', true)
                ->with('subject')
                ->get()
                ->pluck('subject')
                ->unique('id')
                ->map(fn($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'code' => $s->code,
                ]);

            return [
                'id' => $group->id,
                'name' => $group->name,
                'specialty' => $group->specialty ? $group->specialty->name : null,
                'semester' => $group->_semester,
                'student_count' => $studentCount,
                'subjects' => $subjects,
            ];
        });

        return $this->successResponse($groupsWithInfo, 'Guruhlar ro\'yxati');
    }

    /**
     * Helper: Get day name in Uzbek
     */
    private function getDayName(int $day): string
    {
        $days = [
            1 => 'Dushanba',
            2 => 'Seshanba',
            3 => 'Chorshanba',
            4 => 'Payshanba',
            5 => 'Juma',
            6 => 'Shanba',
        ];

        return $days[$day] ?? '';
    }
}
