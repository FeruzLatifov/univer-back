<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationSettings;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * NotificationController
 *
 * Handles all notification operations:
 * - List notifications
 * - Mark as read/unread
 * - Notification settings
 * - Notification preferences
 */
class NotificationController extends Controller
{
    /**
     * Get all notifications for current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $query = Notification::query()
            ->forUser($user->id, $userType)
            ->notExpired()
            ->latest();

        // Filters
        if ($request->has('is_read')) {
            $isRead = filter_var($request->is_read, FILTER_VALIDATE_BOOLEAN);
            if ($isRead) {
                $query->read();
            } else {
                $query->unread();
            }
        }

        if ($request->has('type')) {
            $query->ofType($request->type);
        }

        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('high_priority')) {
            $highPriority = filter_var($request->high_priority, FILTER_VALIDATE_BOOLEAN);
            if ($highPriority) {
                $query->highPriority();
            }
        }

        if ($request->has('recent')) {
            $recent = filter_var($request->recent, FILTER_VALIDATE_BOOLEAN);
            if ($recent) {
                $query->recent();
            }
        }

        $notifications = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get unread notifications
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unread(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $notifications = Notification::query()
            ->forUser($user->id, $userType)
            ->unread()
            ->notExpired()
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get recent notifications (last 7 days)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $notifications = Notification::query()
            ->forUser($user->id, $userType)
            ->recent()
            ->notExpired()
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    /**
     * Get single notification
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $notification = Notification::query()
            ->forUser($user->id, $userType)
            ->findOrFail($id);

        // Auto mark as read
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => $notification,
        ]);
    }

    /**
     * Mark notification as read
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsRead(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $notification = Notification::query()
            ->forUser($user->id, $userType)
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
        ]);
    }

    /**
     * Mark notification as unread
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function markAsUnread(int $id, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $notification = Notification::query()
            ->forUser($user->id, $userType)
            ->findOrFail($id);

        $notification->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as unread',
        ]);
    }

    /**
     * Mark all notifications as read
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $updated = Notification::query()
            ->forUser($user->id, $userType)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => "Marked {$updated} notifications as read",
            'count' => $updated,
        ]);
    }

    /**
     * Get unread notification count
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $count = Notification::query()
            ->forUser($user->id, $userType)
            ->unread()
            ->notExpired()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'count' => $count,
            ],
        ]);
    }

    /**
     * Get notification statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $total = Notification::forUser($user->id, $userType)->count();
        $unread = Notification::forUser($user->id, $userType)->unread()->count();
        $read = Notification::forUser($user->id, $userType)->read()->count();
        $today = Notification::forUser($user->id, $userType)->today()->count();
        $recent = Notification::forUser($user->id, $userType)->recent()->count();
        $urgent = Notification::forUser($user->id, $userType)->urgent()->count();

        // By type
        $byType = Notification::forUser($user->id, $userType)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get()
            ->pluck('count', 'type');

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'unread' => $unread,
                'read' => $read,
                'today' => $today,
                'recent' => $recent,
                'urgent' => $urgent,
                'by_type' => $byType,
            ],
        ]);
    }

    // ==================== NOTIFICATION SETTINGS ====================

    /**
     * Get notification settings for current user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $settings = NotificationSettings::forUser($user->id, $userType)->get();

        // If no settings, create defaults
        if ($settings->isEmpty()) {
            NotificationSettings::createDefaultsForUser($user->id, $userType);
            $settings = NotificationSettings::forUser($user->id, $userType)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update notification settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.notification_type' => 'required|string',
            'settings.*.email_enabled' => 'nullable|boolean',
            'settings.*.push_enabled' => 'nullable|boolean',
            'settings.*.sms_enabled' => 'nullable|boolean',
            'settings.*.in_app_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $userType = $this->getUserType($user);

        foreach ($request->settings as $settingData) {
            $setting = NotificationSettings::getOrCreate(
                $user->id,
                $userType,
                $settingData['notification_type']
            );

            $setting->update([
                'email_enabled' => $settingData['email_enabled'] ?? $setting->email_enabled,
                'push_enabled' => $settingData['push_enabled'] ?? $setting->push_enabled,
                'sms_enabled' => $settingData['sms_enabled'] ?? $setting->sms_enabled,
                'in_app_enabled' => $settingData['in_app_enabled'] ?? $setting->in_app_enabled,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully',
        ]);
    }

    /**
     * Update single notification setting
     *
     * @param string $type
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSetting(string $type, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email_enabled' => 'nullable|boolean',
            'push_enabled' => 'nullable|boolean',
            'sms_enabled' => 'nullable|boolean',
            'in_app_enabled' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $userType = $this->getUserType($user);

        $setting = NotificationSettings::getOrCreate($user->id, $userType, $type);

        $setting->update($request->only([
            'email_enabled',
            'push_enabled',
            'sms_enabled',
            'in_app_enabled',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Setting updated successfully',
            'data' => $setting,
        ]);
    }

    /**
     * Enable all notifications for a type
     *
     * @param string $type
     * @param Request $request
     * @return JsonResponse
     */
    public function enableAll(string $type, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $setting = NotificationSettings::getOrCreate($user->id, $userType, $type);
        $setting->enableAll();

        return response()->json([
            'success' => true,
            'message' => 'All notification channels enabled',
        ]);
    }

    /**
     * Disable all notifications for a type
     *
     * @param string $type
     * @param Request $request
     * @return JsonResponse
     */
    public function disableAll(string $type, Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        $setting = NotificationSettings::getOrCreate($user->id, $userType, $type);
        $setting->disableAll();

        return response()->json([
            'success' => true,
            'message' => 'All notification channels disabled',
        ]);
    }

    /**
     * Reset settings to default
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $this->getUserType($user);

        // Delete all existing settings
        NotificationSettings::forUser($user->id, $userType)->delete();

        // Create new defaults
        NotificationSettings::createDefaultsForUser($user->id, $userType);

        return response()->json([
            'success' => true,
            'message' => 'Settings reset to default',
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
}
