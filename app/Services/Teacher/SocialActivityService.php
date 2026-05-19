<?php

namespace App\Services\Teacher;

use App\Models\EStudentSadApplication;
use App\Models\EStudentSadCategory;
use Illuminate\Support\Facades\DB;

/**
 * Social Activity (SAD) — read-only foundation.
 * Yii2 parity: api\modules\ver1\tutor\controllers\SocialActivityController
 * actions ported: directions, applications, application.
 * Write/approve/calculate-scores deferred to a later phase.
 */
class SocialActivityService
{
    public function __construct(private readonly GroupService $groups)
    {
    }

    /**
     * Directions tree: direction → categories → criteria.
     *
     * @return array<int, array<string, mixed>>
     */
    public function directions(): array
    {
        $directions = EStudentSadCategory::query()
            ->where('parent_id', 0)
            ->where('active', true)
            ->with(['children.criteria'])
            ->orderBy('position')
            ->get();

        return $directions->map(fn(EStudentSadCategory $direction) => [
            'id'         => $direction->id,
            'name'       => $direction->name,
            'position'   => $direction->position,
            'categories' => $direction->children->map(fn(EStudentSadCategory $cat) => [
                'id'          => $cat->id,
                'name'        => $cat->name,
                'max_point'   => $cat->max_point,
                'input_type'  => $cat->input_type,
                'select_type' => $cat->select_type,
                'input_by'    => $cat->input_by,
                'is_strict'   => (bool) $cat->is_strict,
                'position'    => $cat->position,
                'criteria'    => $cat->criteria->map(fn($c) => [
                    'id'        => $c->id,
                    'name'      => $c->name,
                    'point'     => $c->point,
                    'min_value' => $c->min_value,
                    'max_value' => $c->max_value,
                    'input_by'  => $c->input_by,
                    'position'  => $c->position,
                ])->all(),
            ])->all(),
        ])->all();
    }

    /**
     * Applications visible to the tutor (only students in his groups).
     *
     * @return array<string, mixed>
     */
    public function applications(int $employeeId, array $filters = []): array
    {
        $groupIds = $this->groups->teacherGroupIds($employeeId);

        if (empty($groupIds)) {
            return ['applications' => [], 'total_count' => 0];
        }

        $studentIds = DB::table('e_student_meta')
            ->whereIn('_group', $groupIds)
            ->where('active', true)
            ->distinct()
            ->pluck('_student')
            ->all();

        if (empty($studentIds)) {
            return ['applications' => [], 'total_count' => 0];
        }

        $query = EStudentSadApplication::query()
            ->whereIn('_student', $studentIds)
            ->with(['student:id,first_name,second_name,third_name,student_id_number'])
            ->orderByDesc('created_at');

        if (!empty($filters['student_id'])) {
            $query->where('_student', (int) $filters['student_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        if (!empty($filters['group_id'])) {
            $studentsInGroup = DB::table('e_student_meta')
                ->where('_group', (int) $filters['group_id'])
                ->where('active', true)
                ->pluck('_student')
                ->all();
            $query->whereIn('_student', $studentsInGroup);
        }

        $applications = $query->get();

        return [
            'applications' => $applications->map(fn(EStudentSadApplication $a) => $this->formatListItem($a))->all(),
            'total_count'  => $applications->count(),
        ];
    }

    /**
     * Full application detail with criteria list.
     *
     * @return array<string, mixed>
     */
    public function application(int $employeeId, int $applicationId): array
    {
        $application = EStudentSadApplication::query()
            ->with([
                'student:id,first_name,second_name,third_name,student_id_number',
                'applicationCriteria.criteria.category',
            ])
            ->findOrFail($applicationId);

        $this->assertStudentInScope($employeeId, (int) $application->_student);

        $criteriaItems = $application->applicationCriteria->map(function ($ac) {
            $file = $ac->basis_file;
            if (is_string($file) && $file !== '') {
                $decoded = json_decode($file, true);
                if (is_array($decoded)) {
                    $file = $decoded;
                }
            }

            return [
                'id'            => $ac->id,
                'criteria_id'   => $ac->_criteria,
                'criteria_name' => $ac->criteria?->name,
                'category_id'   => $ac->criteria?->category?->id,
                'category_name' => $ac->criteria?->category?->name,
                'point'         => $ac->point,
                'basis'         => $ac->basis,
                'basis_file'    => $file,
                'status'        => $ac->status,
                'reject_comment' => $ac->reject_comment,
            ];
        })->all();

        return [
            'application' => array_merge(
                $this->formatListItem($application),
                ['criteria' => $criteriaItems]
            ),
        ];
    }

    private function formatListItem(EStudentSadApplication $a): array
    {
        $student = $a->student;

        return [
            'id'               => $a->id,
            'name'             => $a->name,
            'description'      => $a->description,
            'application_date' => $a->application_date?->format('Y-m-d'),
            'total_point'      => $a->total_point,
            'status'           => $a->status,
            'education_year'   => $a->_education_year,
            'created_at'       => $a->created_at?->toIso8601String(),
            'student'          => $student ? [
                'id'                => $student->id,
                'full_name'         => trim($student->second_name . ' ' . $student->first_name . ' ' . $student->third_name),
                'student_id_number' => $student->student_id_number,
            ] : null,
        ];
    }

    private function assertStudentInScope(int $employeeId, int $studentId): void
    {
        $groupIds = $this->groups->teacherGroupIds($employeeId);

        if (empty($groupIds)) {
            abort(403, 'Sizga biriktirilgan guruh topilmadi');
        }

        $exists = DB::table('e_student_meta')
            ->where('_student', $studentId)
            ->whereIn('_group', $groupIds)
            ->where('active', true)
            ->exists();

        if (!$exists) {
            abort(404, 'Ariza topilmadi yoki sizga tegishli emas');
        }
    }
}
