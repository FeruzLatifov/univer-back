<?php

namespace App\Services\Teacher;

use App\Models\ESubjectSchedule;
use App\Models\EGroup;
use App\Models\EStudent;
use Illuminate\Support\Collection;

/**
 * Teacher Schedule Service
 *
 * BUSINESS LOGIC LAYER
 * ======================
 *
 * Modular Monolith Architecture - Teacher Module
 * Contains all business logic for teacher schedules
 *
 * Controller → Service → Repository → Model
 *
 * @package App\Services\Teacher
 */
class ScheduleService
{
    /**
     * Get teacher's weekly schedule
     *
     * @param int $teacherId Teacher employee ID
     * @param int|null $semester Optional semester filter
     * @return array
     */
    public function getWeeklySchedule(int $teacherId, ?int $semester = null): array
    {
        $query = ESubjectSchedule::where('_employee', $teacherId)
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
                    return $this->formatScheduleItem($schedule);
                })->sortBy('time.pair_number')->values()->toArray(),
            ];
        }

        return $weeklySchedule;
    }

    /**
     * Get schedule for a specific day
     *
     * @param int $teacherId
     * @param int $day Day of week (1-6)
     * @return array
     */
    public function getDaySchedule(int $teacherId, int $day): array
    {
        $schedules = ESubjectSchedule::where('_employee', $teacherId)
            ->where('week', $day)
            ->where('active', true)
            ->with(['subject', 'group', 'lessonPair'])
            ->get()
            ->sortBy('lessonPair.number');

        return [
            'day' => $day,
            'day_name' => $this->getDayName($day),
            'classes' => $schedules->map(function ($schedule) {
                return $this->formatScheduleItem($schedule);
            })->values()->toArray(),
        ];
    }

    /**
     * Get teacher's workload summary
     *
     * @param int $teacherId
     * @param int|null $semester
     * @return array
     */
    public function getWorkload(int $teacherId, ?int $semester = null): array
    {
        $query = ESubjectSchedule::where('_employee', $teacherId)
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
        })->values()->toArray();

        return [
            'summary' => [
                'total_classes_per_week' => $totalClasses,
                'total_subjects' => $uniqueSubjects,
                'total_groups' => $uniqueGroups,
            ],
            'subjects' => $subjectWorkload,
        ];
    }

    /**
     * Get groups taught by teacher
     *
     * @param int $teacherId
     * @return array
     */
    public function getTeacherGroups(int $teacherId): array
    {
        $groups = EGroup::query()
            ->select('e_group.*')
            ->join('e_subject_schedule', 'e_group.id', '=', 'e_subject_schedule._group')
            ->where('e_subject_schedule._employee', $teacherId)
            ->where('e_subject_schedule.active', true)
            ->distinct()
            ->with(['specialty'])
            ->get();

        // Add student count and subjects for each group
        return $groups->map(function ($group) use ($teacherId) {
            $studentCount = EStudent::where('_group', $group->id)
                ->where('active', true)
                ->count();

            $subjects = ESubjectSchedule::where('_group', $group->id)
                ->where('_employee', $teacherId)
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
                'subjects' => $subjects->toArray(),
            ];
        })->toArray();
    }

    /**
     * Get today's schedule
     *
     * @param int $teacherId
     * @return array
     */
    public function getTodaySchedule(int $teacherId): array
    {
        $today = now();
        $dayOfWeek = $today->dayOfWeek === 0 ? 7 : $today->dayOfWeek; // Sunday = 7

        $schedules = ESubjectSchedule::where('_employee', $teacherId)
            ->where('lesson_date', $today->toDateString())
            ->where('active', true)
            ->with(['subject', 'group', 'lessonPair'])
            ->orderBy('_lesson_pair')
            ->get();

        return $schedules->map(function ($schedule) {
            return $this->formatScheduleItem($schedule);
        })->toArray();
    }

    /**
     * Format schedule item for API response
     *
     * @param ESubjectSchedule $schedule
     * @return array
     */
    private function formatScheduleItem(ESubjectSchedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'subject' => [
                'id' => $schedule->subject->id ?? null,
                'name' => $schedule->subject->name ?? 'Unknown',
                'code' => $schedule->subject->code ?? null,
            ],
            'group' => [
                'id' => $schedule->group->id ?? null,
                'name' => $schedule->group->name ?? 'Unknown',
            ],
            'time' => [
                'pair_number' => $schedule->lessonPair->number ?? null,
                'start' => $schedule->lessonPair->start_time ?? null,
                'end' => $schedule->lessonPair->end_time ?? null,
                'range' => $schedule->lessonPair->time_range ?? null,
            ],
            'training_type' => $schedule->_training_type,
            'semester' => $schedule->_semester ?? null,
            'auditorium' => $schedule->_auditorium ?? null,
        ];
    }

    /**
     * Get day name in Uzbek
     *
     * @param int $day
     * @return string
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
            7 => 'Yakshanba',
        ];

        return $days[$day] ?? '';
    }

    /**
     * Validate day number
     *
     * @param int $day
     * @return bool
     */
    public function isValidDay(int $day): bool
    {
        return $day >= 1 && $day <= 6;
    }
}
