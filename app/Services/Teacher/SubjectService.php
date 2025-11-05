<?php

namespace App\Services\Teacher;

use App\Models\ESubject;
use App\Models\ESubjectSchedule;
use App\Models\EStudent;
use Illuminate\Support\Facades\DB;

/**
 * Teacher Subject Service
 *
 * Manages teacher's subjects and related data
 */
class SubjectService
{
    /**
     * Get teacher's subjects
     */
    public function getTeacherSubjects(int $teacherId): array
    {
        $schedules = ESubjectSchedule::where('_employee', $teacherId)
            ->with(['subject', 'group'])
            ->get()
            ->unique('_subject');

        $subjectsData = [];

        foreach ($schedules->groupBy('_subject') as $subjectId => $subjectSchedules) {
            $subject = $subjectSchedules->first()->subject;
            $groups = $subjectSchedules->pluck('group')->unique('id');

            $totalStudents = 0;
            foreach ($groups as $group) {
                $totalStudents += EStudent::whereHas('meta', function ($q) use ($group) {
                    $q->where('_group', $group->id);
                })->count();
            }

            $subjectsData[] = [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'credit_hours' => $subject->credit_hours,
                'groups' => $groups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                    ];
                })->values()->toArray(),
                'total_groups' => $groups->count(),
                'total_students' => $totalStudents,
                'total_hours' => $subjectSchedules->sum('hours'),
            ];
        }

        return $subjectsData;
    }

    /**
     * Get subject details
     */
    public function getSubjectDetails(int $subjectId, int $teacherId): array
    {
        // Verify teacher has access
        $this->verifyTeacherSubject($subjectId, $teacherId);

        $subject = ESubject::findOrFail($subjectId);

        $schedules = ESubjectSchedule::where('_subject', $subjectId)
            ->where('_employee', $teacherId)
            ->with('group')
            ->get();

        $groups = $schedules->pluck('group')->unique('id');

        $totalStudents = 0;
        foreach ($groups as $group) {
            $totalStudents += EStudent::whereHas('meta', function ($q) use ($group) {
                $q->where('_group', $group->id);
            })->count();
        }

        return [
            'id' => $subject->id,
            'name' => $subject->name,
            'code' => $subject->code,
            'credit_hours' => $subject->credit_hours,
            'description' => $subject->description,
            'syllabus' => $subject->syllabus,
            'groups' => $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                ];
            })->values()->toArray(),
            'total_students' => $totalStudents,
            'schedules' => $schedules->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'group' => [
                        'id' => $schedule->group->id,
                        'name' => $schedule->group->name,
                    ],
                    'lesson_type' => $schedule->lesson_type,
                    'week_day' => $schedule->week_day,
                    'pair_number' => $schedule->pair_number,
                    'room' => $schedule->room,
                    'building' => $schedule->building,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get students for subject group
     */
    public function getSubjectStudents(int $subjectId, int $teacherId, ?int $groupId = null): array
    {
        // Verify teacher has access
        $this->verifyTeacherSubject($subjectId, $teacherId);

        if ($groupId) {
            $students = EStudent::whereHas('meta', function ($q) use ($groupId) {
                $q->where('_group', $groupId);
            })
            ->where('active', true)
            ->with(['meta.group', 'meta.specialty'])
            ->get();
        } else {
            // Get all students from all groups teacher teaches in this subject
            $schedules = ESubjectSchedule::where('_subject', $subjectId)
                ->where('_employee', $teacherId)
                ->get();

            $groupIds = $schedules->pluck('_group')->unique();

            $students = EStudent::whereHas('meta', function ($q) use ($groupIds) {
                $q->whereIn('_group', $groupIds);
            })
            ->where('active', true)
            ->with(['meta.group', 'meta.specialty'])
            ->get();
        }

        return $students->map(function ($student) {
            return [
                'id' => $student->id,
                'student_id' => $student->student_id_number,
                'name' => $student->second_name . ' ' . $student->first_name,
                'email' => $student->email,
                'phone' => $student->phone,
                'group' => $student->meta->group ? [
                    'id' => $student->meta->group->id,
                    'name' => $student->meta->group->name,
                ] : null,
                'specialty' => $student->meta->specialty ? [
                    'id' => $student->meta->specialty->id,
                    'name' => $student->meta->specialty->name,
                ] : null,
                'course' => $student->meta->course ?? null,
            ];
        })->toArray();
    }

    /**
     * Verify teacher has access to subject
     */
    protected function verifyTeacherSubject(int $subjectId, int $teacherId): void
    {
        $hasAccess = ESubjectSchedule::where('_subject', $subjectId)
            ->where('_employee', $teacherId)
            ->exists();

        if (!$hasAccess) {
            throw new \Exception('You do not have access to this subject');
        }
    }
}
