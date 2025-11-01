<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumLike extends Model
{
    protected $table = 'forum_likes';
    const UPDATED_AT = null;

    protected $fillable = [
        'likeable_id', 'likeable_type', 'user_id', 'user_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relationships
    public function likeable() { return $this->morphTo(); }
    public function user() { return $this->morphTo('user', 'user_type', 'user_id'); }
}
