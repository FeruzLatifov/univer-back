<?php

namespace App\Services\Teacher;

use App\Models\EGroup;
use App\Models\EStudentMeta;
use App\Models\ESubjectSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GroupService
{
    private const GENDER_MALE = '11';
    private const GENDER_FEMALE = '12';
    private const STUDENT_STATUS_STUDIED = self::STUDENT_STATUS_STUDIED;

    /**
     * Resolve current academic year code like "2025-2026" based on system date.
     * Academic year flips on September 1st.
     */
    public function currentEducationYear(): string
    {
        $now = Carbon::now();
        $startYear = $now->month >= 9 ? $now->year : $now->year - 1;

        return $startYear . '-' . ($startYear + 1);
    }

    /**
     * Groups that the teacher has any relation to:
     * - subject teacher (e_subject_schedule._employee)
     * - kurator/tutor (e_admin_group._admin)
     * Deduplicated by group id.
     *
     * @return array<int>
     */
    public function teacherGroupIds(int $employeeId): array
    {
        $taught = ESubjectSchedule::where('_employee', $employeeId)
            ->where('active', true)
            ->distinct()
            ->pluck('_group')
            ->all();

        $kurator = [];
        if (DB::getSchemaBuilder()->hasTable('e_admin_group')) {
            $kurator = DB::table('e_admin_group')
                ->where('_admin', $employeeId)
                ->pluck('_group')
                ->all();
        }

        return array_values(array_unique(array_filter(array_merge($taught, $kurator))));
    }

    public function list(int $employeeId, array $filters = []): array
    {
        $educationYear = $filters['education_year'] ?? $this->currentEducationYear();
        $groupIds = $this->teacherGroupIds($employeeId);

        if (empty($groupIds)) {
            return ['groups' => [], 'education_year' => $educationYear];
        }

        $query = EGroup::query()
            ->whereIn('id', $groupIds)
            ->where('active', true)
            ->with(['department', 'specialty']);

        if (!empty($filters['group_id'])) {
            $query->where('id', (int) $filters['group_id']);
        }

        if (!empty($filters['faculty_id'])) {
            $query->where('_department', (int) $filters['faculty_id']);
        }

        $groups = $query->get();

        $stats = $this->fetchGroupStats($groups->pluck('id')->all(), $educationYear);

        $payload = $groups->map(function (EGroup $group) use ($stats, $educationYear) {
            $groupStats = $stats[$group->id] ?? ['total' => 0, 'active' => 0];

            return [
                'id'                    => $group->id,
                'name'                  => $group->name,
                'code'                  => $group->code,
                'department'            => $group->department ? [
                    'id'   => $group->department->id,
                    'name' => $group->department->name,
                ] : null,
                'specialty'             => $group->specialty ? [
                    'id'   => $group->specialty->id,
                    'name' => $group->specialty->name,
                ] : null,
                'education_type'        => $group->_education_type,
                'education_form'        => $group->_education_form,
                'education_year'        => $group->_education_year,
                'level'                 => $group->_level,
                'students_count'        => $groupStats['total'],
                'active_students_count' => $groupStats['active'],
            ];
        })->all();

        return [
            'groups'         => $payload,
            'education_year' => $educationYear,
        ];
    }

    public function view(int $employeeId, int $groupId, ?string $educationYear = null): array
    {
        $educationYear = $educationYear ?? $this->currentEducationYear();

        $group = $this->findOwnedGroup($employeeId, $groupId);

        return [
            'group'          => [
                'id'             => $group->id,
                'name'           => $group->name,
                'code'           => $group->code,
                'department'     => $group->department ? [
                    'id'   => $group->department->id,
                    'name' => $group->department->name,
                ] : null,
                'specialty'      => $group->specialty ? [
                    'id'   => $group->specialty->id,
                    'name' => $group->specialty->name,
                ] : null,
                'education_type' => $group->_education_type,
                'education_form' => $group->_education_form,
                'education_year' => $group->_education_year,
                'level'          => $group->_level,
            ],
            'statistics'     => $this->calculateGroupStatistics($groupId, $educationYear),
            'education_year' => $educationYear,
        ];
    }

    public function students(int $employeeId, int $groupId, ?string $educationYear = null, ?string $status = null): array
    {
        $educationYear = $educationYear ?? $this->currentEducationYear();
        $group = $this->findOwnedGroup($employeeId, $groupId);

        $metaQuery = EStudentMeta::query()
            ->where('_group', $groupId)
            ->where('_education_year', $educationYear)
            ->where('active', true);

        if ($status !== null && $status !== '') {
            $metaQuery->where('student_status', $status);
        }

        $metas = $metaQuery->with(['student', 'educationType', 'educationForm', 'paymentForm'])->get();

        $students = $metas->map(function (EStudentMeta $meta) {
            $student = $meta->student;

            if ($student === null) {
                return null;
            }

            return [
                'id'                => $student->id,
                'full_name'         => trim($student->first_name . ' ' . $student->second_name . ' ' . $student->third_name),
                'first_name'        => $student->first_name,
                'second_name'       => $student->second_name,
                'third_name'        => $student->third_name,
                'student_id_number' => $student->student_id_number,
                'image'             => $student->image,
                'gender'            => $student->_gender,
                'birth_date'        => $student->birth_date?->format('Y-m-d'),
                'phone'             => $student->phone_number,
                'email'             => $student->email,
                'student_status'    => $meta->student_status,
                'payment_form'      => $meta->paymentForm ? [
                    'code' => $meta->paymentForm->code,
                    'name' => $meta->paymentForm->name,
                ] : null,
                'education_type'    => $meta->educationType ? [
                    'code' => $meta->educationType->code,
                    'name' => $meta->educationType->name,
                ] : null,
                'education_form'    => $meta->educationForm ? [
                    'code' => $meta->educationForm->code,
                    'name' => $meta->educationForm->name,
                ] : null,
            ];
        })->filter()->values()->all();

        return [
            'students'       => $students,
            'group'          => ['id' => $group->id, 'name' => $group->name],
            'education_year' => $educationYear,
            'total_count'    => count($students),
        ];
    }

    /**
     * @return array<int, array{total:int,active:int}>
     */
    private function fetchGroupStats(array $groupIds, string $educationYear): array
    {
        if (empty($groupIds)) {
            return [];
        }

        $rows = EStudentMeta::query()
            ->whereIn('_group', $groupIds)
            ->where('_education_year', $educationYear)
            ->where('active', true)
            ->selectRaw('_group, COUNT(*) as total, SUM(CASE WHEN student_status = ? THEN 1 ELSE 0 END) as active_count', [self::STUDENT_STATUS_STUDIED])
            ->groupBy('_group')
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row->_group] = [
                'total'  => (int) $row->total,
                'active' => (int) $row->active_count,
            ];
        }

        return $map;
    }

    private function calculateGroupStatistics(int $groupId, string $educationYear): array
    {
        $base = EStudentMeta::query()
            ->where('_group', $groupId)
            ->where('_education_year', $educationYear)
            ->where('active', true);

        $total = (clone $base)->count();
        $active = (clone $base)->where('student_status', self::STUDENT_STATUS_STUDIED)->count();

        $male = (clone $base)
            ->join('e_student', 'e_student.id', '=', 'e_student_meta._student')
            ->where('e_student._gender', self::GENDER_MALE)
            ->count();

        $female = (clone $base)
            ->join('e_student', 'e_student.id', '=', 'e_student_meta._student')
            ->where('e_student._gender', self::GENDER_FEMALE)
            ->count();

        return [
            'total_students'  => $total,
            'active_students' => $active,
            'male_students'   => $male,
            'female_students' => $female,
        ];
    }

    /**
     * Ensure the group is in the teacher's scope; otherwise 404.
     */
    private function findOwnedGroup(int $employeeId, int $groupId): EGroup
    {
        $ownedIds = $this->teacherGroupIds($employeeId);

        if (!in_array($groupId, $ownedIds, true)) {
            abort(404, 'Guruh topilmadi yoki sizga tegishli emas');
        }

        return EGroup::with(['department', 'specialty'])->findOrFail($groupId);
    }
}
