<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\MessageAttachment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * MessagingController
 *
 * Handles all messaging operations:
 * - Send messages (direct, broadcast)
 * - Inbox/Sent messages
 * - Read/Unread/Archive/Star
 * - Attachments
 * - Replies
 */
class MessagingController extends Controller
{
    /**
     * Notification service instance
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Create a new controller instance
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Get inbox messages for current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function inbox(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $query = Message::query()
            ->with(['sender', 'attachments'])
            ->receivedBy($user->id, $userType)
            ->latest();

        // Filters
        if ($request->has('is_read')) {
            $isRead = filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead);
        }

        if ($request->has('message_type')) {
            $query->where('message_type', $request->message_type);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('has_attachments')) {
            $hasAttachments = filter_var($request->has_attachments, FILTER_VALIDATE_BOOLEAN);
            $query->where('has_attachments', $hasAttachments);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ILIKE', "%{$search}%")
                  ->orWhere('body', 'ILIKE', "%{$search}%");
            });
        }

        $messages = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Get sent messages for current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function sent(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $messages = Message::query()
            ->with(['receiver', 'recipients', 'attachments'])
            ->sentBy($user->id, $userType)
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'data' => $messages,
        ]);
    }

    /**
     * Get single message details
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $message = Message::with(['sender', 'receiver', 'recipients', 'attachments', 'replies'])
            ->findOrFail($id);

        // Check if user has access to this message
        if (!$this->userCanAccessMessage($message, $user->id, $userType)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        // Mark as read if user is receiver
        if ($this->userIsReceiver($message, $user->id, $userType)) {
            if ($message->isDirect() && !$message->is_read) {
                $message->markAsRead();
            } elseif ($message->isBroadcast()) {
                $recipient = MessageRecipient::where('message_id', $message->id)
                    ->forRecipient($user->id, $userType)
                    ->first();
                if ($recipient && !$recipient->is_read) {
                    $recipient->markAsRead();
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $message,
        ]);
    }

    /**
     * Send a new message
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required_if:message_type,direct|integer',
            'receiver_type' => 'required_if:message_type,direct|in:teacher,student,admin',
            'recipients' => 'required_if:message_type,broadcast|array',
            'recipients.*.id' => 'required_with:recipients|integer',
            'recipients.*.type' => 'required_with:recipients|in:teacher,student,admin',
            'subject' => 'required|string|max:500',
            'body' => 'required|string',
            'message_type' => 'required|in:direct,broadcast,announcement',
            'priority' => 'nullable|in:low,normal,high,urgent',
            'parent_message_id' => 'nullable|exists:messages,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = $request->user();
            $userType = $this->getUserType($user);

            // Create message
            $message = Message::create([
                'sender_id' => $user->id,
                'sender_type' => $userType,
                'receiver_id' => $request->receiver_id,
                'receiver_type' => $request->receiver_type,
                'subject' => $request->subject,
                'body' => $request->body,
                'message_type' => $request->message_type ?? 'direct',
                'priority' => $request->priority ?? 'normal',
                'parent_message_id' => $request->parent_message_id,
                'has_attachments' => $request->hasFile('attachments'),
            ]);

            // Handle broadcast recipients
            if ($request->message_type === 'broadcast' && $request->has('recipients')) {
                foreach ($request->recipients as $recipient) {
                    MessageRecipient::create([
                        'message_id' => $message->id,
                        'recipient_id' => $recipient['id'],
                        'recipient_type' => $recipient['type'],
                    ]);
                }
            }

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('messages/' . date('Y/m'), 'public');

                    MessageAttachment::create([
                        'message_id' => $message->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }

            // Load relationships before sending notification
            $message->load(['receiver', 'recipients', 'attachments']);

            // Send notification to recipients
            $this->notificationService->notifyNewMessage($message);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $message,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark message as read
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsRead(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $message = Message::findOrFail($id);

        if ($message->isDirect()) {
            if ($message->receiver_id == $user->id && $message->receiver_type == $userType) {
                $message->markAsRead();
            }
        } elseif ($message->isBroadcast()) {
            $recipient = MessageRecipient::where('message_id', $message->id)
                ->forRecipient($user->id, $userType)
                ->first();
            if ($recipient) {
                $recipient->markAsRead();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
        ]);
    }

    /**
     * Mark message as unread
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsUnread(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $message = Message::findOrFail($id);

        if ($message->isDirect()) {
            if ($message->receiver_id == $user->id && $message->receiver_type == $userType) {
                $message->markAsUnread();
            }
        } elseif ($message->isBroadcast()) {
            $recipient = MessageRecipient::where('message_id', $message->id)
                ->forRecipient($user->id, $userType)
                ->first();
            if ($recipient) {
                $recipient->markAsUnread();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Message marked as unread',
        ]);
    }

    /**
     * Archive message
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function archive(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $message = Message::findOrFail($id);

        if ($message->isBroadcast()) {
            $recipient = MessageRecipient::where('message_id', $message->id)
                ->forRecipient($user->id, $userType)
                ->first();
            if ($recipient) {
                $recipient->archive();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Message archived',
        ]);
    }

    /**
     * Star message
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function star(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $message = Message::findOrFail($id);

        if ($message->isBroadcast()) {
            $recipient = MessageRecipient::where('message_id', $message->id)
                ->forRecipient($user->id, $userType)
                ->first();
            if ($recipient) {
                $recipient->star();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Message starred',
        ]);
    }

    /**
     * Unstar message
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function unstar(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $message = Message::findOrFail($id);

        if ($message->isBroadcast()) {
            $recipient = MessageRecipient::where('message_id', $message->id)
                ->forRecipient($user->id, $userType)
                ->first();
            if ($recipient) {
                $recipient->unstar();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Message unstarred',
        ]);
    }

    /**
     * Delete message (soft delete)
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $message = Message::findOrFail($id);

        // Only sender can delete message
        if ($message->sender_id != $user->id || $message->sender_type != $userType) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted',
        ]);
    }

    /**
     * Download attachment
     *
     * @param int $attachmentId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadAttachment(int $attachmentId)
    {
        $attachment = MessageAttachment::findOrFail($attachmentId);

        if (!Storage::exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Get unread message count
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $directCount = Message::receivedBy($user->id, $userType)
            ->direct()
            ->unread()
            ->count();

        $broadcastCount = MessageRecipient::forRecipient($user->id, $userType)
            ->unread()
            ->count();

        $totalCount = $directCount + $broadcastCount;

        return response()->json([
            'success' => true,
            'data' => [
                'direct' => $directCount,
                'broadcast' => $broadcastCount,
                'total' => $totalCount,
            ],
        ]);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Get user type from authenticated user
     */
    private function getUserType($user): string
    {
        return match (get_class($user)) {
            'App\Models\Teacher' => 'teacher',
            'App\Models\Student' => 'student',
            'App\Models\Admin' => 'admin',
            default => 'student',
        };
    }

    /**
     * Check if user can access message
     */
    private function userCanAccessMessage(Message $message, int $userId, string $userType): bool
    {
        // Sender can access
        if ($message->sender_id == $userId && $message->sender_type == $userType) {
            return true;
        }

        // Direct receiver can access
        if ($message->receiver_id == $userId && $message->receiver_type == $userType) {
            return true;
        }

        // Broadcast recipient can access
        if ($message->isBroadcast()) {
            $recipient = MessageRecipient::where('message_id', $message->id)
                ->forRecipient($userId, $userType)
                ->exists();
            return $recipient;
        }

        return false;
    }

    /**
     * Check if user is receiver of message
     */
    private function userIsReceiver(Message $message, int $userId, string $userType): bool
    {
        if ($message->isDirect()) {
            return $message->receiver_id == $userId && $message->receiver_type == $userType;
        }

        if ($message->isBroadcast()) {
            return MessageRecipient::where('message_id', $message->id)
                ->forRecipient($userId, $userType)
                ->exists();
        }

        return false;
    }
}
