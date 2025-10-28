<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumSubscription extends Model
{
    protected $table = 'forum_subscriptions';

    protected $fillable = [
        'subscribable_id', 'subscribable_type', 'user_id', 'user_type',
        'notify_email', 'notify_push', 'last_notified_at',
    ];

    protected $casts = [
        'notify_email' => 'boolean',
        'notify_push' => 'boolean',
        'last_notified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function subscribable() { return $this->morphTo(); }
    public function user() { return $this->morphTo('user', 'user_type', 'user_id'); }

    // Methods
    public function updateLastNotified() { $this->update(['last_notified_at' => now()]); }
}
