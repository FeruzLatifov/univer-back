<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * NotificationSettings Model
 *
 * User preferences for notification delivery channels
 * Controls how each notification type should be delivered (email/push/sms/in-app)
 */
class NotificationSettings extends Model
{
    use HasFactory;

    protected $table = 'notification_settings';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'user_type',
        'notification_type',
        'email_enabled',
        'push_enabled',
        'sms_enabled',
        'in_app_enabled',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'in_app_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * User types
     */
    const USER_TEACHER = 'teacher';
    const USER_STUDENT = 'student';
    const USER_ADMIN = 'admin';

    /**
     * Default notification types
     */
    const DEFAULT_TYPES = [
        // Assignment
        'assignment_due',
        'assignment_graded',
        'assignment_posted',
        'assignment_submitted',
        // Test
        'test_available',
        'test_ending_soon',
        'test_graded',
        // Grade
        'grade_posted',
        'grade_updated',
        // Attendance
        'attendance_marked',
        'attendance_warning',
        // General
        'announcement',
        'message_received',
        'comment_posted',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get user (polymorphic)
     */
    public function user()
    {
        return $this->morphTo('user', 'user_type', 'user_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId, $userType)
    {
        return $query->where('user_id', $userId)
                     ->where('user_type', $userType);
    }

    /**
     * Scope for specific notification type
     */
    public function scopeForType($query, $type)
    {
        return $query->where('notification_type', $type);
    }

    /**
     * Scope for email enabled
     */
    public function scopeEmailEnabled($query)
    {
        return $query->where('email_enabled', true);
    }

    /**
     * Scope for push enabled
     */
    public function scopePushEnabled($query)
    {
        return $query->where('push_enabled', true);
    }

    /**
     * Scope for SMS enabled
     */
    public function scopeSmsEnabled($query)
    {
        return $query->where('sms_enabled', true);
    }

    /**
     * Scope for in-app enabled
     */
    public function scopeInAppEnabled($query)
    {
        return $query->where('in_app_enabled', true);
    }

    // ==================== STATIC METHODS ====================

    /**
     * Create default settings for a user
     */
    public static function createDefaultsForUser($userId, $userType)
    {
        $settings = [];

        foreach (self::DEFAULT_TYPES as $type) {
            $settings[] = [
                'user_id' => $userId,
                'user_type' => $userType,
                'notification_type' => $type,
                'email_enabled' => self::getDefaultEmailEnabled($type),
                'push_enabled' => self::getDefaultPushEnabled($type),
                'sms_enabled' => false, // SMS disabled by default
                'in_app_enabled' => true, // In-app always enabled by default
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        self::insert($settings);
    }

    /**
     * Get default email enabled status for notification type
     */
    private static function getDefaultEmailEnabled($type): bool
    {
        // Enable email by default for important notifications
        $emailEnabledTypes = [
            'assignment_due',
            'assignment_graded',
            'test_available',
            'test_graded',
            'grade_posted',
            'attendance_warning',
            'announcement',
        ];

        return in_array($type, $emailEnabledTypes);
    }

    /**
     * Get default push enabled status for notification type
     */
    private static function getDefaultPushEnabled($type): bool
    {
        // Enable push for most notifications
        $pushDisabledTypes = [
            'comment_posted', // Too frequent
        ];

        return !in_array($type, $pushDisabledTypes);
    }

    /**
     * Get or create setting for user and type
     */
    public static function getOrCreate($userId, $userType, $notificationType)
    {
        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'user_type' => $userType,
                'notification_type' => $notificationType,
            ],
            [
                'email_enabled' => self::getDefaultEmailEnabled($notificationType),
                'push_enabled' => self::getDefaultPushEnabled($notificationType),
                'sms_enabled' => false,
                'in_app_enabled' => true,
            ]
        );
    }

    /**
     * Check if notification should be sent via email
     */
    public static function shouldSendEmail($userId, $userType, $notificationType): bool
    {
        $setting = self::getOrCreate($userId, $userType, $notificationType);
        return $setting->email_enabled;
    }

    /**
     * Check if notification should be sent via push
     */
    public static function shouldSendPush($userId, $userType, $notificationType): bool
    {
        $setting = self::getOrCreate($userId, $userType, $notificationType);
        return $setting->push_enabled;
    }

    /**
     * Check if notification should be sent via SMS
     */
    public static function shouldSendSms($userId, $userType, $notificationType): bool
    {
        $setting = self::getOrCreate($userId, $userType, $notificationType);
        return $setting->sms_enabled;
    }

    /**
     * Check if notification should be shown in-app
     */
    public static function shouldShowInApp($userId, $userType, $notificationType): bool
    {
        $setting = self::getOrCreate($userId, $userType, $notificationType);
        return $setting->in_app_enabled;
    }

    // ==================== METHODS ====================

    /**
     * Enable email notifications
     */
    public function enableEmail()
    {
        $this->update(['email_enabled' => true]);
    }

    /**
     * Disable email notifications
     */
    public function disableEmail()
    {
        $this->update(['email_enabled' => false]);
    }

    /**
     * Enable push notifications
     */
    public function enablePush()
    {
        $this->update(['push_enabled' => true]);
    }

    /**
     * Disable push notifications
     */
    public function disablePush()
    {
        $this->update(['push_enabled' => false]);
    }

    /**
     * Enable SMS notifications
     */
    public function enableSms()
    {
        $this->update(['sms_enabled' => true]);
    }

    /**
     * Disable SMS notifications
     */
    public function disableSms()
    {
        $this->update(['sms_enabled' => false]);
    }

    /**
     * Enable in-app notifications
     */
    public function enableInApp()
    {
        $this->update(['in_app_enabled' => true]);
    }

    /**
     * Disable in-app notifications
     */
    public function disableInApp()
    {
        $this->update(['in_app_enabled' => false]);
    }

    /**
     * Enable all channels
     */
    public function enableAll()
    {
        $this->update([
            'email_enabled' => true,
            'push_enabled' => true,
            'sms_enabled' => true,
            'in_app_enabled' => true,
        ]);
    }

    /**
     * Disable all channels (except in-app, which is always recommended)
     */
    public function disableAll()
    {
        $this->update([
            'email_enabled' => false,
            'push_enabled' => false,
            'sms_enabled' => false,
            // Keep in_app_enabled as is
        ]);
    }

    /**
     * Get enabled channels as array
     */
    public function getEnabledChannelsAttribute(): array
    {
        $channels = [];

        if ($this->email_enabled) {
            $channels[] = 'email';
        }
        if ($this->push_enabled) {
            $channels[] = 'push';
        }
        if ($this->sms_enabled) {
            $channels[] = 'sms';
        }
        if ($this->in_app_enabled) {
            $channels[] = 'in_app';
        }

        return $channels;
    }
}
