<?php

namespace App\Services\Teacher;

use App\Models\ELessonPlan;
use App\Models\ESubject;
use App\Models\ESubjectTopic;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CalendarPlanService
{
    public function __construct(private readonly GroupService $groups)
    {
    }

    /**
     * Return the topic list with the teacher's planned/actual dates merged in.
     */
    public function listForSubject(int $employeeId, int $subjectId, ?int $groupId = null): array
    {
        $this->assertSubjectInScope($employeeId, $subjectId);

        $subject = ESubject::find($subjectId);
        $topics = ESubjectTopic::query()
            ->where('_subject', $subjectId)
            ->where('active', true)
            ->orderBy('order_number')
            ->orderBy('id')
            ->get();

        $plans = ELessonPlan::query()
            ->where('_employee', $employeeId)
            ->where('_subject', $subjectId)
            ->when($groupId, fn($q) => $q->where('_group', $groupId))
            ->where('active', true)
            ->get()
            ->keyBy('_topic');

        $items = $topics->map(function (ESubjectTopic $topic) use ($plans) {
            $plan = $plans->get($topic->id);

            return [
                'topic_id'     => $topic->id,
                'name'         => $topic->name,
                'order_number' => $topic->order_number,
                'hours'        => $plan?->hours ?? $topic->hours ?? 2,
                'planned_date' => $plan?->planned_date?->format('Y-m-d'),
                'actual_date'  => $plan?->actual_date?->format('Y-m-d'),
                'notes'        => $plan?->notes,
                'plan_id'      => $plan?->id,
            ];
        })->all();

        $totalHours = array_sum(array_column($items, 'hours'));
        $completed = count(array_filter($items, fn($i) => $i['actual_date'] !== null));

        return [
            'subject'         => $subject ? ['id' => $subject->id, 'name' => $subject->name] : null,
            'group_id'        => $groupId,
            'items'           => $items,
            'total_topics'    => count($items),
            'planned_topics'  => count(array_filter($items, fn($i) => $i['planned_date'] !== null)),
            'completed_topics' => $completed,
            'total_hours'     => $totalHours,
        ];
    }

    /**
     * Bulk upsert plan rows. Each $items entry: {topic_id, planned_date?, actual_date?, hours?, notes?}.
     */
    public function upsert(int $employeeId, int $subjectId, ?int $groupId, array $items): int
    {
        $this->assertSubjectInScope($employeeId, $subjectId);

        return DB::transaction(function () use ($employeeId, $subjectId, $groupId, $items) {
            $count = 0;

            foreach ($items as $item) {
                if (empty($item['topic_id'])) {
                    continue;
                }

                $topicExists = ESubjectTopic::where('id', $item['topic_id'])
                    ->where('_subject', $subjectId)
                    ->exists();

                if (!$topicExists) {
                    continue;
                }

                ELessonPlan::updateOrCreate(
                    [
                        '_employee' => $employeeId,
                        '_subject'  => $subjectId,
                        '_group'    => $groupId,
                        '_topic'    => (int) $item['topic_id'],
                    ],
                    array_filter([
                        'planned_date' => $this->parseDate($item['planned_date'] ?? null),
                        'actual_date'  => $this->parseDate($item['actual_date'] ?? null),
                        'hours'        => isset($item['hours']) ? (int) $item['hours'] : null,
                        'notes'        => $item['notes'] ?? null,
                        'active'       => true,
                    ], fn($v) => $v !== null)
                );

                $count++;
            }

            return $count;
        });
    }

    private function assertSubjectInScope(int $employeeId, int $subjectId): void
    {
        $taught = DB::table('e_subject_schedule')
            ->where('_employee', $employeeId)
            ->where('_subject', $subjectId)
            ->where('active', true)
            ->exists();

        if (!$taught) {
            abort(404, 'Fan topilmadi yoki sizga tegishli emas');
        }
    }

    private function parseDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
