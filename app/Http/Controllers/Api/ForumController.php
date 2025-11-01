<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\ForumPost;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * ForumController
 *
 * CRUD operations for forum categories, topics, and posts
 */
class ForumController extends Controller
{
    /**
     * Notification service instance
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Create a new controller instance
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    // ==================== CATEGORIES ====================

    /**
     * Get all categories
     */
    public function getCategories(Request $request): JsonResponse
    {
        $categories = ForumCategory::query()
            ->with(['children', 'lastPost.author'])
            ->active()
            ->root()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Get single category
     */
    public function getCategory(int $id): JsonResponse
    {
        $category = ForumCategory::with(['parent', 'children'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    // ==================== TOPICS ====================

    /**
     * Get topics in category
     */
    public function getTopics(int $categoryId, Request $request): JsonResponse
    {
        $query = ForumTopic::query()
            ->with(['author', 'category', 'lastPostAuthor'])
            ->inCategory($categoryId)
            ->approved();

        // Search
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Sort
        $sort = $request->get('sort', 'recent');
        if ($sort === 'popular') {
            $query->popular();
        } else {
            $query->recentActivity();
        }

        // Pinned first
        $query->orderByDesc('is_pinned');

        $topics = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $topics,
        ]);
    }

    /**
     * Get single topic
     */
    public function getTopic(int $id, Request $request): JsonResponse
    {
        $topic = ForumTopic::with([
            'author',
            'category',
            'attachments',
            'posts' => fn($q) => $q->with(['author', 'attachments', 'replies'])->rootPosts()->approved()->latest(),
        ])->findOrFail($id);

        // Increment views
        $topic->incrementViews();

        return response()->json([
            'success' => true,
            'data' => $topic,
        ]);
    }

    /**
     * Create new topic
     */
    public function createTopic(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:forum_categories,id',
            'title' => 'required|string|max:500',
            'body' => 'required|string',
            'tags' => 'nullable|array',
            'attachments' => 'nullable|array|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $userType = $this->getUserType($user);

        // Check if user can post
        $category = ForumCategory::findOrFail($request->category_id);
        if (!$category->canUserPost($userType)) {
            return response()->json(['success' => false, 'message' => 'Cannot post in this category'], 403);
        }

        DB::beginTransaction();
        try {
            $topic = ForumTopic::create([
                'category_id' => $request->category_id,
                'author_id' => $user->id,
                'author_type' => $userType,
                'title' => $request->title,
                'slug' => Str::slug($request->title) . '-' . Str::random(6),
                'body' => $request->body,
                'tags' => $request->tags,
                'is_approved' => !$category->requires_approval,
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('forum/' . date('Y/m'), 'public');
                    $topic->attachments()->create([
                        'uploaded_by' => $user->id,
                        'uploader_type' => $userType,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }

            // Update category stats
            $category->incrementTopics();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Topic created successfully',
                'data' => $topic->load(['author', 'category', 'attachments']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create topic', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update topic
     */
    public function updateTopic(int $id, Request $request): JsonResponse
    {
        $topic = ForumTopic::findOrFail($id);
        $user = $request->user();
        $userType = $this->getUserType($user);

        // Check ownership
        if ($topic->author_id != $user->id || $topic->author_type != $userType) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'tags' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $topic->update($request->only(['title', 'body', 'tags']));

        return response()->json([
            'success' => true,
            'message' => 'Topic updated successfully',
            'data' => $topic,
        ]);
    }

    /**
     * Delete topic
     */
    public function deleteTopic(int $id, Request $request): JsonResponse
    {
        $topic = ForumTopic::findOrFail($id);
        $user = $request->user();
        $userType = $this->getUserType($user);

        if ($topic->author_id != $user->id || $topic->author_type != $userType) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $topic->category->decrementTopics();
        $topic->delete();

        return response()->json(['success' => true, 'message' => 'Topic deleted']);
    }

    // ==================== POSTS ====================

    /**
     * Create post (reply)
     */
    public function createPost(int $topicId, Request $request): JsonResponse
    {
        $topic = ForumTopic::findOrFail($topicId);

        if (!$topic->canReply()) {
            return response()->json(['success' => false, 'message' => 'Cannot reply to this topic'], 403);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
            'parent_post_id' => 'nullable|exists:forum_posts,id',
            'attachments' => 'nullable|array|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $userType = $this->getUserType($user);

        DB::beginTransaction();
        try {
            $post = ForumPost::create([
                'topic_id' => $topicId,
                'author_id' => $user->id,
                'author_type' => $userType,
                'body' => $request->body,
                'parent_post_id' => $request->parent_post_id,
            ]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('forum/' . date('Y/m'), 'public');
                    $post->attachments()->create([
                        'uploaded_by' => $user->id,
                        'uploader_type' => $userType,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);
                }
            }

            // Update topic stats
            $topic->incrementPosts();
            $topic->updateLastPost($post);

            // Update category stats
            $topic->category->incrementPosts();
            $topic->category->updateLastPost($post->id);

            // Send notification to topic author and subscribers
            $this->notificationService->notifyForumReply($post, $topic);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $post->load(['author', 'attachments']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create post', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update post
     */
    public function updatePost(int $id, Request $request): JsonResponse
    {
        $post = ForumPost::findOrFail($id);
        $user = $request->user();
        $userType = $this->getUserType($user);

        if ($post->author_id != $user->id || $post->author_type != $userType) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $post->update([
            'body' => $request->body,
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully',
            'data' => $post,
        ]);
    }

    /**
     * Delete post
     */
    public function deletePost(int $id, Request $request): JsonResponse
    {
        $post = ForumPost::findOrFail($id);
        $user = $request->user();
        $userType = $this->getUserType($user);

        if ($post->author_id != $user->id || $post->author_type != $userType) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $post->topic->decrementPosts();
        $post->delete();

        return response()->json(['success' => true, 'message' => 'Post deleted']);
    }

    // ==================== LIKES ====================

    /**
     * Toggle like on topic
     */
    public function toggleTopicLike(int $id, Request $request): JsonResponse
    {
        $topic = ForumTopic::findOrFail($id);
        $user = $request->user();
        $userType = $this->getUserType($user);

        $liked = $topic->toggleLike($user->id, $userType);

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $topic->likes_count,
        ]);
    }

    /**
     * Toggle like on post
     */
    public function togglePostLike(int $id, Request $request): JsonResponse
    {
        $post = ForumPost::findOrFail($id);
        $user = $request->user();
        $userType = $this->getUserType($user);

        $liked = $post->toggleLike($user->id, $userType);

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'likes_count' => $post->likes_count,
        ]);
    }

    // ==================== SUBSCRIPTIONS ====================

    /**
     * Subscribe to topic
     */
    public function subscribeToTopic(int $id, Request $request): JsonResponse
    {
        $topic = ForumTopic::findOrFail($id);
        $user = $request->user();
        $userType = $this->getUserType($user);

        $topic->subscriptions()->firstOrCreate([
            'user_id' => $user->id,
            'user_type' => $userType,
        ]);

        return response()->json(['success' => true, 'message' => 'Subscribed successfully']);
    }

    /**
     * Unsubscribe from topic
     */
    public function unsubscribeFromTopic(int $id, Request $request): JsonResponse
    {
        $topic = ForumTopic::findOrFail($id);
        $user = $request->user();
        $userType = $this->getUserType($user);

        $topic->subscriptions()
            ->where('user_id', $user->id)
            ->where('user_type', $userType)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Unsubscribed successfully']);
    }

    // ==================== HELPER METHODS ====================

    private function getUserType($user): string
    {
        return match (get_class($user)) {
            'App\Models\Teacher' => 'teacher',
            'App\Models\Student' => 'student',
            'App\Models\Admin' => 'admin',
            default => 'student',
        };
    }
}
