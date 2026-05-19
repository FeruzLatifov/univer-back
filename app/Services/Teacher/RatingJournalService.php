<?php

namespace App\Services\Teacher;

use App\Services\Teacher\Concerns\ResolvesGroupSemesters;
use Illuminate\Support\Facades\DB;

class RatingJournalService
{
    use ResolvesGroupSemesters;

    public function __construct(private readonly GroupService $groups)
    {
    }

    /**
     * Reyting qaydnomasi — talabalar ro'yxati, har birida o'tilgan fanlar va baholari.
     * Yii2 contracti: api/ver1/tutor/grade/rating.
     *
     * @return array{
     *     ratings: array<int, array<string, mixed>>,
     *     group_id: int|null,
     *     group_ids: int[],
     *     group_semesters: array<int, string>,
     *     semester: string|null,
     *     education_year: string,
     *     subject_id: int|null,
     *     pagination: array<string, int>
     * }
     */
    public function list(int $employeeId, array $filters): array
    {
        $educationYear = (string) $filters['education_year'];
        $requestedGroupId = isset($filters['group_id']) ? (int) $filters['group_id'] : null;
        $forcedSemester = $filters['semester'] ?? null;
        $subjectId = isset($filters['subject_id']) ? (int) $filters['subject_id'] : null;
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));

        $groupIds = $this->resolveGroupScope($employeeId, $requestedGroupId);

        $emptyResponse = [
            'ratings'         => [],
            'group_id'        => $requestedGroupId,
            'group_ids'       => [],
            'group_semesters' => [],
            'semester'        => $forcedSemester,
            'education_year'  => $educationYear,
            'subject_id'      => $subjectId,
            'pagination'      => $this->buildPagination(0, $page, $perPage),
        ];

        if (empty($groupIds)) {
            return $emptyResponse;
        }

        $groupSemesters = $this->resolveGroupSemesters($groupIds, $forcedSemester, $educationYear);

        if (empty($groupSemesters)) {
            return $emptyResponse;
        }

        $baseQuery = $this->baseQuery($groupSemesters, $educationYear, $subjectId);

        $totalCount = (int) (clone $baseQuery)->distinct()->count('ss._student');

        if ($totalCount === 0) {
            return array_merge($emptyResponse, [
                'group_ids'       => array_map('intval', array_keys($groupSemesters)),
                'group_semesters' => $groupSemesters,
                'semester'        => $this->primarySemester($groupSemesters, $requestedGroupId, $forcedSemester),
            ]);
        }

        $paginatedStudentIds = (clone $baseQuery)
            ->select('ss._student')
            ->distinct()
            ->orderBy('ss._student')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->pluck('ss._student')
            ->map(fn($v) => (int) $v)
            ->all();

        $rows = $this->loadSubjectRows($paginatedStudentIds, $groupSemesters, $educationYear, $subjectId);

        return [
            'ratings'         => $this->buildRatings($paginatedStudentIds, $rows),
            'group_id'        => $requestedGroupId,
            'group_ids'       => array_map('intval', array_keys($groupSemesters)),
            'group_semesters' => $groupSemesters,
            'semester'        => $this->primarySemester($groupSemesters, $requestedGroupId, $forcedSemester),
            'education_year'  => $educationYear,
            'subject_id'      => $subjectId,
            'pagination'      => $this->buildPagination($totalCount, $page, $perPage),
        ];
    }

    /**
     * @return int[]
     */
    private function resolveGroupScope(int $employeeId, ?int $requestedGroupId): array
    {
        $owned = $this->groups->teacherGroupIds($employeeId);

        if ($requestedGroupId === null) {
            return $owned;
        }

        if (!in_array($requestedGroupId, $owned, true)) {
            abort(404, 'Guruh topilmadi yoki sizga tegishli emas');
        }

        return [$requestedGroupId];
    }

    /**
     * Base e_student_subject query with the (group, semester) compound filter.
     *
     * @param array<int, string> $groupSemesters
     */
    private function baseQuery(array $groupSemesters, string $educationYear, ?int $subjectId): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('e_student_subject as ss')
            ->where('ss._education_year', $educationYear);

        if (count($groupSemesters) === 1) {
            $groupId = (int) array_key_first($groupSemesters);
            $query->where('ss._group', $groupId)
                ->where('ss._semester', $groupSemesters[$groupId]);
        } else {
            $query->where(function ($q) use ($groupSemesters) {
                foreach ($groupSemesters as $groupId => $semester) {
                    $q->orWhere(function ($qq) use ($groupId, $semester) {
                        $qq->where('ss._group', (int) $groupId)
                            ->where('ss._semester', $semester);
                    });
                }
            });
        }

        if ($subjectId !== null) {
            $query->where('ss._subject', $subjectId);
        }

        return $query;
    }

    /**
     * Fetch student + subject + group + academic_record rows for the paginated set.
     *
     * @param int[] $studentIds
     * @param array<int, string> $groupSemesters
     * @return array<int, object>
     */
    private function loadSubjectRows(array $studentIds, array $groupSemesters, string $educationYear, ?int $subjectId): array
    {
        if (empty($studentIds)) {
            return [];
        }

        $groupIds = array_map('intval', array_keys($groupSemesters));

        $query = DB::table('e_student_subject as ss')
            ->leftJoin('e_student as st', 'st.id', '=', 'ss._student')
            ->leftJoin('e_subject as sub', 'sub.id', '=', 'ss._subject')
            ->leftJoin('e_group as g', 'g.id', '=', 'ss._group')
            ->leftJoin('e_academic_record as ar', function ($join) {
                $join->on('ar._curriculum', '=', 'ss._curriculum')
                    ->on('ar._subject', '=', 'ss._subject')
                    ->on('ar._semester', '=', 'ss._semester')
                    ->on('ar._student', '=', 'ss._student');
            })
            ->whereIn('ss._student', $studentIds)
            ->whereIn('ss._group', $groupIds)
            ->where('ss._education_year', $educationYear)
            ->select([
                'ss.id as ss_id',
                'ss._student as student_id',
                'ss._group as group_id',
                'st.first_name',
                'st.second_name',
                'st.third_name',
                'st.student_id_number',
                'sub.id as subject_id',
                'sub.name as subject_name',
                'g.name as group_name',
                'ar.grade',
                'ar.total_point',
                'ar.credit',
            ])
            ->orderBy('ss._student')
            ->orderBy('ss.id');

        if ($subjectId !== null) {
            $query->where('ss._subject', $subjectId);
        }

        return $query->get()->all();
    }

    /**
     * Group flat rows into the {student, group, subjects[]} shape, preserving page order.
     *
     * @param int[] $orderedStudentIds
     * @param array<int, object> $rows
     */
    private function buildRatings(array $orderedStudentIds, array $rows): array
    {
        $map = [];

        foreach ($rows as $row) {
            $studentId = (int) $row->student_id;

            if (!isset($map[$studentId])) {
                $map[$studentId] = [
                    'student'  => [
                        'id'                => $studentId,
                        'full_name'         => trim(($row->second_name ?? '') . ' ' . ($row->first_name ?? '') . ' ' . ($row->third_name ?? '')),
                        'student_id_number' => $row->student_id_number,
                    ],
                    'group'    => $row->group_id ? [
                        'id'   => (int) $row->group_id,
                        'name' => $row->group_name,
                    ] : null,
                    'subjects' => [],
                ];
            }

            $map[$studentId]['subjects'][] = [
                'id'          => (int) $row->ss_id,
                'subject'     => $row->subject_id ? [
                    'id'   => (int) $row->subject_id,
                    'name' => $row->subject_name,
                ] : null,
                'grade'       => $row->grade,
                'total_point' => $row->total_point !== null ? (float) $row->total_point : null,
                'credit'      => $row->credit !== null ? (float) $row->credit : null,
            ];
        }

        $result = [];
        foreach ($orderedStudentIds as $studentId) {
            if (isset($map[$studentId])) {
                $result[] = $map[$studentId];
            }
        }

        return $result;
    }

    /**
     * @param array<int, string> $groupSemesters
     */
    private function primarySemester(array $groupSemesters, ?int $requestedGroupId, ?string $forcedSemester): ?string
    {
        if (!empty($forcedSemester)) {
            return $forcedSemester;
        }

        if ($requestedGroupId !== null && isset($groupSemesters[$requestedGroupId])) {
            return $groupSemesters[$requestedGroupId];
        }

        return reset($groupSemesters) ?: null;
    }

    /**
     * @return array<string, int>
     */
    private function buildPagination(int $totalCount, int $page = 1, int $perPage = 20): array
    {
        return [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total_count'  => $totalCount,
            'total_pages'  => $perPage > 0 ? (int) ceil($totalCount / $perPage) : 0,
        ];
    }
}
