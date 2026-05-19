<?php

namespace App\Services\Reference;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Read-only lookups for cascading selects / classifiers.
 * Port of Yii2 api\modules\ver1\tutor\controllers\ReferenceController.
 */
class ReferenceService
{
    private const CACHE_TTL_SECONDS = 600;

    /**
     * @return array<int, array{code: string, name: string|null}>
     */
    public function countries(): array
    {
        return $this->cached('countries', fn() => $this->mapCodeName(
            DB::table('h_country')->where('active', true)->orderBy('name')->get(['code', 'name'])
        ));
    }

    /**
     * Top-level Soato entries (length(code) = 4 → provinces).
     *
     * @return array<int, array{code: string, name: string|null}>
     */
    public function provinces(): array
    {
        return $this->cached('provinces', fn() => $this->mapCodeName(
            DB::table('h_soato')
                ->where('active', true)
                ->whereRaw('length(code) = 4')
                ->orderBy('position')
                ->orderBy('code')
                ->get(['code', 'name'])
        ));
    }

    /**
     * @return array<int, array{code: string, name: string|null}>
     */
    public function districts(string $provinceCode): array
    {
        return $this->cached("districts:{$provinceCode}", fn() => $this->mapCodeName(
            DB::table('h_soato')
                ->where('active', true)
                ->where('_parent', $provinceCode)
                ->orderBy('position')
                ->orderBy('code')
                ->get(['code', 'name'])
        ));
    }

    /**
     * @return array<int, array{code: string, name: string|null}>
     */
    public function terrains(string $districtCode): array
    {
        return $this->cached("terrains:{$districtCode}", fn() => $this->mapCodeName(
            DB::table('h_terrain')
                ->where('active', true)
                ->where('_soato', $districtCode)
                ->orderBy('name')
                ->get(['code', 'name'])
        ));
    }

    /**
     * @return array<int, array{code: int, name: string|null}>
     */
    public function studentLivingStatuses(): array
    {
        return $this->cached('student_living_statuses', fn() => $this->mapCodeNameInt(
            DB::table('h_student_living_status')->where('active', true)->orderBy('position')->get(['code', 'name'])
        ));
    }

    /**
     * @return array<int, array{code: string, name: string|null}>
     */
    public function accommodations(): array
    {
        return $this->cached('accommodations', fn() => $this->mapCodeName(
            DB::table('h_accommodation')->where('active', true)->orderBy('position')->get(['code', 'name'])
        ));
    }

    /**
     * @return array<int, array{code: int, name: string|null}>
     */
    public function studentRoommateTypes(): array
    {
        return $this->cached('student_roommate_types', fn() => $this->mapCodeNameInt(
            DB::table('h_student_roommate_type')->where('active', true)->orderBy('position')->get(['code', 'name'])
        ));
    }

    /**
     * @return array<int, array{code: string, name: string|null}>
     */
    public function studentStatuses(): array
    {
        return $this->cached('student_statuses', fn() => $this->mapCodeName(
            DB::table('h_student_status')->where('active', true)->orderBy('position')->get(['code', 'name'])
        ));
    }

    /**
     * @return array<int, array{code: string, name: string|null, current_status: bool}>
     */
    public function educationYears(): array
    {
        return $this->cached('education_years', function () {
            return DB::table('e_education_year')
                ->where('active', true)
                ->orderByDesc('code')
                ->get(['code', 'name', 'current_status'])
                ->map(fn($row) => [
                    'code'           => (string) $row->code,
                    'name'           => $row->name,
                    'current_status' => (bool) $row->current_status,
                ])
                ->all();
        });
    }

    /**
     * @return array<int, array{id: int, code: string, name: string|null}>
     */
    public function specialties(): array
    {
        return $this->cached('specialties', function () {
            return DB::table('e_specialty')
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name'])
                ->map(fn($row) => [
                    'id'   => (int) $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                ])
                ->all();
        });
    }

    /**
     * Subjects, optionally filtered by group (via curriculum) or curriculum+semester pair.
     *
     * @param string|null $yearSemester  Format "YYYY-NN" → only the semester part is used.
     * @return array<int, array{id: int, code: string|null, name: string|null}>
     */
    public function subjects(?int $groupId = null, ?string $yearSemester = null): array
    {
        $cacheKey = 'subjects:' . ($groupId ?? '*') . ':' . ($yearSemester ?? '*');

        return $this->cached($cacheKey, function () use ($groupId, $yearSemester) {
            $curriculumId = null;
            if ($groupId !== null) {
                $curriculumId = DB::table('e_group')
                    ->where('id', $groupId)
                    ->where('active', true)
                    ->value('_curriculum');
                $curriculumId = $curriculumId !== null ? (int) $curriculumId : null;
            }

            $semesterPart = null;
            if ($yearSemester !== null && $yearSemester !== '') {
                $parts = explode('-', $yearSemester);
                $semesterPart = $parts[1] ?? null;
            }

            if ($curriculumId !== null || $semesterPart !== null) {
                $subjectIds = DB::table('e_curriculum_subject')
                    ->where('active', true)
                    ->when($curriculumId, fn($q) => $q->where('_curriculum', $curriculumId))
                    ->when($semesterPart, fn($q) => $q->where('_semester', $semesterPart))
                    ->distinct()
                    ->pluck('_subject');

                if ($subjectIds->isEmpty()) {
                    return [];
                }

                $rows = DB::table('e_subject')
                    ->whereIn('id', $subjectIds)
                    ->where('active', true)
                    ->orderBy('name')
                    ->get(['id', 'code', 'name']);
            } else {
                $rows = DB::table('e_subject')
                    ->where('active', true)
                    ->orderBy('name')
                    ->get(['id', 'code', 'name']);
            }

            return $rows->map(fn($row) => [
                'id'   => (int) $row->id,
                'code' => $row->code,
                'name' => $row->name,
            ])->all();
        });
    }

    /**
     * Semesters for a given curriculum (or via group → curriculum).
     *
     * @return array<int, array<string, mixed>>
     */
    public function semesters(?int $groupId = null, ?int $curriculumId = null): array
    {
        $resolvedCurriculum = $curriculumId;

        if ($resolvedCurriculum === null && $groupId !== null) {
            $resolvedCurriculum = DB::table('e_group')
                ->where('id', $groupId)
                ->where('active', true)
                ->value('_curriculum');
            $resolvedCurriculum = $resolvedCurriculum !== null ? (int) $resolvedCurriculum : null;
        }

        if ($resolvedCurriculum === null) {
            return [];
        }

        $cacheKey = "semesters:curriculum:{$resolvedCurriculum}";

        return $this->cached($cacheKey, function () use ($resolvedCurriculum) {
            $rows = DB::table('h_semestr as s')
                ->leftJoin('e_education_year as ey', 'ey.code', '=', 's._education_year')
                ->leftJoin('h_course as c', 'c.code', '=', 's._level')
                ->where('s._curriculum', $resolvedCurriculum)
                ->where('s.active', true)
                ->orderBy('s.position')
                ->select([
                    's.code',
                    's.name',
                    's._education_year',
                    'ey.name as education_year_name',
                    'c.name as level_name',
                ])
                ->get();

            return $rows->map(fn($row) => [
                'code'           => $row->_education_year . '-' . $row->code,
                'name'           => sprintf(
                    '%s / %s / %s',
                    $row->education_year_name ?: '--',
                    $row->level_name ?: '--',
                    $row->name ?: '--'
                ),
                'education_year' => $row->_education_year,
                'semester_code'  => $row->code,
            ])->all();
        });
    }

    /**
     * @return array<int, array{code: string, name: string|null}>
     */
    private function mapCodeName(\Illuminate\Support\Collection $rows): array
    {
        return $rows->map(fn($row) => [
            'code' => (string) $row->code,
            'name' => $row->name,
        ])->all();
    }

    /**
     * @return array<int, array{code: int, name: string|null}>
     */
    private function mapCodeNameInt(\Illuminate\Support\Collection $rows): array
    {
        return $rows->map(fn($row) => [
            'code' => (int) $row->code,
            'name' => $row->name,
        ])->all();
    }

    /**
     * @template T
     * @param callable():T $callback
     * @return T
     */
    private function cached(string $key, callable $callback): mixed
    {
        return Cache::remember('reference:' . $key, self::CACHE_TTL_SECONDS, $callback);
    }
}
