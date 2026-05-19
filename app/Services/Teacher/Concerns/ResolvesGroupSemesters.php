<?php

namespace App\Services\Teacher\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Port of Yii2 api\modules\ver1\tutor\requests\GroupSemesterResolverTrait.
 * Resolves a semester code per group from h_semestr, using curriculum + date window or a forced override.
 */
trait ResolvesGroupSemesters
{
    /**
     * Per-group semester resolution. Returns map of groupId => semester code.
     *
     * @param int[] $groupIds
     * @return array<int, string>
     */
    protected function resolveGroupSemesters(array $groupIds, ?string $forcedSemester, ?string $educationYear = null): array
    {
        if (empty($groupIds)) {
            return [];
        }

        if (!empty($forcedSemester)) {
            return array_combine($groupIds, array_fill(0, count($groupIds), $forcedSemester));
        }

        $curriculumByGroup = DB::table('e_group')
            ->whereIn('id', $groupIds)
            ->whereNotNull('_curriculum')
            ->pluck('_curriculum', 'id')
            ->map(fn($v) => (int) $v)
            ->all();

        $today = Carbon::now()->toDateString();
        $result = [];

        if (!empty($curriculumByGroup)) {
            $curriculumIds = array_values(array_unique($curriculumByGroup));

            $currentQuery = DB::table('h_semestr')
                ->whereIn('_curriculum', $curriculumIds)
                ->where('active', true)
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->orderByDesc('start_date');

            if ($educationYear !== null) {
                $currentQuery->where('_education_year', $educationYear);
            }

            $current = $currentQuery->get(['_curriculum', 'code'])
                ->unique('_curriculum')
                ->mapWithKeys(fn($row) => [(int) $row->_curriculum => (string) $row->code]);

            $missing = array_diff($curriculumIds, $current->keys()->all());
            $latest = collect();

            if (!empty($missing)) {
                $latestQuery = DB::table('h_semestr')
                    ->whereIn('_curriculum', $missing)
                    ->where('active', true)
                    ->orderByDesc('end_date');

                if ($educationYear !== null) {
                    $latestQuery->where('_education_year', $educationYear);
                }

                $latest = $latestQuery->get(['_curriculum', 'code'])
                    ->unique('_curriculum')
                    ->mapWithKeys(fn($row) => [(int) $row->_curriculum => (string) $row->code]);
            }

            foreach ($curriculumByGroup as $groupId => $curriculumId) {
                $code = $current->get($curriculumId) ?? $latest->get($curriculumId);
                if ($code !== null) {
                    $result[(int) $groupId] = $code;
                }
            }
        }

        $missingGroups = array_diff($groupIds, array_keys($result));
        if (!empty($missingGroups)) {
            $globalCode = $this->resolveGlobalSemester($educationYear);

            if ($globalCode !== null) {
                foreach ($missingGroups as $groupId) {
                    $result[(int) $groupId] = $globalCode;
                }
            }
        }

        return $result;
    }

    /**
     * Global semester fallback (no group/curriculum context).
     */
    protected function resolveGlobalSemester(?string $educationYear = null): ?string
    {
        $today = Carbon::now()->toDateString();

        $currentQuery = DB::table('h_semestr')
            ->where('active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderByDesc('start_date');

        if ($educationYear !== null) {
            $currentQuery->where('_education_year', $educationYear);
        }

        $code = $currentQuery->value('code');

        if ($code !== null) {
            return (string) $code;
        }

        $fallbackQuery = DB::table('h_semestr')
            ->where('active', true)
            ->orderByDesc('end_date');

        if ($educationYear !== null) {
            $fallbackQuery->where('_education_year', $educationYear);
        }

        $fallback = $fallbackQuery->value('code');

        return $fallback !== null ? (string) $fallback : null;
    }
}
