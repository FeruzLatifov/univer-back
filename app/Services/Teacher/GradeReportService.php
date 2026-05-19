<?php

namespace App\Services\Teacher;

use App\Services\Teacher\Concerns\ResolvesGroupSemesters;
use Illuminate\Support\Facades\DB;

/**
 * Tutor-scoped grade reports: GPA, debtors, summary, single-student grades.
 * Yii2 parity: api/modules/ver1/tutor/controllers/GradeController.php
 *   - actionGpa, actionDebtors, actionStudent, actionSummary
 */
class GradeReportService
{
    use ResolvesGroupSemesters;

    private const STUDENT_STATUS_STUDIED = 'STUDENT_STATUS_STUDIED';

    /**
     * Mandatory subject type marker used by the Yii2 debtors query.
     */
    private const MANDATORY_SUBJECT_TYPE = '11';

    public function __construct(private readonly GroupService $groups)
    {
    }

    /**
     * GPA ro'yxati. Yii2: actionGpa.
     *
     * @return array<string, mixed>
     */
    public function gpa(int $employeeId, array $filters): array
    {
        $requestedGroupId = isset($filters['group_id']) ? (int) $filters['group_id'] : null;
        $forcedSemester = $filters['semester'] ?? null;

        $groupIds = $this->resolveGroupScope($employeeId, $requestedGroupId);

        $semesterCode = $forcedSemester ?: $this->resolveGlobalSemester();
        $educationYear = $this->educationYearFromSemester($semesterCode);

        if (empty($groupIds)) {
            return [
                'gpa_records'    => [],
                'group_id'       => $requestedGroupId,
                'group_ids'      => [],
                'semester'       => $semesterCode,
                'education_year' => $educationYear,
                'total_students' => 0,
            ];
        }

        $query = DB::table('e_student_gpa as gpa')
            ->leftJoin('e_student as st', 'st.id', '=', 'gpa._student')
            ->leftJoin('e_group as g', 'g.id', '=', 'gpa._group')
            ->whereIn('gpa._group', $groupIds)
            ->orderByDesc('gpa.gpa')
            ->select([
                'gpa.gpa',
                'gpa.credit_sum',
                'gpa.subjects',
                'gpa._education_year as education_year',
                'st.id as student_id',
                'st.first_name',
                'st.second_name',
                'st.third_name',
                'st.student_id_number',
                'st.image',
                'g.id as group_id',
                'g.name as group_name',
            ]);

        if ($educationYear !== null) {
            $query->where('gpa._education_year', $educationYear);
        }

        $rows = $query->get();

        $records = $rows->map(fn($row) => [
            'student'        => [
                'id'                => (int) $row->student_id,
                'full_name'         => $this->fullName($row),
                'student_id_number' => $row->student_id_number,
                'image'             => $row->image,
            ],
            'group'          => $row->group_id ? [
                'id'   => (int) $row->group_id,
                'name' => $row->group_name,
            ] : null,
            'gpa'            => $row->gpa !== null ? round((float) $row->gpa, 2) : null,
            'credit_sum'     => $row->credit_sum !== null ? (float) $row->credit_sum : null,
            'total_subjects' => $row->subjects !== null ? (int) $row->subjects : 0,
            'semester'       => $semesterCode,
            'education_year' => $row->education_year,
            'status'         => $row->gpa !== null ? $this->gpaStatus((float) $row->gpa) : null,
        ])->all();

        return [
            'gpa_records'    => $records,
            'group_id'       => $requestedGroupId,
            'group_ids'      => array_map('intval', $groupIds),
            'semester'       => $semesterCode,
            'education_year' => $educationYear,
            'total_students' => count($records),
        ];
    }

    /**
     * Qarzdor talabalar (e_academic_record yo'q bo'lgan majburiy fanlar). Yii2: actionDebtors.
     *
     * @return array<string, mixed>
     */
    public function debtors(int $employeeId, array $filters): array
    {
        $educationYear = (string) $filters['education_year'];
        $requestedGroupId = isset($filters['group_id']) ? (int) $filters['group_id'] : null;
        $curriculumId = isset($filters['curriculum']) ? (int) $filters['curriculum'] : null;
        $semester = $filters['semester'] ?? null;
        $subjectId = isset($filters['subject_id']) ? (int) $filters['subject_id'] : null;
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));

        $groupIds = $this->resolveGroupScope($employeeId, $requestedGroupId);

        $emptyResponse = [
            'debtors'        => [],
            'group_id'       => $requestedGroupId,
            'semester'       => $semester,
            'education_year' => $educationYear,
            'curriculum'     => $curriculumId,
            'subject_id'     => $subjectId,
            'pagination'     => $this->buildPagination(0, $page, $perPage),
        ];

        if (empty($groupIds)) {
            return $emptyResponse;
        }

        $query = DB::table('e_student_meta as sm')
            ->join('e_student as s', 's.id', '=', 'sm._student')
            ->join('e_group as g', 'g.id', '=', 'sm._group')
            ->join('e_curriculum_subject as cs', function ($join) {
                $join->on('cs._curriculum', '=', 'sm._curriculum')
                    ->on('cs._semester', '=', 'sm._semestr')
                    ->where('cs.active', '=', true);
            })
            ->join('e_subject as subj', 'subj.id', '=', 'cs._subject')
            ->leftJoin('e_specialty as spec', 'spec.id', '=', 'sm._specialty')
            ->leftJoin('e_academic_record as ar', function ($join) {
                $join->on('ar._curriculum', '=', 'cs._curriculum')
                    ->on('ar._subject', '=', 'cs._subject')
                    ->on('ar._semester', '=', 'cs._semester')
                    ->on('ar._student', '=', 'sm._student')
                    ->where('ar.active', '=', true);
            })
            ->where('cs._subject_type', self::MANDATORY_SUBJECT_TYPE)
            ->where('sm.active', true)
            ->whereIn('sm._group', $groupIds)
            ->where('sm._education_year', $educationYear)
            ->whereNull('ar.id');

        if ($curriculumId !== null) {
            $query->where('sm._curriculum', $curriculumId);
        }
        if ($semester !== null && $semester !== '') {
            $query->where('sm._semestr', $semester);
        }
        if ($subjectId !== null) {
            $query->where('cs._subject', $subjectId);
        }

        $rows = $query
            ->orderBy('sm._student')
            ->orderBy('cs._subject')
            ->select([
                'sm._student as student_id',
                'sm._group as meta_group_id',
                'sm._curriculum as curriculum_id',
                'sm._semestr as semester_code',
                'sm._level as level_code',
                's.first_name',
                's.second_name',
                's.third_name',
                's.student_id_number',
                'g.id as group_id',
                'g.name as group_name',
                'spec.name as specialty_name',
                'cs._subject as subject_id',
                'subj.name as subject_name',
                'cs.credit as subject_credit',
            ])
            ->get();

        $debtorsMap = [];
        foreach ($rows as $row) {
            $studentId = (int) $row->student_id;

            if (!isset($debtorsMap[$studentId])) {
                $debtorsMap[$studentId] = [
                    'student'        => [
                        'id'                => $studentId,
                        'full_name'         => $this->fullName($row),
                        'student_id_number' => $row->student_id_number,
                    ],
                    'group'          => $row->group_id ? [
                        'id'   => (int) $row->group_id,
                        'name' => $row->group_name,
                    ] : null,
                    'level'          => $row->level_code,
                    'specialty'      => $row->specialty_name,
                    'education_year' => $educationYear,
                    'semester'       => $row->semester_code,
                    'subjects'       => [],
                ];
            }

            $debtorsMap[$studentId]['subjects'][] = [
                'id'     => (int) $row->subject_id,
                'name'   => $row->subject_name,
                'credit' => $row->subject_credit !== null ? (float) $row->subject_credit : null,
            ];
        }

        $allDebtors = array_values($debtorsMap);
        $totalCount = count($allDebtors);
        $paginated = array_slice($allDebtors, ($page - 1) * $perPage, $perPage);

        return [
            'debtors'        => $paginated,
            'group_id'       => $requestedGroupId,
            'semester'       => $semester,
            'education_year' => $educationYear,
            'curriculum'     => $curriculumId,
            'subject_id'     => $subjectId,
            'pagination'     => $this->buildPagination($totalCount, $page, $perPage),
        ];
    }

    /**
     * Bitta talabaning fanlar bo'yicha baholari. Yii2: actionStudent.
     *
     * @return array<string, mixed>
     */
    public function studentGrades(int $employeeId, int $studentId, ?string $semester = null): array
    {
        $groupIds = $this->groups->teacherGroupIds($employeeId);

        if (empty($groupIds)) {
            abort(403, 'Sizga biriktirilgan guruh topilmadi');
        }

        $semesterCode = $semester ?: $this->resolveGlobalSemester();

        $query = DB::table('e_student_subject as ss')
            ->leftJoin('e_student as st', 'st.id', '=', 'ss._student')
            ->leftJoin('e_subject as sub', 'sub.id', '=', 'ss._subject')
            ->leftJoin('e_academic_record as ar', function ($join) {
                $join->on('ar._curriculum', '=', 'ss._curriculum')
                    ->on('ar._subject', '=', 'ss._subject')
                    ->on('ar._semester', '=', 'ss._semester')
                    ->on('ar._student', '=', 'ss._student');
            })
            ->where('ss._student', $studentId)
            ->whereIn('ss._group', $groupIds)
            ->orderBy('ss.id')
            ->select([
                'ss._student as student_id',
                'st.first_name',
                'st.second_name',
                'st.third_name',
                'st.student_id_number',
                'sub.name as subject_name',
                'ar.grade',
                'ar.total_point',
                'ar.credit',
            ]);

        if ($semesterCode !== null) {
            $query->where('ss._semester', $semesterCode);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            abort(403, 'Talaba topilmadi yoki sizga tegishli guruhda emas');
        }

        $first = $rows->first();

        $grades = $rows->map(fn($row) => [
            'subject'     => $row->subject_name,
            'grade'       => $row->grade,
            'total_point' => $row->total_point !== null ? (float) $row->total_point : null,
            'credit'      => $row->credit !== null ? (float) $row->credit : null,
        ])->all();

        return [
            'student'  => [
                'id'                => (int) $first->student_id,
                'full_name'         => $this->fullName($first),
                'student_id_number' => $first->student_id_number,
            ],
            'grades'   => $grades,
            'semester' => $semesterCode,
        ];
    }

    /**
     * Jamlanma qaydnoma — talaba × fan × baho jadvali + o'rtacha. Yii2: actionSummary.
     *
     * @return array<string, mixed>
     */
    public function summary(int $employeeId, array $filters): array
    {
        $requestedGroupId = isset($filters['group_id']) ? (int) $filters['group_id'] : null;
        $forcedSemester = $filters['semester'] ?? null;

        $groupIds = $this->resolveGroupScope($employeeId, $requestedGroupId);

        if (empty($groupIds)) {
            return [
                'summary'         => [],
                'group_id'        => $requestedGroupId,
                'group_ids'       => [],
                'semester'        => $forcedSemester,
                'group_semesters' => [],
                'total_students'  => 0,
            ];
        }

        $groupSemesters = $this->resolveGroupSemesters($groupIds, $forcedSemester);

        if (empty($groupSemesters)) {
            return [
                'summary'         => [],
                'group_id'        => $requestedGroupId,
                'group_ids'       => array_map('intval', $groupIds),
                'semester'        => $forcedSemester,
                'group_semesters' => [],
                'total_students'  => 0,
            ];
        }

        $rows = DB::table('e_student_subject as ss')
            ->leftJoin('e_student as st', 'st.id', '=', 'ss._student')
            ->leftJoin('e_subject as sub', 'sub.id', '=', 'ss._subject')
            ->leftJoin('e_group as g', 'g.id', '=', 'ss._group')
            ->leftJoin('e_academic_record as ar', function ($join) {
                $join->on('ar._curriculum', '=', 'ss._curriculum')
                    ->on('ar._subject', '=', 'ss._subject')
                    ->on('ar._semester', '=', 'ss._semester')
                    ->on('ar._student', '=', 'ss._student');
            })
            ->where(function ($q) use ($groupSemesters) {
                foreach ($groupSemesters as $gid => $sem) {
                    $q->orWhere(function ($qq) use ($gid, $sem) {
                        $qq->where('ss._group', (int) $gid)->where('ss._semester', $sem);
                    });
                }
            })
            ->orderBy('ss._student')
            ->select([
                'ss._student as student_id',
                'st.first_name',
                'st.second_name',
                'st.third_name',
                'st.student_id_number',
                'st.image',
                'g.id as group_id',
                'g.name as group_name',
                'sub.name as subject_name',
                'ar.grade',
                'ar.total_point',
                'ar.credit',
            ])
            ->get();

        $studentData = [];
        foreach ($rows as $row) {
            $studentId = (int) $row->student_id;

            if (!isset($studentData[$studentId])) {
                $studentData[$studentId] = [
                    'student'        => [
                        'id'                => $studentId,
                        'full_name'         => $this->fullName($row),
                        'student_id_number' => $row->student_id_number,
                        'image'             => $row->image,
                    ],
                    'group'          => $row->group_id ? [
                        'id'   => (int) $row->group_id,
                        'name' => $row->group_name,
                    ] : null,
                    'subjects'       => [],
                    'total_subjects' => 0,
                    'total_credit'   => 0.0,
                    'average_grade'  => 0,
                    'status'         => '',
                ];
            }

            $credit = $row->credit !== null ? (float) $row->credit : 0.0;
            $totalPoint = $row->total_point !== null ? (float) $row->total_point : 0.0;

            $studentData[$studentId]['subjects'][] = [
                'subject'     => $row->subject_name,
                'grade'       => $row->grade,
                'total_point' => $totalPoint,
                'credit'      => $credit,
            ];
            $studentData[$studentId]['total_subjects']++;
            $studentData[$studentId]['total_credit'] += $credit;
        }

        foreach ($studentData as &$data) {
            if ($data['total_subjects'] > 0) {
                $totalPoints = array_sum(array_column($data['subjects'], 'total_point'));
                $data['average_grade'] = round($totalPoints / $data['total_subjects'], 2);
                $data['status'] = $this->gradeStatus($data['average_grade']);
            }
        }
        unset($data);

        $semesterForResponse = $forcedSemester;
        if ($semesterForResponse === null) {
            $unique = array_unique(array_values($groupSemesters));
            if (count($unique) === 1) {
                $semesterForResponse = $unique[0];
            }
        }

        return [
            'summary'         => array_values($studentData),
            'group_id'        => $requestedGroupId,
            'group_ids'       => array_map('intval', $groupIds),
            'semester'        => $semesterForResponse,
            'group_semesters' => $groupSemesters,
            'total_students'  => count($studentData),
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
            abort(403, "Ushbu guruhga kirish huquqingiz yo'q");
        }

        return [$requestedGroupId];
    }

    /**
     * Extracts "YYYY-YYYY" prefix from a semester code like "2024-2025-1".
     */
    private function educationYearFromSemester(?string $semesterCode): ?string
    {
        if ($semesterCode === null || $semesterCode === '') {
            return null;
        }

        $parts = explode('-', $semesterCode);
        if (count($parts) >= 2) {
            return $parts[0] . '-' . $parts[1];
        }

        return null;
    }

    private function fullName(object $row): string
    {
        return trim(($row->second_name ?? '') . ' ' . ($row->first_name ?? '') . ' ' . ($row->third_name ?? ''));
    }

    private function gpaStatus(float $gpa): string
    {
        return match (true) {
            $gpa >= 4.5 => "A'lo",
            $gpa >= 4.0 => 'Yaxshi',
            $gpa >= 3.0 => 'Qoniqarli',
            default     => 'Qoniqarsiz',
        };
    }

    private function gradeStatus(float $average): string
    {
        return match (true) {
            $average >= 86 => "A'lo",
            $average >= 71 => 'Yaxshi',
            $average >= 55 => 'Qoniqarli',
            default        => 'Qoniqarsiz',
        };
    }

    /**
     * @return array<string, int>
     */
    private function buildPagination(int $totalCount, int $page, int $perPage): array
    {
        return [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total_count'  => $totalCount,
            'total_pages'  => $perPage > 0 ? (int) ceil($totalCount / $perPage) : 0,
        ];
    }
}
