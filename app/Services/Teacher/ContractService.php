<?php

namespace App\Services\Teacher;

use App\Models\EStudentContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(private readonly GroupService $groups)
    {
    }

    public function list(int $employeeId, array $filters = []): array
    {
        $educationYear = $filters['education_year'] ?? $this->groups->currentEducationYear();
        $studentIds = $this->studentIdsInScope($employeeId, $filters['group_id'] ?? null, $educationYear);

        if (empty($studentIds)) {
            return ['contracts' => [], 'education_year' => $educationYear, 'count' => 0];
        }

        $query = EStudentContract::query()
            ->whereIn('_student', $studentIds)
            ->where('_education_year', $educationYear)
            ->where('active', true)
            ->with(['student', 'payments']);

        if (!empty($filters['status'])) {
            $query->where('contract_status', $filters['status']);
        }

        $contracts = $query->orderByDesc('id')->get();

        $payload = $contracts->map(fn($c) => $this->formatListItem($c))->all();

        return [
            'contracts'      => $payload,
            'education_year' => $educationYear,
            'count'          => count($payload),
        ];
    }

    public function view(int $employeeId, int $contractId): array
    {
        $contract = EStudentContract::with(['student', 'payments'])
            ->findOrFail($contractId);

        $this->assertContractInScope($employeeId, $contract);

        return ['contract' => $this->formatDetail($contract)];
    }

    public function debtors(int $employeeId, array $filters = []): array
    {
        $list = $this->list($employeeId, $filters);

        $debtors = array_values(array_filter($list['contracts'], fn($c) => ($c['debt_summa'] ?? 0) > 0));

        return [
            'debtors'        => $debtors,
            'education_year' => $list['education_year'],
            'total_debtors'  => count($debtors),
            'total_debt'     => array_sum(array_column($debtors, 'debt_summa')),
        ];
    }

    private function studentIdsInScope(int $employeeId, ?int $groupId, string $educationYear): array
    {
        $groupIds = $this->groups->teacherGroupIds($employeeId);

        if (empty($groupIds)) {
            return [];
        }

        if ($groupId !== null) {
            $groupIds = array_intersect($groupIds, [$groupId]);

            if (empty($groupIds)) {
                return [];
            }
        }

        return DB::table('e_student_meta')
            ->whereIn('_group', $groupIds)
            ->where('_education_year', $educationYear)
            ->where('active', true)
            ->pluck('_student')
            ->unique()
            ->values()
            ->all();
    }

    private function assertContractInScope(int $employeeId, EStudentContract $contract): void
    {
        $groupIds = $this->groups->teacherGroupIds($employeeId);

        if (empty($groupIds)) {
            abort(404, 'Shartnoma topilmadi yoki sizga tegishli emas');
        }

        $exists = DB::table('e_student_meta')
            ->where('_student', $contract->_student)
            ->whereIn('_group', $groupIds)
            ->exists();

        if (!$exists) {
            abort(404, 'Shartnoma topilmadi yoki sizga tegishli emas');
        }
    }

    private function formatListItem(EStudentContract $c): array
    {
        $paidSum = $c->paidSum();

        $studentMeta = DB::table('e_student_meta')
            ->where('_student', $c->_student)
            ->where('_education_year', $c->_education_year)
            ->where('active', true)
            ->first();

        $groupName = null;
        if ($studentMeta) {
            $group = DB::table('e_group')->where('id', $studentMeta->_group)->first();
            $groupName = $group->name ?? null;
        }

        return [
            'id'              => $c->id,
            'contract_number' => $c->number,
            'contract_date'   => $c->date instanceof \DateTimeInterface
                ? $c->date->format('Y-m-d')
                : (is_string($c->date) ? substr($c->date, 0, 10) : null),
            'student'         => $c->student ? [
                'id'                => $c->student->id,
                'full_name'         => trim($c->student->first_name . ' ' . $c->student->second_name . ' ' . $c->student->third_name),
                'student_id_number' => $c->student->student_id_number,
            ] : null,
            'group'           => $groupName,
            'contract_status' => $c->contract_status,
            'contract_summa'  => (float) $c->summa,
            'paid_summa'      => $paidSum,
            'debt_summa'      => max(0.0, (float) $c->summa - $paidSum),
        ];
    }

    private function formatDetail(EStudentContract $c): array
    {
        $base = $this->formatListItem($c);

        return array_merge($base, [
            'real_summa'    => (float) $c->real_summa,
            'student_full' => $c->student ? [
                'id'                => $c->student->id,
                'full_name'         => trim($c->student->first_name . ' ' . $c->student->second_name . ' ' . $c->student->third_name),
                'student_id_number' => $c->student->student_id_number,
                'phone'             => $c->student->phone,
                'email'             => $c->student->email,
            ] : null,
            'payments'      => $c->payments->map(fn($p) => [
                'id'    => $p->id,
                'summa' => (float) $p->summa,
                'date'  => $p->created_at?->format('Y-m-d'),
            ])->values(),
        ]);
    }
}
