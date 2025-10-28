<?php

namespace App\Services;

use App\Models\Messaging\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Notification Service
 *
 * O'qituvchi va talabalar uchun notification yuborish
 *
 * @package App\Services
 */
class NotificationService
{
    /**
     * Create notification for user(s)
     *
     * @param string $type Notification type (assignment_created, test_published, etc)
     * @param array|int $recipientIds User ID or array of IDs
     * @param array $data Notification data (title, message, link, etc)
     * @return void
     */
    public function create(string $type, $recipientIds, array $data)
    {
        // Convert single ID to array
        if (!is_array($recipientIds)) {
            $recipientIds = [$recipientIds];
        }

        foreach ($recipientIds as $recipientId) {
            try {
                Notification::create([
                    '_user' => $recipientId,
                    'type' => $type,
                    'title' => $data['title'] ?? '',
                    'message' => $data['message'] ?? '',
                    'link' => $data['link'] ?? null,
                    'data' => json_encode($data['data'] ?? []),
                    'is_read' => false,
                    'active' => true,
                ]);
            } catch (\Exception $e) {
                Log::error('Notification creation failed', [
                    'recipient' => $recipientId,
                    'type' => $type,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Notify students about new assignment
     *
     * @param object $assignment EAssignment model
     * @return void
     */
    public function notifyNewAssignment($assignment)
    {
        // Get students from the subject's groups
        $studentIds = $this->getStudentsFromSubject($assignment->_subject);

        if (empty($studentIds)) {
            return;
        }

        $this->create('assignment_created', $studentIds, [
            'title' => 'Yangi topshiriq',
            'message' => "\"{$assignment->title}\" topshiriqi yuklandi. Muddat: " . date('d.m.Y', strtotime($assignment->deadline)),
            'link' => "/student/assignments",
            'data' => [
                'assignment_id' => $assignment->id,
                'subject_id' => $assignment->_subject,
                'deadline' => $assignment->deadline,
            ]
        ]);
    }

    /**
     * Notify students about graded assignment
     *
     * @param object $submission EAssignmentSubmission model
     * @return void
     */
    public function notifyAssignmentGraded($submission)
    {
        $assignment = $submission->assignment;

        $this->create('assignment_graded', $submission->_student, [
            'title' => 'Topshiriq baholandi',
            'message' => "\"{$assignment->title}\" topshirig'ingiz baholandi. Ball: {$submission->grade}",
            'link' => "/student/assignments",
            'data' => [
                'assignment_id' => $assignment->id,
                'submission_id' => $submission->id,
                'grade' => $submission->grade,
            ]
        ]);
    }

    /**
     * Notify students about published test
     *
     * @param object $test ETest model
     * @return void
     */
    public function notifyTestPublished($test)
    {
        $studentIds = $this->getStudentsFromSubject($test->_subject);

        if (empty($studentIds)) {
            return;
        }

        $this->create('test_published', $studentIds, [
            'title' => 'Yangi test',
            'message' => "\"{$test->title}\" testi yaratildi. Boshlanish: " . date('d.m.Y H:i', strtotime($test->start_time)),
            'link' => "/student/tests",
            'data' => [
                'test_id' => $test->id,
                'subject_id' => $test->_subject,
                'start_time' => $test->start_time,
                'end_time' => $test->end_time,
            ]
        ]);
    }

    /**
     * Notify students about test results
     *
     * @param object $attempt ETestAttempt model
     * @return void
     */
    public function notifyTestGraded($attempt)
    {
        $test = $attempt->test;

        $this->create('test_graded', $attempt->_student, [
            'title' => 'Test natijalari',
            'message' => "\"{$test->title}\" testi uchun natijangiz tayyor. Ball: {$attempt->score}%",
            'link' => "/student/tests",
            'data' => [
                'test_id' => $test->id,
                'attempt_id' => $attempt->id,
                'score' => $attempt->score,
            ]
        ]);
    }

    /**
     * Notify users about new message
     *
     * @param object $message Message model
     * @return void
     */
    public function notifyNewMessage($message)
    {
        $recipientUserIds = [];

        // Handle direct message
        if ($message->message_type === 'direct' && $message->receiver_id) {
            $userId = $this->getUserIdFromAuthor($message->receiver_id, $message->receiver_type);
            if ($userId) {
                $recipientUserIds[] = $userId;
            }
        }

        // Handle broadcast/announcement messages
        if (in_array($message->message_type, ['broadcast', 'announcement']) && $message->recipients) {
            foreach ($message->recipients as $recipient) {
                $userId = $this->getUserIdFromAuthor($recipient->recipient_id, $recipient->recipient_type);
                if ($userId) {
                    $recipientUserIds[] = $userId;
                }
            }
        }

        if (empty($recipientUserIds)) {
            return;
        }

        // Determine link based on recipient type
        $link = "/teacher/messages/{$message->id}"; // Default for teachers
        // You can add logic to determine if recipient is student and use /student/messages/{id}

        $this->create('new_message', $recipientUserIds, [
            'title' => 'Yangi xabar',
            'message' => "Sizga \"{$message->subject}\" mavzusida xabar keldi",
            'link' => $link,
            'data' => [
                'message_id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_type' => $message->sender_type,
            ]
        ]);
    }

    /**
     * Notify about new forum post/reply
     *
     * @param object $post ForumPost model
     * @param object $topic ForumTopic model
     * @return void
     */
    public function notifyForumReply($post, $topic)
    {
        // Notify topic author if it's a reply (and author is not the same)
        if ($post->author_id != $topic->author_id || $post->author_type != $topic->author_type) {
            // Get user ID based on author type
            $topicAuthorUserId = $this->getUserIdFromAuthor($topic->author_id, $topic->author_type);

            if ($topicAuthorUserId) {
                $this->create('forum_reply', $topicAuthorUserId, [
                    'title' => 'Yangi javob',
                    'message' => "\"{$topic->title}\" mavzusiga yangi javob yozildi",
                    'link' => "/teacher/forum/topics/{$topic->id}",
                    'data' => [
                        'topic_id' => $topic->id,
                        'post_id' => $post->id,
                    ]
                ]);
            }
        }

        // Notify subscribers (except post author)
        $subscribers = $topic->subscriptions()
            ->where('user_id', '!=', $post->author_id)
            ->orWhere('user_type', '!=', $post->author_type)
            ->get();

        $subscriberUserIds = [];
        foreach ($subscribers as $subscriber) {
            $userId = $this->getUserIdFromAuthor($subscriber->user_id, $subscriber->user_type);
            if ($userId) {
                $subscriberUserIds[] = $userId;
            }
        }

        if (!empty($subscriberUserIds)) {
            $this->create('forum_reply', $subscriberUserIds, [
                'title' => 'Yangi javob',
                'message' => "\"{$topic->title}\" mavzusiga yangi javob yozildi",
                'link' => "/teacher/forum/topics/{$topic->id}",
                'data' => [
                    'topic_id' => $topic->id,
                    'post_id' => $post->id,
                ]
            ]);
        }
    }

    /**
     * Get user ID from polymorphic author
     *
     * @param int $authorId
     * @param string $authorType (teacher, student, admin)
     * @return int|null User ID for notifications table
     */
    private function getUserIdFromAuthor($authorId, $authorType)
    {
        try {
            // Map author type to model and get corresponding user ID
            switch ($authorType) {
                case 'teacher':
                    $employee = \App\Models\System\EEmployee::find($authorId);
                    return $employee ? $employee->id : null;

                case 'student':
                    $student = \App\Models\System\EStudent::find($authorId);
                    return $student ? $student->id : null;

                case 'admin':
                    // Assuming admin has direct user ID
                    return $authorId;

                default:
                    return null;
            }
        } catch (\Exception $e) {
            Log::error('Failed to get user ID from author', [
                'author_id' => $authorId,
                'author_type' => $authorType,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get student IDs from subject
     *
     * @param int $subjectId
     * @return array
     */
    private function getStudentsFromSubject($subjectId)
    {
        try {
            // Get curriculum subject
            $curriculumSubject = \App\Models\Curriculum\ECurriculumSubject::where('_subject', $subjectId)
                ->where('active', true)
                ->first();

            if (!$curriculumSubject) {
                return [];
            }

            // Get students from the curriculum
            $students = \App\Models\System\EStudent::where('_curriculum', $curriculumSubject->_curriculum)
                ->where('_semestr', $curriculumSubject->_semester)
                ->where('active', true)
                ->pluck('id')
                ->toArray();

            return $students;
        } catch (\Exception $e) {
            Log::error('Failed to get students from subject', [
                'subject_id' => $subjectId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @param int $userId
     * @return bool
     */
    public function markAsRead($notificationId, $userId)
    {
        try {
            $notification = Notification::where('id', $notificationId)
                ->where('_user', $userId)
                ->first();

            if ($notification) {
                $notification->is_read = true;
                $notification->read_at = now();
                $notification->save();
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'notification_id' => $notificationId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark all notifications as read for user
     *
     * @param int $userId
     * @return int Number of notifications marked as read
     */
    public function markAllAsRead($userId)
    {
        try {
            return Notification::where('_user', $userId)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark all notifications as read', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get unread count for user
     *
     * @param int $userId
     * @return int
     */
    public function getUnreadCount($userId)
    {
        return Notification::where('_user', $userId)
            ->where('is_read', false)
            ->where('active', true)
            ->count();
    }

    /**
     * Delete old notifications (older than 30 days)
     *
     * @return int Number of deleted notifications
     */
    public function deleteOldNotifications()
    {
        try {
            return Notification::where('created_at', '<', now()->subDays(30))
                ->where('is_read', true)
                ->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete old notifications', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }
}
