<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MessageRecipient Model
 *
 * Handles recipients for broadcast messages
 * One message can have multiple recipients
 */
class MessageRecipient extends Model
{
    use HasFactory;

    protected $table = 'message_recipients';

    /**
     * Disable updated_at timestamp (we only track created_at)
     */
    const UPDATED_AT = null;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'message_id',
        'recipient_id',
        'recipient_type',
        'is_read',
        'read_at',
        'is_archived',
        'is_starred',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_read' => 'boolean',
        'is_archived' => 'boolean',
        'is_starred' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the message
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Get recipient (polymorphic)
     */
    public function recipient()
    {
        return $this->morphTo('recipient', 'recipient_type', 'recipient_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope for unread recipients
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read recipients
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for archived recipients
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope for starred recipients
     */
    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    /**
     * Scope for specific recipient
     */
    public function scopeForRecipient($query, $recipientId, $recipientType)
    {
        return $query->where('recipient_id', $recipientId)
                     ->where('recipient_type', $recipientType);
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
     * Archive the message
     */
    public function archive()
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * Unarchive the message
     */
    public function unarchive()
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * Star the message
     */
    public function star()
    {
        $this->update(['is_starred' => true]);
    }

    /**
     * Unstar the message
     */
    public function unstar()
    {
        $this->update(['is_starred' => false]);
    }

    /**
     * Toggle starred status
     */
    public function toggleStar()
    {
        $this->update(['is_starred' => !$this->is_starred]);
    }

    /**
     * Get recipient name
     */
    public function getRecipientNameAttribute()
    {
        if ($this->recipient) {
            if (method_exists($this->recipient, 'getFullNameAttribute')) {
                return $this->recipient->full_name;
            }
            if (isset($this->recipient->firstname) && isset($this->recipient->lastname)) {
                return $this->recipient->firstname . ' ' . $this->recipient->lastname;
            }
        }
        return 'Unknown';
    }
}
