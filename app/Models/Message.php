<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Translatable;

/**
 * Message Model
 *
 * Handles messages between users (teachers, students, admins)
 * Supports: direct messages, broadcast, announcements, replies
 */
class Message extends Model
{
    use HasFactory, SoftDeletes, Translatable;

    protected $table = 'messages';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sender_id',
        'sender_type',
        'receiver_id',
        'receiver_type',
        'subject',
        'body',
        'message_type',
        'priority',
        'is_read',
        'read_at',
        'has_attachments',
        'parent_message_id',
        '_translations',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_read' => 'boolean',
        'has_attachments' => 'boolean',
        'read_at' => 'datetime',
        '_translations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Translatable fields
     */
    protected $translatable = ['subject', 'body'];

    /**
     * Message types
     */
    const TYPE_DIRECT = 'direct';
    const TYPE_BROADCAST = 'broadcast';
    const TYPE_ANNOUNCEMENT = 'announcement';

    /**
     * Priority levels
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Sender types
     */
    const SENDER_TEACHER = 'teacher';
    const SENDER_STUDENT = 'student';
    const SENDER_ADMIN = 'admin';

    // ==================== RELATIONSHIPS ====================

    /**
     * Get sender (polymorphic)
     */
    public function sender()
    {
        return $this->morphTo('sender', 'sender_type', 'sender_id');
    }

    /**
     * Get receiver (polymorphic)
     */
    public function receiver()
    {
        return $this->morphTo('receiver', 'receiver_type', 'receiver_id');
    }

    /**
     * Message recipients (for broadcast messages)
     */
    public function recipients()
    {
        return $this->hasMany(MessageRecipient::class);
    }

    /**
     * Message attachments
     */
    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * Parent message (for replies)
     */
    public function parent()
    {
        return $this->belongsTo(Message::class, 'parent_message_id');
    }

    /**
     * Replies to this message
     */
    public function replies()
    {
        return $this->hasMany(Message::class, 'parent_message_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope for direct messages
     */
    public function scopeDirect($query)
    {
        return $query->where('message_type', self::TYPE_DIRECT);
    }

    /**
     * Scope for broadcast messages
     */
    public function scopeBroadcast($query)
    {
        return $query->where('message_type', self::TYPE_BROADCAST);
    }

    /**
     * Scope for announcements
     */
    public function scopeAnnouncement($query)
    {
        return $query->where('message_type', self::TYPE_ANNOUNCEMENT);
    }

    /**
     * Scope for unread messages
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read messages
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for messages sent by user
     */
    public function scopeSentBy($query, $userId, $userType)
    {
        return $query->where('sender_id', $userId)
                     ->where('sender_type', $userType);
    }

    /**
     * Scope for messages received by user
     */
    public function scopeReceivedBy($query, $userId, $userType)
    {
        return $query->where(function ($q) use ($userId, $userType) {
            // Direct messages
            $q->where(function ($subQ) use ($userId, $userType) {
                $subQ->where('receiver_id', $userId)
                     ->where('receiver_type', $userType);
            })
            // Or broadcast messages
            ->orWhereHas('recipients', function ($subQ) use ($userId, $userType) {
                $subQ->where('recipient_id', $userId)
                     ->where('recipient_type', $userType);
            });
        });
    }

    /**
     * Scope for high priority messages
     */
    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * Scope for messages with attachments
     */
    public function scopeWithAttachments($query)
    {
        return $query->where('has_attachments', true);
    }

    // ==================== METHODS ====================

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Mark message as unread
     */
    public function markAsUnread()
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Check if message is a broadcast
     */
    public function isBroadcast(): bool
    {
        return $this->message_type === self::TYPE_BROADCAST;
    }

    /**
     * Check if message is direct
     */
    public function isDirect(): bool
    {
        return $this->message_type === self::TYPE_DIRECT;
    }

    /**
     * Check if message is announcement
     */
    public function isAnnouncement(): bool
    {
        return $this->message_type === self::TYPE_ANNOUNCEMENT;
    }

    /**
     * Check if message is a reply
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_message_id);
    }

    /**
     * Get sender name
     */
    public function getSenderNameAttribute()
    {
        if ($this->sender) {
            if ($this->sender_type === self::SENDER_TEACHER) {
                return $this->sender->firstname . ' ' . $this->sender->lastname;
            } elseif ($this->sender_type === self::SENDER_STUDENT) {
                return $this->sender->firstname . ' ' . $this->sender->lastname;
            }
        }
        return 'Unknown';
    }

    /**
     * Get receiver name
     */
    public function getReceiverNameAttribute()
    {
        if ($this->receiver) {
            if ($this->receiver_type === self::SENDER_TEACHER) {
                return $this->receiver->firstname . ' ' . $this->receiver->lastname;
            } elseif ($this->receiver_type === self::SENDER_STUDENT) {
                return $this->receiver->firstname . ' ' . $this->receiver->lastname;
            }
        }
        return $this->isBroadcast() ? 'Broadcast' : 'Unknown';
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
}
