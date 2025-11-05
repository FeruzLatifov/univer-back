<?php

namespace App\Services\Teacher;

use App\Models\ESubjectTopic;
use App\Models\ESubject;
use Illuminate\Support\Facades\DB;

/**
 * Teacher Topic Service
 *
 * Manages subject topics/syllabus
 */
class TopicService
{
    /**
     * Get topics for subject
     */
    public function getTopics(int $subjectId, int $teacherId): array
    {
        // Verify teacher teaches this subject
        $this->verifyTeacherSubject($subjectId, $teacherId);

        $topics = ESubjectTopic::where('_subject', $subjectId)
            ->where('active', true)
            ->orderBy('order_number')
            ->get();

        return $topics->map(function ($topic) {
            return [
                'id' => $topic->id,
                'title' => $topic->title,
                'description' => $topic->description,
                'order_number' => $topic->order_number,
                'duration_hours' => $topic->duration_hours,
                'learning_outcomes' => $topic->learning_outcomes,
                'resources' => $topic->resources,
                'is_completed' => $topic->is_completed,
                'created_at' => $topic->created_at,
            ];
        })->toArray();
    }

    /**
     * Create new topic
     */
    public function createTopic(int $subjectId, int $teacherId, array $data): ESubjectTopic
    {
        $this->verifyTeacherSubject($subjectId, $teacherId);

        // Get next order number
        $maxOrder = ESubjectTopic::where('_subject', $subjectId)->max('order_number') ?? 0;

        return ESubjectTopic::create([
            '_subject' => $subjectId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_hours' => $data['duration_hours'] ?? 2,
            'learning_outcomes' => $data['learning_outcomes'] ?? [],
            'resources' => $data['resources'] ?? [],
            'order_number' => $maxOrder + 1,
            'active' => true,
        ]);
    }

    /**
     * Update topic
     */
    public function updateTopic(int $subjectId, int $topicId, int $teacherId, array $data): ESubjectTopic
    {
        $this->verifyTeacherSubject($subjectId, $teacherId);

        $topic = ESubjectTopic::where('id', $topicId)
            ->where('_subject', $subjectId)
            ->firstOrFail();

        $topic->update([
            'title' => $data['title'] ?? $topic->title,
            'description' => $data['description'] ?? $topic->description,
            'duration_hours' => $data['duration_hours'] ?? $topic->duration_hours,
            'learning_outcomes' => $data['learning_outcomes'] ?? $topic->learning_outcomes,
            'resources' => $data['resources'] ?? $topic->resources,
            'is_completed' => $data['is_completed'] ?? $topic->is_completed,
        ]);

        return $topic;
    }

    /**
     * Delete topic
     */
    public function deleteTopic(int $subjectId, int $topicId, int $teacherId): bool
    {
        $this->verifyTeacherSubject($subjectId, $teacherId);

        $topic = ESubjectTopic::where('id', $topicId)
            ->where('_subject', $subjectId)
            ->firstOrFail();

        return $topic->delete();
    }

    /**
     * Reorder topics
     */
    public function reorderTopics(int $subjectId, int $teacherId, array $topicIds): bool
    {
        $this->verifyTeacherSubject($subjectId, $teacherId);

        DB::transaction(function () use ($subjectId, $topicIds) {
            foreach ($topicIds as $index => $topicId) {
                ESubjectTopic::where('id', $topicId)
                    ->where('_subject', $subjectId)
                    ->update(['order_number' => $index + 1]);
            }
        });

        return true;
    }

    /**
     * Get subject syllabus overview
     */
    public function getSyllabus(int $subjectId, int $teacherId): array
    {
        $this->verifyTeacherSubject($subjectId, $teacherId);

        $subject = ESubject::findOrFail($subjectId);

        $topics = ESubjectTopic::where('_subject', $subjectId)
            ->where('active', true)
            ->orderBy('order_number')
            ->get();

        $totalHours = $topics->sum('duration_hours');
        $completedTopics = $topics->where('is_completed', true)->count();
        $totalTopics = $topics->count();

        return [
            'subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'credit_hours' => $subject->credit_hours,
            ],
            'summary' => [
                'total_topics' => $totalTopics,
                'completed_topics' => $completedTopics,
                'completion_rate' => $totalTopics > 0 ? round(($completedTopics / $totalTopics) * 100, 2) : 0,
                'total_hours' => $totalHours,
            ],
            'topics' => $topics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'description' => $topic->description,
                    'order_number' => $topic->order_number,
                    'duration_hours' => $topic->duration_hours,
                    'is_completed' => $topic->is_completed,
                    'learning_outcomes_count' => is_array($topic->learning_outcomes) ? count($topic->learning_outcomes) : 0,
                    'resources_count' => is_array($topic->resources) ? count($topic->resources) : 0,
                ];
            })->toArray(),
        ];
    }

    /**
     * Verify teacher teaches this subject
     */
    protected function verifyTeacherSubject(int $subjectId, int $teacherId): void
    {
        $hasAccess = DB::table('e_subject_schedule')
            ->where('_subject', $subjectId)
            ->where('_employee', $teacherId)
            ->exists();

        if (!$hasAccess) {
            throw new \Exception('You do not have access to this subject');
        }
    }
}
