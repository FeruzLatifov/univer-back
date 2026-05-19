<?php

namespace App\Services\Teacher;

use App\Models\EAttendance;
use App\Models\EGrade;
use App\Models\ESubjectSchedule;
use App\Models\ESubjectTopic;
use Illuminate\Support\Facades\DB;

class ReportsService
{
    public function getOverviewReport(int $teacherId, ?int $subjectId = null): array
    {
        return [
            'total_students'      => $this->countStudents($teacherId, $subjectId),
            'average_attendance'  => $this->averageAttendance($teacherId, $subjectId),
            'average_grade'       => $this->averageGrade($teacherId, $subjectId),
            'total_lessons'       => $this->countConductedLessons($teacherId, $subjectId),
            'completed_topics'    => $this->countTopics($teacherId, $subjectId, true),
            'total_topics'        => $this->countTopics($teacherId, $subjectId, null),
        ];
    }

    private function scheduleQuery(int $teacherId, ?int $subjectId)
    {
        $q = ESubjectSchedule::where('_employee', $teacherId)->where('active', true);

        if ($subjectId !== null) {
            $q->where('_subject', $subjectId);
        }

        return $q;
    }

    private function countStudents(int $teacherId, ?int $subjectId): int
    {
        $groupIds = $this->scheduleQuery($teacherId, $subjectId)
            ->distinct('_group')
            ->pluck('_group');

        if ($groupIds->isEmpty()) {
            return 0;
        }

        return DB::table('e_student')
            ->join('e_student_meta', 'e_student.id', '=', 'e_student_meta._student')
            ->whereIn('e_student_meta._group', $groupIds)
            ->where('e_student.active', true)
            ->distinct('e_student.id')
            ->count('e_student.id');
    }

    private function averageAttendance(int $teacherId, ?int $subjectId): float
    {
        $scheduleIds = $this->scheduleQuery($teacherId, $subjectId)->pluck('id');

        if ($scheduleIds->isEmpty()) {
            return 0.0;
        }

        $total = EAttendance::whereIn('_subject_schedule', $scheduleIds)
            ->where('active', true)
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $present = EAttendance::whereIn('_subject_schedule', $scheduleIds)
            ->where('active', true)
            ->whereIn('_attendance_type', [EAttendance::STATUS_PRESENT, EAttendance::STATUS_LATE])
            ->count();

        return round(($present / $total) * 100, 1);
    }

    private function averageGrade(int $teacherId, ?int $subjectId): float
    {
        $subjectIds = $subjectId !== null
            ? collect([$subjectId])
            : $this->scheduleQuery($teacherId, null)->distinct('_subject')->pluck('_subject');

        if ($subjectIds->isEmpty()) {
            return 0.0;
        }

        $avg = EGrade::whereIn('_subject', $subjectIds)
            ->whereNotNull('grade')
            ->avg('grade');

        return $avg !== null ? round((float) $avg, 1) : 0.0;
    }

    private function countConductedLessons(int $teacherId, ?int $subjectId): int
    {
        return $this->scheduleQuery($teacherId, $subjectId)
            ->whereDate('lesson_date', '<=', now()->toDateString())
            ->count();
    }

    private function countTopics(int $teacherId, ?int $subjectId, ?bool $completed): int
    {
        $subjectIds = $subjectId !== null
            ? collect([$subjectId])
            : $this->scheduleQuery($teacherId, null)->distinct('_subject')->pluck('_subject');

        if ($subjectIds->isEmpty()) {
            return 0;
        }

        $q = ESubjectTopic::whereIn('_subject', $subjectIds);

        if ($completed === true) {
            $q->where('is_completed', true);
        }

        return $q->count();
    }
}
