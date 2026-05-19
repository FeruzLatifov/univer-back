<?php

namespace App\Services\Teacher;

use App\Models\ESubjectSchedule;
use App\Models\EGroup;
use App\Models\EStudent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
    /** @var array<string, string|null>|null */
    private ?array $trainingTypeMap = null;

    /** @var array<string, string|null> */
    private array $auditoriumMap = [];

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
        $this->preloadAuditoriums($schedules);

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

        $this->preloadAuditoriums($schedules);

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

        $this->preloadAuditoriums($schedules);

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
        $trainingTypeCode = $schedule->_training_type;
        $auditoriumCode = $schedule->_auditorium;

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
            'lesson_pair' => [
                'code' => $schedule->_lesson_pair,
                'number' => $schedule->lessonPair->number ?? null,
            ],
            'training_type' => [
                'code' => $trainingTypeCode,
                'name' => $trainingTypeCode !== null ? $this->trainingTypeName($trainingTypeCode) : null,
            ],
            'semester' => $schedule->_semester ?? null,
            'auditorium' => [
                'code' => $auditoriumCode,
                'name' => $auditoriumCode !== null ? $this->auditoriumName($auditoriumCode) : null,
            ],
            'lesson_date' => $schedule->lesson_date ?? null,
            'week' => $schedule->week ?? null,
        ];
    }

    /**
     * Pre-load auditorium names for every distinct _auditorium code in the schedule set.
     *
     * @param \Illuminate\Support\Collection<int, ESubjectSchedule> $schedules
     */
    private function preloadAuditoriums(Collection $schedules): void
    {
        $codes = $schedules->pluck('_auditorium')->filter()->unique()->values();
        $missing = $codes->reject(fn($code) => array_key_exists((string) $code, $this->auditoriumMap));

        if ($missing->isEmpty()) {
            return;
        }

        $rows = DB::table('e_auditorium')
            ->whereIn('code', $missing->all())
            ->pluck('name', 'code');

        foreach ($rows as $code => $name) {
            $this->auditoriumMap[(string) $code] = $name;
        }

        // Mark unresolved codes as null to avoid re-querying.
        foreach ($missing as $code) {
            if (!array_key_exists((string) $code, $this->auditoriumMap)) {
                $this->auditoriumMap[(string) $code] = null;
            }
        }
    }

    private function auditoriumName(string|int $code): ?string
    {
        $key = (string) $code;

        if (!array_key_exists($key, $this->auditoriumMap)) {
            $this->auditoriumMap[$key] = DB::table('e_auditorium')
                ->where('code', $code)
                ->value('name');
        }

        return $this->auditoriumMap[$key];
    }

    private function trainingTypeName(string|int $code): ?string
    {
        if ($this->trainingTypeMap === null) {
            $this->trainingTypeMap = \Illuminate\Support\Facades\Cache::remember(
                'training_types:map',
                3600,
                fn() => DB::table('h_training_type')
                    ->where('active', true)
                    ->pluck('name', 'code')
                    ->map(fn($v) => (string) $v)
                    ->all()
            );
        }

        return $this->trainingTypeMap[(string) $code] ?? null;
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

    /**
     * Filter options for the schedule UI: faculties, curriculums, education years,
     * semesters, groups, weeks — all scoped to the teacher's group set.
     * Yii2 parity: ScheduleController::actionFilterOptions.
     *
     * Supported $filters keys: faculty_id, group_id, education_year, semester, expand
     * (expand = comma-separated subset of [faculties, curriculums, educationYears, semesters, groups, weeks]).
     *
     * @return array<string, mixed>
     */
    public function getFilterOptions(int $teacherId, array $filters = []): array
    {
        $groupIds = app(GroupService::class)->teacherGroupIds($teacherId);
        $sections = $this->resolveExpandSections($filters['expand'] ?? null);

        $facultyId = isset($filters['faculty_id']) ? (int) $filters['faculty_id'] : null;
        $groupId = isset($filters['group_id']) ? (int) $filters['group_id'] : null;
        $educationYear = $filters['education_year'] ?? null;
        $semester = $filters['semester'] ?? null;

        $result = [];

        if (empty($groupIds)) {
            foreach ($sections as $name) {
                $result[$name] = [];
            }

            return $result;
        }

        $curriculumIdForGroup = null;
        if ($groupId !== null && in_array($groupId, $groupIds, true)) {
            $curriculumIdForGroup = DB::table('e_group')->where('id', $groupId)->value('_curriculum');
            $curriculumIdForGroup = $curriculumIdForGroup !== null ? (int) $curriculumIdForGroup : null;
        }

        if (in_array('faculties', $sections, true)) {
            $result['faculties'] = $this->facultyOptions($groupIds);
        }

        if (in_array('curriculums', $sections, true)) {
            $result['curriculums'] = $this->curriculumOptions($groupIds, $facultyId, $groupId);
        }

        if (in_array('educationYears', $sections, true)) {
            $result['educationYears'] = $this->educationYearOptions();
        }

        if (in_array('semesters', $sections, true)) {
            $result['semesters'] = $this->semesterOptions($curriculumIdForGroup, $educationYear);
        }

        if (in_array('groups', $sections, true)) {
            $result['groups'] = $this->groupContingentOptions($groupIds, $curriculumIdForGroup, $educationYear, $semester);
        }

        if (in_array('weeks', $sections, true)) {
            $result['weeks'] = $this->weekOptions($curriculumIdForGroup, $semester);
        }

        return $result;
    }

    /**
     * @return string[]
     */
    private function resolveExpandSections(?string $expand): array
    {
        $all = ['faculties', 'curriculums', 'educationYears', 'semesters', 'groups', 'weeks'];

        if (empty($expand)) {
            return $all;
        }

        $items = array_filter(array_map('trim', explode(',', $expand)));
        $items = array_values(array_intersect($all, $items));

        return $items ?: $all;
    }

    /**
     * @param int[] $groupIds
     * @return array<int, array{id: int, name: string|null}>
     */
    private function facultyOptions(array $groupIds): array
    {
        $facultyIds = DB::table('e_group')
            ->whereIn('id', $groupIds)
            ->whereNotNull('_department')
            ->distinct()
            ->pluck('_department')
            ->all();

        if (empty($facultyIds)) {
            return [];
        }

        return DB::table('e_department')
            ->whereIn('id', $facultyIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($row) => ['id' => (int) $row->id, 'name' => $row->name])
            ->all();
    }

    /**
     * @param int[] $groupIds
     * @return array<int, array{id: int, name: string|null}>
     */
    private function curriculumOptions(array $groupIds, ?int $facultyId, ?int $groupId): array
    {
        $query = DB::table('e_group')
            ->whereIn('id', $groupIds)
            ->whereNotNull('_curriculum');

        if ($facultyId !== null) {
            $query->where('_department', $facultyId);
        }

        if ($groupId !== null) {
            $query->where('id', $groupId);
        }

        $curriculumIds = $query->distinct()->pluck('_curriculum')->all();

        if (empty($curriculumIds)) {
            return [];
        }

        return DB::table('e_curriculum')
            ->whereIn('id', $curriculumIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($row) => ['id' => (int) $row->id, 'name' => $row->name])
            ->all();
    }

    /**
     * @return array<int, array{id: string, name: string|null}>
     */
    private function educationYearOptions(): array
    {
        return DB::table('e_education_year')
            ->where('active', true)
            ->orderByDesc('code')
            ->get(['code', 'name'])
            ->map(fn($row) => ['id' => (string) $row->code, 'name' => $row->name])
            ->all();
    }

    /**
     * @return array<int, array{code: string, name: string|null}>
     */
    private function semesterOptions(?int $curriculumId, ?string $educationYear): array
    {
        if ($curriculumId === null || $educationYear === null) {
            return [];
        }

        return DB::table('h_semestr')
            ->where('_curriculum', $curriculumId)
            ->where('_education_year', $educationYear)
            ->where('active', true)
            ->orderBy('position')
            ->get(['code', 'name'])
            ->map(fn($row) => ['code' => (string) $row->code, 'name' => $row->name])
            ->all();
    }

    /**
     * Groups present in the teacher scope for the given (curriculum, year, semester) contingent.
     *
     * @param int[] $groupIds
     * @return array<int, array{id: int, name: string|null}>
     */
    private function groupContingentOptions(array $groupIds, ?int $curriculumId, ?string $educationYear, ?string $semester): array
    {
        if ($curriculumId === null || $educationYear === null || $semester === null) {
            return [];
        }

        $rows = DB::table('e_student_meta as sm')
            ->join('e_group as g', 'g.id', '=', 'sm._group')
            ->whereIn('sm._group', $groupIds)
            ->where('sm._curriculum', $curriculumId)
            ->where('sm._education_year', $educationYear)
            ->where('sm._semestr', $semester)
            ->where('sm.active', true)
            ->distinct()
            ->orderBy('g.name')
            ->get(['g.id', 'g.name']);

        return $rows->map(fn($row) => ['id' => (int) $row->id, 'name' => $row->name])->all();
    }

    /**
     * @return array<int, array{id: int, name: string|null}>
     */
    private function weekOptions(?int $curriculumId, ?string $semester): array
    {
        if ($curriculumId === null || $semester === null) {
            return [];
        }

        if (!DB::getSchemaBuilder()->hasTable('e_curriculum_week')) {
            return [];
        }

        return DB::table('e_curriculum_week')
            ->where('_curriculum', $curriculumId)
            ->where('_semester', $semester)
            ->where('active', true)
            ->orderBy('position')
            ->get(['id', 'name', 'start_date', 'end_date'])
            ->map(fn($row) => [
                'id'         => (int) $row->id,
                'name'       => $row->name,
                'start_date' => $row->start_date,
                'end_date'   => $row->end_date,
            ])
            ->all();
    }
}
