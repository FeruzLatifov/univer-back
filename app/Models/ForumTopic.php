<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Translatable;

/**
 * ForumTopic Model
 *
 * Discussion topics in forum
 */
class ForumTopic extends Model
{
    use HasFactory, SoftDeletes, Translatable;

    protected $table = 'forum_topics';

    protected $fillable = [
        'category_id',
        'author_id',
        'author_type',
        'title',
        'slug',
        'body',
        'is_pinned',
        'is_locked',
        'is_approved',
        'is_featured',
        'tags',
        'views_count',
        'posts_count',
        'likes_count',
        'last_post_id',
        'last_post_author_id',
        'last_post_author_type',
        'last_post_at',
        '_translations',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'tags' => 'array',
        'views_count' => 'integer',
        'posts_count' => 'integer',
        'likes_count' => 'integer',
        'last_post_at' => 'datetime',
        '_translations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $translatable = ['title', 'body'];

    // ==================== RELATIONSHIPS ====================

    /**
     * Category
     */
    public function category()
    {
        return $this->belongsTo(ForumCategory::class, 'category_id');
    }

    /**
     * Author (polymorphic)
     */
    public function author()
    {
        return $this->morphTo('author', 'author_type', 'author_id');
    }

    /**
     * Posts/replies
     */
    public function posts()
    {
        return $this->hasMany(ForumPost::class, 'topic_id');
    }

    /**
     * Last post
     */
    public function lastPost()
    {
        return $this->belongsTo(ForumPost::class, 'last_post_id');
    }

    /**
     * Last post author (polymorphic)
     */
    public function lastPostAuthor()
    {
        return $this->morphTo('lastPostAuthor', 'last_post_author_type', 'last_post_author_id');
    }

    /**
     * Attachments
     */
    public function attachments()
    {
        return $this->morphMany(ForumAttachment::class, 'attachable');
    }

    /**
     * Likes
     */
    public function likes()
    {
        return $this->morphMany(ForumLike::class, 'likeable');
    }

    /**
     * Subscriptions
     */
    public function subscriptions()
    {
        return $this->morphMany(ForumSubscription::class, 'subscribable');
    }

    /**
     * Moderator actions
     */
    public function moderatorActions()
    {
        return $this->morphMany(ForumModeratorAction::class, 'target');
    }

    // ==================== SCOPES ====================

    /**
     * Approved topics
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Pinned topics
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Featured topics
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Not locked
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    /**
     * By category
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * By author
     */
    public function scopeByAuthor($query, $authorId, $authorType)
    {
        return $query->where('author_id', $authorId)
                     ->where('author_type', $authorType);
    }

    /**
     * Search by title or body
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'ILIKE', "%{$term}%")
              ->orWhere('body', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Popular topics (high views or posts)
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('views_count');
    }

    /**
     * Recent activity
     */
    public function scopeRecentActivity($query)
    {
        return $query->orderByDesc('last_post_at');
    }

    // ==================== METHODS ====================

    /**
     * Increment views
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * Increment posts count
     */
    public function incrementPosts()
    {
        $this->increment('posts_count');
    }

    /**
     * Decrement posts count
     */
    public function decrementPosts()
    {
        $this->decrement('posts_count');
    }

    /**
     * Update last post info
     */
    public function updateLastPost(ForumPost $post)
    {
        $this->update([
            'last_post_id' => $post->id,
            'last_post_author_id' => $post->author_id,
            'last_post_author_type' => $post->author_type,
            'last_post_at' => $post->created_at,
        ]);
    }

    /**
     * Check if user can reply
     */
    public function canReply(): bool
    {
        return $this->is_approved && !$this->is_locked;
    }

    /**
     * Toggle like for user
     */
    public function toggleLike($userId, $userType): bool
    {
        $like = $this->likes()
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->first();

        if ($like) {
            $like->delete();
            $this->decrement('likes_count');
            return false;
        } else {
            $this->likes()->create([
                'user_id' => $userId,
                'user_type' => $userType,
            ]);
            $this->increment('likes_count');
            return true;
        }
    }

    /**
     * Check if user has liked
     */
    public function isLikedBy($userId, $userType): bool
    {
        return $this->likes()
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->exists();
    }
}
