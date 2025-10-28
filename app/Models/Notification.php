<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Translatable;

/**
 * Notification Model
 *
 * System-generated notifications
 * Examples: assignment_due, grade_posted, test_available, etc.
 */
class Notification extends Model
{
    use HasFactory, Translatable;

    protected $table = 'notifications';

    /**
     * Disable updated_at timestamp (we only track created_at)
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'user_type',
        'type',
        'title',
        'message',
        'entity_type',
        'entity_id',
        'action_url',
        'action_text',
        'is_read',
        'read_at',
        'priority',
        'sent_via_email',
        'sent_via_push',
        'email_sent_at',
        'push_sent_at',
        '_translations',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_read' => 'boolean',
        'sent_via_email' => 'boolean',
        'sent_via_push' => 'boolean',
        'read_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'push_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        '_translations' => 'array',
    ];

    /**
     * Translatable fields
     */
    protected $translatable = ['title', 'message', 'action_text'];

    /**
     * Notification types
     */
    // Assignment notifications
    const TYPE_ASSIGNMENT_DUE = 'assignment_due';
    const TYPE_ASSIGNMENT_GRADED = 'assignment_graded';
    const TYPE_ASSIGNMENT_POSTED = 'assignment_posted';
    const TYPE_ASSIGNMENT_SUBMITTED = 'assignment_submitted';

    // Test notifications
    const TYPE_TEST_AVAILABLE = 'test_available';
    const TYPE_TEST_ENDING_SOON = 'test_ending_soon';
    const TYPE_TEST_GRADED = 'test_graded';

    // Grade notifications
    const TYPE_GRADE_POSTED = 'grade_posted';
    const TYPE_GRADE_UPDATED = 'grade_updated';

    // Attendance notifications
    const TYPE_ATTENDANCE_MARKED = 'attendance_marked';
    const TYPE_ATTENDANCE_WARNING = 'attendance_warning';

    // General notifications
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_MESSAGE_RECEIVED = 'message_received';
    const TYPE_COMMENT_POSTED = 'comment_posted';

    /**
     * Priority levels
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * User types
     */
    const USER_TEACHER = 'teacher';
    const USER_STUDENT = 'student';
    const USER_ADMIN = 'admin';

    // ==================== RELATIONSHIPS ====================

    /**
     * Get user (polymorphic)
     */
    public function user()
    {
        return $this->morphTo('user', 'user_type', 'user_id');
    }

    /**
     * Get related entity (polymorphic)
     */
    public function entity()
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId, $userType)
    {
        return $query->where('user_id', $userId)
                     ->where('user_type', $userType);
    }

    /**
     * Scope for specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for high priority
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope for urgent priority
     */
    public function scopeUrgent($query)
    {
        return $query->where('priority', self::PRIORITY_URGENT);
    }

    /**
     * Scope for not expired
     */
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope for expired
     */
    public function scopeExpired($query)
    {
        return $query->whereNotNull('expires_at')
                     ->where('expires_at', '<=', now());
    }

    /**
     * Scope for recent notifications (last 7 days)
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(7));
    }

    /**
     * Scope for today's notifications
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ==================== METHODS ====================

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark as unread
     */
    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Check if notification is expired
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast();
    }

    /**
     * Check if notification is urgent
     */
    public function isUrgent(): bool
    {
        return $this->priority === self::PRIORITY_URGENT;
    }

    /**
     * Check if notification is high priority
     */
    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute()
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Past',
            self::PRIORITY_NORMAL => 'Oddiy',
            self::PRIORITY_HIGH => 'Yuqori',
            self::PRIORITY_URGENT => 'Shoshilinch',
            default => 'Oddiy',
        };
    }

    /**
     * Get priority color
     */
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_URGENT => 'red',
            default => 'blue',
        };
    }

    /**
     * Get notification icon
     */
    public function getIconAttribute()
    {
        return match($this->type) {
            self::TYPE_ASSIGNMENT_DUE => 'clock',
            self::TYPE_ASSIGNMENT_GRADED => 'check-circle',
            self::TYPE_ASSIGNMENT_POSTED => 'file-text',
            self::TYPE_ASSIGNMENT_SUBMITTED => 'upload',
            self::TYPE_TEST_AVAILABLE => 'clipboard',
            self::TYPE_TEST_ENDING_SOON => 'alert-circle',
            self::TYPE_TEST_GRADED => 'award',
            self::TYPE_GRADE_POSTED => 'star',
            self::TYPE_GRADE_UPDATED => 'edit',
            self::TYPE_ATTENDANCE_MARKED => 'check',
            self::TYPE_ATTENDANCE_WARNING => 'alert-triangle',
            self::TYPE_ANNOUNCEMENT => 'megaphone',
            self::TYPE_MESSAGE_RECEIVED => 'mail',
            self::TYPE_COMMENT_POSTED => 'message-circle',
            default => 'bell',
        };
    }

    /**
     * Get time ago (human-readable)
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Send notification via email
     */
    public function sendViaEmail()
    {
        // TODO: Implement email sending logic
        $this->update([
            'sent_via_email' => true,
            'email_sent_at' => now(),
        ]);
    }

    /**
     * Send notification via push
     */
    public function sendViaPush()
    {
        // TODO: Implement push notification logic
        $this->update([
            'sent_via_push' => true,
            'push_sent_at' => now(),
        ]);
    }
}
