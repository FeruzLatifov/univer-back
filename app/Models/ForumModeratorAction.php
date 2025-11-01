<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumModeratorAction extends Model
{
    protected $table = 'forum_moderator_actions';
    const UPDATED_AT = null;

    protected $fillable = [
        'moderator_id', 'moderator_type', 'target_id', 'target_type',
        'action', 'reason', 'metadata', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    // Constants
    const ACTION_APPROVE = 'approve';
    const ACTION_REJECT = 'reject';
    const ACTION_DELETE = 'delete';
    const ACTION_LOCK = 'lock';
    const ACTION_UNLOCK = 'unlock';
    const ACTION_PIN = 'pin';
    const ACTION_UNPIN = 'unpin';
    const ACTION_FEATURE = 'feature';
    const ACTION_EDIT = 'edit';

    // Relationships
    public function moderator() { return $this->morphTo('moderator', 'moderator_type', 'moderator_id'); }
    public function target() { return $this->morphTo(); }

    // Scopes
    public function scopeByModerator($query, $moderatorId) { return $query->where('moderator_id', $moderatorId); }
    public function scopeByAction($query, $action) { return $query->where('action', $action); }
}
