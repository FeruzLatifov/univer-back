<?php

namespace App\Services\Teacher;

use App\Models\EStudent;
use App\Models\EStudentMeta;
use Illuminate\Support\Facades\DB;

class StudentService
{
    /**
     * Fields a tutor is allowed to update via the API.
     * Mirrors api/modules/ver1/tutor/requests/student/StudentUpdateForm.php in Yii2.
     */
    private const UPDATABLE_FIELDS = [
        'phone',
        'email',
        'home_address',
        '_country',
        '_province',
        '_district',
        '_terrain',
        '_current_terrain',
        '_current_province',
        '_current_district',
        'current_address',
        'roommate_count',
        '_student_roommate_type',
        '_student_living_status',
        'geo_location',
        'parent_phone',
        'person_phone',
    ];

    public function __construct(private readonly GroupService $groups)
    {
    }

    public function profile(int $employeeId, int $studentId): array
    {
        $student = $this->loadOwnedStudent($employeeId, $studentId);
        $meta = EStudentMeta::with(['group', 'department', 'specialty', 'educationType', 'educationForm', 'paymentForm'])
            ->where('_student', $studentId)
            ->where('active', true)
            ->orderByDesc('id')
            ->first();

        return [
            'student' => $this->formatStudent($student),
            'meta'    => $meta ? $this->formatMeta($meta) : null,
        ];
    }

    public function update(int $employeeId, int $studentId, array $payload): EStudent
    {
        $student = $this->loadOwnedStudent($employeeId, $studentId);

        foreach (self::UPDATABLE_FIELDS as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] !== null) {
                $student->setAttribute($field, $payload[$field]);
            }
        }

        $student->save();

        return $student->fresh();
    }

    /**
     * Group/department/specialty/year history for a student (from e_student_meta).
     */
    public function history(int $employeeId, int $studentId): array
    {
        $this->loadOwnedStudent($employeeId, $studentId);

        $rows = EStudentMeta::query()
            ->where('_student', $studentId)
            ->with(['group', 'department', 'specialty', 'educationType', 'educationForm', 'paymentForm'])
            ->orderByDesc('id')
            ->get();

        return $rows->map(fn($meta) => $this->formatMeta($meta) + [
            'active'     => (bool) $meta->active,
            'created_at' => $meta->created_at?->toIso8601String(),
        ])->all();
    }

    public static function fields(): array
    {
        return self::UPDATABLE_FIELDS;
    }

    private function loadOwnedStudent(int $employeeId, int $studentId): EStudent
    {
        $groupIds = $this->groups->teacherGroupIds($employeeId);

        if (empty($groupIds)) {
            abort(404, 'Talaba topilmadi yoki sizga tegishli emas');
        }

        $exists = DB::table('e_student_meta')
            ->where('_student', $studentId)
            ->whereIn('_group', $groupIds)
            ->where('active', true)
            ->exists();

        if (!$exists) {
            abort(404, 'Talaba topilmadi yoki sizga tegishli emas');
        }

        return EStudent::findOrFail($studentId);
    }

    private function formatStudent(EStudent $s): array
    {
        return [
            'id'                       => $s->id,
            'first_name'               => $s->first_name,
            'second_name'              => $s->second_name,
            'third_name'               => $s->third_name,
            'full_name'                => trim($s->first_name . ' ' . $s->second_name . ' ' . $s->third_name),
            'student_id_number'        => $s->student_id_number,
            'passport_number'          => $s->passport_number,
            'birth_date'               => $s->birth_date?->format('Y-m-d'),
            'gender'                   => $s->_gender,
            'image'                    => $s->image,
            'phone'                    => $s->phone,
            'email'                    => $s->email,
            'parent_phone'             => $s->parent_phone,
            'person_phone'             => $s->person_phone,
            'home_address'             => $s->home_address,
            'current_address'          => $s->current_address,
            '_country'                 => $s->_country,
            '_province'                => $s->_province,
            '_district'                => $s->_district,
            '_terrain'                 => $s->_terrain,
            '_current_province'        => $s->_current_province,
            '_current_district'        => $s->_current_district,
            '_current_terrain'         => $s->_current_terrain,
            '_student_living_status'   => $s->_student_living_status,
            '_accommodation'           => $s->_accommodation,
            '_student_roommate_type'   => $s->_student_roommate_type,
            'roommate_count'           => $s->roommate_count,
            'geo_location'             => $s->geo_location,
            '_social_category'         => $s->_social_category,
        ];
    }

    private function formatMeta(EStudentMeta $m): array
    {
        return [
            'id'              => $m->id,
            'group'           => $m->group ? ['id' => $m->group->id, 'name' => $m->group->name] : null,
            'department'      => $m->department ? ['id' => $m->department->id, 'name' => $m->department->name] : null,
            'specialty'       => $m->specialty ? ['id' => $m->specialty->id, 'name' => $m->specialty->name] : null,
            'education_year'  => $m->_education_year,
            'education_type'  => $m->educationType ? ['code' => $m->educationType->code, 'name' => $m->educationType->name] : null,
            'education_form'  => $m->educationForm ? ['code' => $m->educationForm->code, 'name' => $m->educationForm->name] : null,
            'payment_form'    => $m->paymentForm ? ['code' => $m->paymentForm->code, 'name' => $m->paymentForm->name] : null,
            'student_status'  => $m->student_status,
            'level'           => $m->_level,
        ];
    }
}
