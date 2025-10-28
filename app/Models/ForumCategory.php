<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Translatable;

/**
 * ForumCategory Model
 *
 * Categories for organizing forum topics
 */
class ForumCategory extends Model
{
    use HasFactory, Translatable;

    protected $table = 'forum_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'color',
        'order',
        'is_active',
        'is_locked',
        'requires_approval',
        'allowed_user_types',
        'parent_id',
        'topics_count',
        'posts_count',
        'last_post_id',
        'last_post_at',
        '_translations',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'requires_approval' => 'boolean',
        'allowed_user_types' => 'array',
        'topics_count' => 'integer',
        'posts_count' => 'integer',
        'last_post_at' => 'datetime',
        '_translations' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $translatable = ['name', 'description'];

    // ==================== RELATIONSHIPS ====================

    /**
     * Parent category
     */
    public function parent()
    {
        return $this->belongsTo(ForumCategory::class, 'parent_id');
    }

    /**
     * Child categories (subcategories)
     */
    public function children()
    {
        return $this->hasMany(ForumCategory::class, 'parent_id')->orderBy('order');
    }

    /**
     * Topics in this category
     */
    public function topics()
    {
        return $this->hasMany(ForumTopic::class, 'category_id');
    }

    /**
     * Last post
     */
    public function lastPost()
    {
        return $this->belongsTo(ForumPost::class, 'last_post_id');
    }

    /**
     * Subscriptions
     */
    public function subscriptions()
    {
        return $this->morphMany(ForumSubscription::class, 'subscribable');
    }

    // ==================== SCOPES ====================

    /**
     * Active categories
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Root categories (no parent)
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Ordered by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Not locked
     */
    public function scopeUnlocked($query)
    {
        return $query->where('is_locked', false);
    }

    // ==================== METHODS ====================

    /**
     * Check if user can post in this category
     */
    public function canUserPost($userType): bool
    {
        if (!$this->is_active || $this->is_locked) {
            return false;
        }

        if (empty($this->allowed_user_types)) {
            return true; // All users allowed
        }

        return in_array($userType, $this->allowed_user_types);
    }

    /**
     * Increment topics count
     */
    public function incrementTopics()
    {
        $this->increment('topics_count');
    }

    /**
     * Decrement topics count
     */
    public function decrementTopics()
    {
        $this->decrement('topics_count');
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
    public function updateLastPost($postId = null)
    {
        if ($postId) {
            $this->update([
                'last_post_id' => $postId,
                'last_post_at' => now(),
            ]);
        } else {
            // Find the latest post
            $lastPost = ForumPost::whereHas('topic', function ($query) {
                $query->where('category_id', $this->id);
            })->latest()->first();

            if ($lastPost) {
                $this->update([
                    'last_post_id' => $lastPost->id,
                    'last_post_at' => $lastPost->created_at,
                ]);
            } else {
                $this->update([
                    'last_post_id' => null,
                    'last_post_at' => null,
                ]);
            }
        }
    }

    /**
     * Get breadcrumb path
     */
    public function getBreadcrumbAttribute(): array
    {
        $breadcrumb = [];
        $category = $this;

        while ($category) {
            array_unshift($breadcrumb, [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ]);
            $category = $category->parent;
        }

        return $breadcrumb;
    }
}
