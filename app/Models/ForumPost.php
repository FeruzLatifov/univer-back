<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Translatable;

class ForumPost extends Model
{
    use HasFactory, SoftDeletes, Translatable;

    protected $table = 'forum_posts';

    protected $fillable = [
        'topic_id', 'author_id', 'author_type', 'body', 'parent_post_id',
        'is_approved', 'is_best_answer', 'is_edited', 'edited_at', 'edited_by',
        'likes_count', '_translations',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_best_answer' => 'boolean',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
        'likes_count' => 'integer',
        '_translations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $translatable = ['body'];

    // Relationships
    public function topic() { return $this->belongsTo(ForumTopic::class, 'topic_id'); }
    public function author() { return $this->morphTo('author', 'author_type', 'author_id'); }
    public function parent() { return $this->belongsTo(ForumPost::class, 'parent_post_id'); }
    public function replies() { return $this->hasMany(ForumPost::class, 'parent_post_id'); }
    public function attachments() { return $this->morphMany(ForumAttachment::class, 'attachable'); }
    public function likes() { return $this->morphMany(ForumLike::class, 'likeable'); }
    public function moderatorActions() { return $this->morphMany(ForumModeratorAction::class, 'target'); }

    // Scopes
    public function scopeApproved($query) { return $query->where('is_approved', true); }
    public function scopeBestAnswer($query) { return $query->where('is_best_answer', true); }
    public function scopeRootPosts($query) { return $query->whereNull('parent_post_id'); }

    // Methods
    public function markAsBestAnswer() { $this->update(['is_best_answer' => true]); }
    public function toggleLike($userId, $userType): bool
    {
        $like = $this->likes()->where('user_id', $userId)->where('user_type', $userType)->first();
        if ($like) {
            $like->delete();
            $this->decrement('likes_count');
            return false;
        }
        $this->likes()->create(['user_id' => $userId, 'user_type' => $userType]);
        $this->increment('likes_count');
        return true;
    }
}
