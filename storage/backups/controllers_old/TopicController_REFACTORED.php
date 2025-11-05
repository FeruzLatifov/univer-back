<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\TopicService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Topic Controller (REFACTORED)
 */
class TopicController extends Controller
{
    use ApiResponse;

    protected TopicService $topicService;

    public function __construct(TopicService $topicService)
    {
        $this->topicService = $topicService;
    }

    /**
     * Get subject topics
     *
     * @OA\Get(
     *     path="/api/v1/teacher/subjects/{id}/topics",
     *     tags={"Teacher - Topics"},
     *     summary="Get all topics for a subject",
     *     description="Returns a list of all topics for the specified subject",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="title", type="string", example="Introduction to Calculus"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="duration_hours", type="integer", example=4),
     *                     @OA\Property(property="order", type="integer"),
     *                     @OA\Property(property="is_completed", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Subject not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $topics = $this->topicService->getTopics($id, $teacher->employee->id);

        return $this->successResponse($topics);
    }

    /**
     * Create topic
     *
     * @OA\Post(
     *     path="/api/v1/teacher/subjects/{id}/topics",
     *     tags={"Teacher - Topics"},
     *     summary="Create a new topic",
     *     description="Creates a new topic for the specified subject",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title"},
     *             @OA\Property(property="title", type="string", maxLength=255, example="Introduction to Calculus"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="duration_hours", type="integer", minimum=1, nullable=true, example=4),
     *             @OA\Property(property="learning_outcomes", type="array", @OA\Items(type="string"), nullable=true),
     *             @OA\Property(property="resources", type="array", @OA\Items(type="string"), nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Topic created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Topic created successfully"),
     *                 @OA\Property(property="topic", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_hours' => 'nullable|integer|min:1',
            'learning_outcomes' => 'nullable|array',
            'resources' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $topic = $this->topicService->createTopic($id, $teacher->employee->id, $request->all());

        return $this->successResponse([
            'message' => 'Topic created successfully',
            'topic' => $topic,
        ], 201);
    }

    /**
     * Update topic
     *
     * @OA\Put(
     *     path="/api/v1/teacher/subjects/{subjectId}/topics/{topicId}",
     *     tags={"Teacher - Topics"},
     *     summary="Update a topic",
     *     description="Updates topic details. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subjectId",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="topicId",
     *         in="path",
     *         description="Topic ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", nullable=true),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="duration_hours", type="integer", nullable=true),
     *             @OA\Property(property="learning_outcomes", type="array", @OA\Items(type="string"), nullable=true),
     *             @OA\Property(property="resources", type="array", @OA\Items(type="string"), nullable=true),
     *             @OA\Property(property="is_completed", type="boolean", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Topic updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Topic updated successfully"),
     *                 @OA\Property(property="topic", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Topic or subject not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, int $subjectId, int $topicId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'duration_hours' => 'nullable|integer|min:1',
            'learning_outcomes' => 'nullable|array',
            'resources' => 'nullable|array',
            'is_completed' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $topic = $this->topicService->updateTopic($subjectId, $topicId, $teacher->employee->id, $request->all());

        return $this->successResponse([
            'message' => 'Topic updated successfully',
            'topic' => $topic,
        ]);
    }

    /**
     * Delete topic
     *
     * @OA\Delete(
     *     path="/api/v1/teacher/subjects/{subjectId}/topics/{topicId}",
     *     tags={"Teacher - Topics"},
     *     summary="Delete a topic",
     *     description="Permanently deletes a topic. Cannot be undone.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subjectId",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="topicId",
     *         in="path",
     *         description="Topic ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Topic deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="message", type="string"))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Topic or subject not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Request $request, int $subjectId, int $topicId): JsonResponse
    {
        $teacher = $request->user();

        $this->topicService->deleteTopic($subjectId, $topicId, $teacher->employee->id);

        return $this->successResponse(['message' => 'Topic deleted successfully']);
    }

    /**
     * Reorder topics
     *
     * @OA\Post(
     *     path="/api/v1/teacher/subjects/{id}/topics/reorder",
     *     tags={"Teacher - Topics"},
     *     summary="Reorder subject topics",
     *     description="Changes the order of topics in a subject",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"topic_ids"},
     *             @OA\Property(
     *                 property="topic_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={3, 1, 5, 2},
     *                 description="Array of topic IDs in desired order"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Topics reordered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="message", type="string"))
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function reorder(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'topic_ids' => 'required|array',
            'topic_ids.*' => 'integer|exists:e_subject_topic,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $this->topicService->reorderTopics($id, $teacher->employee->id, $request->input('topic_ids'));

        return $this->successResponse(['message' => 'Topics reordered successfully']);
    }

    /**
     * Get syllabus overview
     *
     * @OA\Get(
     *     path="/api/v1/teacher/subjects/{id}/syllabus",
     *     tags={"Teacher - Topics"},
     *     summary="Get subject syllabus",
     *     description="Returns a complete syllabus overview with all topics and their learning outcomes",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="subject", type="object"),
     *                 @OA\Property(property="topics", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="total_hours", type="integer"),
     *                 @OA\Property(property="completed_topics", type="integer"),
     *                 @OA\Property(property="progress_percentage", type="number")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Subject not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function syllabus(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $syllabus = $this->topicService->getSyllabus($id, $teacher->employee->id);

        return $this->successResponse($syllabus);
    }
}
