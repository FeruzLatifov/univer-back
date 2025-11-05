<?php

namespace App\Services\Teacher;

use App\Models\EAssignment;
use App\Models\EAssignmentSubmission;
use App\Models\EStudent;
use App\Models\EStudentTaskActivity;
use App\Models\ESubjectSchedule;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Teacher Assignment Service
 *
 * Handles all assignment/task related business logic for teachers
 */
class AssignmentService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get teacher's assignments with filters
     */
    public function getAssignments(int $teacherId, array $filters = []): array
    {
        $query = EAssignment::where('_employee', $teacherId)
            ->where('active', true)
            ->with(['subject', 'group', 'topic']);

        // Apply filters
        if (!empty($filters['subject_id'])) {
            $query->where('_subject', $filters['subject_id']);
        }

        if (!empty($filters['group_id'])) {
            $query->where('_group', $filters['group_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'upcoming':
                    $query->where('deadline', '>', now()->addDay())
                        ->where('published_at', '<=', now());
                    break;
                case 'active':
                    $query->where('deadline', '>', now())
                        ->where('deadline', '<=', now()->addWeek())
                        ->where('published_at', '<=', now());
                    break;
                case 'overdue':
                    $query->where('deadline', '<', now());
                    break;
                case 'past':
                    $query->where('deadline', '<', now()->subMonth());
                    break;
            }
        }

        $assignments = $query->orderBy('deadline', 'desc')->get();

        return $assignments->map(function ($assignment) {
            $stats = $assignment->submission_stats;

            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'subject' => [
                    'id' => $assignment->subject->id,
                    'name' => $assignment->subject->name,
                ],
                'group' => [
                    'id' => $assignment->group->id,
                    'name' => $assignment->group->name,
                ],
                'topic' => $assignment->topic ? [
                    'id' => $assignment->topic->id,
                    'name' => $assignment->topic->name,
                ] : null,
                'deadline' => $assignment->deadline,
                'max_score' => $assignment->max_score,
                'is_published' => $assignment->published_at !== null,
                'published_at' => $assignment->published_at,
                'submissions' => [
                    'total' => $stats['total_students'] ?? 0,
                    'submitted' => $stats['submitted_count'] ?? 0,
                    'graded' => $stats['graded_count'] ?? 0,
                    'pending' => $stats['pending_count'] ?? 0,
                ],
                'created_at' => $assignment->created_at,
            ];
        })->toArray();
    }

    /**
     * Get single assignment details
     */
    public function getAssignment(int $id, int $teacherId): array
    {
        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacherId)
            ->with(['subject', 'group', 'topic', 'attachments'])
            ->firstOrFail();

        $stats = $assignment->submission_stats;

        return [
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'subject' => [
                'id' => $assignment->subject->id,
                'name' => $assignment->subject->name,
            ],
            'group' => [
                'id' => $assignment->group->id,
                'name' => $assignment->group->name,
            ],
            'topic' => $assignment->topic ? [
                'id' => $assignment->topic->id,
                'name' => $assignment->topic->name,
            ] : null,
            'deadline' => $assignment->deadline,
            'max_score' => $assignment->max_score,
            'min_score' => $assignment->min_score,
            'allow_late_submission' => $assignment->allow_late_submission,
            'is_published' => $assignment->published_at !== null,
            'published_at' => $assignment->published_at,
            'attachments' => $assignment->attachments,
            'instructions' => $assignment->instructions,
            'submissions' => [
                'total' => $stats['total_students'] ?? 0,
                'submitted' => $stats['submitted_count'] ?? 0,
                'graded' => $stats['graded_count'] ?? 0,
                'pending' => $stats['pending_count'] ?? 0,
                'avg_score' => $stats['avg_score'] ?? 0,
            ],
            'created_at' => $assignment->created_at,
            'updated_at' => $assignment->updated_at,
        ];
    }

    /**
     * Create new assignment
     */
    public function createAssignment(int $teacherId, array $data): EAssignment
    {
        return DB::transaction(function () use ($teacherId, $data) {
            $assignment = EAssignment::create([
                '_employee' => $teacherId,
                '_subject' => $data['subject_id'],
                '_group' => $data['group_id'],
                '_topic' => $data['topic_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'deadline' => $data['deadline'],
                'max_score' => $data['max_score'],
                'min_score' => $data['min_score'] ?? 0,
                'allow_late_submission' => $data['allow_late_submission'] ?? false,
                'published_at' => $data['publish_immediately'] ? now() : null,
                'active' => true,
            ]);

            // Handle file attachments
            if (!empty($data['attachments'])) {
                $this->handleAttachments($assignment, $data['attachments']);
            }

            // Send notifications if published
            if ($assignment->published_at) {
                $this->notifyStudents($assignment);
            }

            return $assignment;
        });
    }

    /**
     * Update assignment
     */
    public function updateAssignment(int $id, int $teacherId, array $data): EAssignment
    {
        return DB::transaction(function () use ($id, $teacherId, $data) {
            $assignment = EAssignment::where('id', $id)
                ->where('_employee', $teacherId)
                ->firstOrFail();

            $assignment->update([
                '_subject' => $data['subject_id'] ?? $assignment->_subject,
                '_group' => $data['group_id'] ?? $assignment->_group,
                '_topic' => $data['topic_id'] ?? $assignment->_topic,
                'title' => $data['title'] ?? $assignment->title,
                'description' => $data['description'] ?? $assignment->description,
                'instructions' => $data['instructions'] ?? $assignment->instructions,
                'deadline' => $data['deadline'] ?? $assignment->deadline,
                'max_score' => $data['max_score'] ?? $assignment->max_score,
                'min_score' => $data['min_score'] ?? $assignment->min_score,
                'allow_late_submission' => $data['allow_late_submission'] ?? $assignment->allow_late_submission,
            ]);

            // Handle new attachments
            if (isset($data['attachments'])) {
                $this->handleAttachments($assignment, $data['attachments'], $data['remove_existing'] ?? false);
            }

            return $assignment->fresh();
        });
    }

    /**
     * Delete assignment
     */
    public function deleteAssignment(int $id, int $teacherId): bool
    {
        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        return $assignment->delete();
    }

    /**
     * Publish assignment
     */
    public function publishAssignment(int $id, int $teacherId): EAssignment
    {
        return DB::transaction(function () use ($id, $teacherId) {
            $assignment = EAssignment::where('id', $id)
                ->where('_employee', $teacherId)
                ->whereNull('published_at')
                ->firstOrFail();

            $assignment->update(['published_at' => now()]);

            // Notify students
            $this->notifyStudents($assignment);

            return $assignment;
        });
    }

    /**
     * Unpublish assignment
     */
    public function unpublishAssignment(int $id, int $teacherId): EAssignment
    {
        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $assignment->update(['published_at' => null]);

        return $assignment;
    }

    /**
     * Get assignment submissions
     */
    public function getSubmissions(int $assignmentId, int $teacherId, array $filters = []): array
    {
        $assignment = EAssignment::where('id', $assignmentId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        // Get all students in the group
        $students = EStudent::whereHas('meta', function ($q) use ($assignment) {
            $q->where('_group', $assignment->_group);
        })->with('meta')->get();

        $submissions = EAssignmentSubmission::where('_assignment', $assignmentId)
            ->with('student')
            ->get()
            ->keyBy('_student');

        $result = $students->map(function ($student) use ($submissions, $assignment) {
            $submission = $submissions->get($student->id);

            return [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->second_name . ' ' . $student->first_name,
                    'student_id' => $student->student_id_number,
                ],
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'submitted_at' => $submission->submitted_at,
                    'status' => $submission->status,
                    'score' => $submission->score,
                    'feedback' => $submission->feedback,
                    'is_late' => $submission->submitted_at > $assignment->deadline,
                    'graded_at' => $submission->graded_at,
                ] : null,
            ];
        });

        // Apply filters
        if (!empty($filters['status'])) {
            $result = $result->filter(function ($item) use ($filters) {
                if ($filters['status'] === 'submitted' && $item['submission']) {
                    return true;
                }
                if ($filters['status'] === 'not_submitted' && !$item['submission']) {
                    return true;
                }
                if ($filters['status'] === 'graded' && $item['submission'] && $item['submission']['score'] !== null) {
                    return true;
                }
                if ($filters['status'] === 'pending' && $item['submission'] && $item['submission']['score'] === null) {
                    return true;
                }
                return false;
            });
        }

        return $result->values()->toArray();
    }

    /**
     * Get submission detail
     */
    public function getSubmissionDetail(int $submissionId, int $teacherId): array
    {
        $submission = EAssignmentSubmission::where('id', $submissionId)
            ->with(['student', 'assignment'])
            ->firstOrFail();

        // Verify teacher owns this assignment
        if ($submission->assignment->_employee !== $teacherId) {
            throw new \Exception('Unauthorized access to submission');
        }

        return [
            'id' => $submission->id,
            'student' => [
                'id' => $submission->student->id,
                'name' => $submission->student->second_name . ' ' . $submission->student->first_name,
                'student_id' => $submission->student->student_id_number,
            ],
            'assignment' => [
                'id' => $submission->assignment->id,
                'title' => $submission->assignment->title,
                'max_score' => $submission->assignment->max_score,
                'deadline' => $submission->assignment->deadline,
            ],
            'content' => $submission->content,
            'attachments' => $submission->attachments ?? [],
            'submitted_at' => $submission->submitted_at,
            'is_late' => $submission->submitted_at > $submission->assignment->deadline,
            'score' => $submission->score,
            'feedback' => $submission->feedback,
            'status' => $submission->status,
            'graded_at' => $submission->graded_at,
            'graded_by' => $submission->_graded_by,
        ];
    }

    /**
     * Grade submission
     */
    public function gradeSubmission(int $submissionId, int $teacherId, array $data): EAssignmentSubmission
    {
        return DB::transaction(function () use ($submissionId, $teacherId, $data) {
            $submission = EAssignmentSubmission::where('id', $submissionId)
                ->with('assignment')
                ->firstOrFail();

            // Verify teacher owns this assignment
            if ($submission->assignment->_employee !== $teacherId) {
                throw new \Exception('Unauthorized access to submission');
            }

            $submission->update([
                'score' => $data['score'],
                'feedback' => $data['feedback'] ?? null,
                'status' => 'graded',
                'graded_at' => now(),
                '_graded_by' => $teacherId,
            ]);

            // Log activity
            EStudentTaskActivity::create([
                '_student' => $submission->_student,
                '_assignment' => $submission->_assignment,
                'activity_type' => 'graded',
                'description' => 'Assignment graded by teacher',
                'score' => $data['score'],
            ]);

            // Notify student
            $this->notificationService->send([
                'user_id' => $submission->_student,
                'type' => 'assignment_graded',
                'title' => 'Assignment Graded',
                'message' => "Your assignment '{$submission->assignment->title}' has been graded",
                'data' => [
                    'assignment_id' => $submission->_assignment,
                    'submission_id' => $submission->id,
                    'score' => $data['score'],
                ],
            ]);

            return $submission;
        });
    }

    /**
     * Get assignment statistics
     */
    public function getStatistics(int $assignmentId, int $teacherId): array
    {
        $assignment = EAssignment::where('id', $assignmentId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $submissions = EAssignmentSubmission::where('_assignment', $assignmentId)->get();

        $totalStudents = EStudent::whereHas('meta', function ($q) use ($assignment) {
            $q->where('_group', $assignment->_group);
        })->count();

        $submittedCount = $submissions->count();
        $gradedCount = $submissions->where('score', '!=', null)->count();
        $pendingCount = $submittedCount - $gradedCount;
        $notSubmittedCount = $totalStudents - $submittedCount;

        $gradedSubmissions = $submissions->whereNotNull('score');
        $avgScore = $gradedSubmissions->avg('score');
        $maxScoreAchieved = $gradedSubmissions->max('score');
        $minScoreAchieved = $gradedSubmissions->min('score');

        return [
            'total_students' => $totalStudents,
            'submitted_count' => $submittedCount,
            'not_submitted_count' => $notSubmittedCount,
            'graded_count' => $gradedCount,
            'pending_count' => $pendingCount,
            'submission_rate' => $totalStudents > 0 ? round(($submittedCount / $totalStudents) * 100, 2) : 0,
            'grading_rate' => $submittedCount > 0 ? round(($gradedCount / $submittedCount) * 100, 2) : 0,
            'scores' => [
                'average' => $avgScore ? round($avgScore, 2) : null,
                'max' => $maxScoreAchieved,
                'min' => $minScoreAchieved,
                'max_possible' => $assignment->max_score,
            ],
            'deadline' => $assignment->deadline,
            'is_overdue' => $assignment->deadline < now(),
            'late_submissions' => $submissions->filter(function ($sub) use ($assignment) {
                return $sub->submitted_at > $assignment->deadline;
            })->count(),
        ];
    }

    /**
     * Get assignment activities
     */
    public function getActivities(int $assignmentId, int $teacherId, int $limit = 20): array
    {
        $assignment = EAssignment::where('id', $assignmentId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $activities = EStudentTaskActivity::where('_assignment', $assignmentId)
            ->with('student')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'student' => [
                    'id' => $activity->student->id,
                    'name' => $activity->student->second_name . ' ' . $activity->student->first_name,
                ],
                'activity_type' => $activity->activity_type,
                'description' => $activity->description,
                'score' => $activity->score,
                'timestamp' => $activity->created_at,
            ];
        })->toArray();
    }

    /**
     * Get teacher's subjects
     */
    public function getTeacherSubjects(int $teacherId): array
    {
        $schedules = ESubjectSchedule::where('_employee', $teacherId)
            ->with('subject')
            ->get()
            ->unique('_subject');

        return $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
                'code' => $schedule->subject->code,
            ];
        })->values()->toArray();
    }

    /**
     * Get teacher's groups
     */
    public function getTeacherGroups(int $teacherId): array
    {
        $schedules = ESubjectSchedule::where('_employee', $teacherId)
            ->with('group')
            ->get()
            ->unique('_group');

        return $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->group->id,
                'name' => $schedule->group->name,
            ];
        })->values()->toArray();
    }

    /**
     * Handle file attachments
     */
    protected function handleAttachments(EAssignment $assignment, array $files, bool $removeExisting = false): void
    {
        if ($removeExisting && $assignment->attachments) {
            foreach ($assignment->attachments as $file) {
                Storage::delete($file['path']);
            }
        }

        $attachments = $assignment->attachments ?? [];

        foreach ($files as $file) {
            $path = $file->store('assignments/' . $assignment->id, 'public');
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
            ];
        }

        $assignment->update(['attachments' => $attachments]);
    }

    /**
     * Notify students about new assignment
     */
    protected function notifyStudents(EAssignment $assignment): void
    {
        $students = EStudent::whereHas('meta', function ($q) use ($assignment) {
            $q->where('_group', $assignment->_group);
        })->get();

        foreach ($students as $student) {
            $this->notificationService->send([
                'user_id' => $student->id,
                'type' => 'new_assignment',
                'title' => 'New Assignment',
                'message' => "New assignment: {$assignment->title}",
                'data' => [
                    'assignment_id' => $assignment->id,
                    'deadline' => $assignment->deadline,
                ],
            ]);
        }
    }
}
