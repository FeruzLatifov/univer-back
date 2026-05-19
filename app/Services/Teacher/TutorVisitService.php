<?php

namespace App\Services\Teacher;

use App\Models\EStudent;
use App\Models\ETutorVisit;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TutorVisitService
{
    public function __construct(private readonly GroupService $groups)
    {
    }

    /**
     * Visits across all students in the teacher's scope (kurator + subject teacher).
     */
    public function listForTeacher(int $employeeId, array $filters = []): LengthAwarePaginator
    {
        $groupIds = $this->groups->teacherGroupIds($employeeId);

        if (empty($groupIds)) {
            return new LengthAwarePaginator([], 0, (int) ($filters['per_page'] ?? 20));
        }

        $studentIds = DB::table('e_student_meta')
            ->whereIn('_group', $groupIds)
            ->where('active', true)
            ->pluck('_student')
            ->unique()
            ->all();

        if (empty($studentIds)) {
            return new LengthAwarePaginator([], 0, (int) ($filters['per_page'] ?? 20));
        }

        $query = ETutorVisit::query()
            ->whereIn('_student', $studentIds)
            ->where('active', true)
            ->with('student:id,first_name,second_name,third_name,student_id_number,phone_number')
            ->orderByDesc('created_at');

        if (!empty($filters['student_id'])) {
            $query->where('_student', (int) $filters['student_id']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('second_name', 'ilike', "%{$search}%")
                    ->orWhere('third_name', 'ilike', "%{$search}%")
                    ->orWhere('student_id_number', 'ilike', "%{$search}%");
            });
        }

        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * Visit history for a specific student in the teacher's scope.
     */
    public function listForStudent(int $employeeId, int $studentId): array
    {
        $this->assertStudentInScope($employeeId, $studentId);

        return ETutorVisit::query()
            ->where('_student', $studentId)
            ->where('active', true)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Create a new visit; mirrors Yii2 ETutorVisit::createVisit + beforeSave + afterSave behaviour.
     */
    public function create(int $employeeId, int $studentId, array $data): ETutorVisit
    {
        $this->assertStudentInScope($employeeId, $studentId);

        return DB::transaction(function () use ($employeeId, $studentId, $data) {
            $visit = ETutorVisit::create([
                '_student'               => $studentId,
                '_tutor'                 => $employeeId,
                '_student_living_status' => $this->nullIfEmpty($data['_student_living_status'] ?? null),
                '_accommodation'         => $this->nullIfEmpty($data['_accommodation'] ?? null),
                '_current_province'      => $this->nullIfEmpty($data['_current_province'] ?? null),
                '_current_district'      => $this->nullIfEmpty($data['_current_district'] ?? null),
                '_current_terrain'       => $this->nullIfEmpty($data['_current_terrain'] ?? null),
                'current_address'        => $this->nullIfEmpty($data['current_address'] ?? null),
                'geolocation'            => $this->nullIfEmpty($data['geolocation'] ?? null),
                'roommate_count'         => isset($data['roommate_count']) ? (int) $data['roommate_count'] : null,
                'comment'                => $data['comment'] ?? null,
                'active'                 => true,
                'position'               => 0,
            ]);

            $this->propagateToStudent($visit);

            return $visit->load('student');
        });
    }

    /**
     * Mirror of Yii2 ETutorVisit::updateStudentAddress so the canonical student
     * address stays in sync with the latest visit.
     */
    private function propagateToStudent(ETutorVisit $visit): void
    {
        $student = EStudent::find($visit->_student);

        if (!$student) {
            return;
        }

        $updates = array_filter([
            '_student_living_status' => $visit->_student_living_status,
            '_accommodation'         => $visit->_accommodation,
            '_current_province'      => $visit->_current_province,
            '_current_district'      => $visit->_current_district,
            '_current_terrain'       => $visit->_current_terrain,
            'current_address'        => $visit->current_address,
            'geo_location'           => $visit->geolocation,
            'roommate_count'         => $visit->roommate_count,
        ], fn($v) => $v !== null);

        if (empty($updates)) {
            return;
        }

        // Use direct query to bypass mass-assignment guards on legacy fields.
        DB::table('e_student')->where('id', $student->id)->update($updates);
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
            abort(404, 'Talaba topilmadi yoki sizga tegishli emas');
        }
    }

    private function nullIfEmpty($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
