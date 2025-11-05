<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\ResourceService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Resource Controller (REFACTORED)
 */
class ResourceController extends Controller
{
    use ApiResponse;

    protected ResourceService $resourceService;

    public function __construct(ResourceService $resourceService)
    {
        $this->resourceService = $resourceService;
    }

    /**
     * Get subject resources
     *
     * @OA\Get(
     *     path="/api/v1/teacher/subjects/{id}/resources",
     *     tags={"Teacher - Resources"},
     *     summary="Get all resources for a subject",
     *     description="Returns a list of all learning resources for the specified subject",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Subject ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by resource type",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"file", "link", "video", "document", "presentation", "other"},
     *             example="file"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="topic_id",
     *         in="query",
     *         description="Filter by topic ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=2)
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
     *                     @OA\Property(property="title", type="string", example="Lecture Notes - Chapter 1"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="resource_type", type="string", example="file"),
     *                     @OA\Property(property="url", type="string", nullable=true),
     *                     @OA\Property(property="file_size", type="integer"),
     *                     @OA\Property(property="created_at", type="string", format="date-time")
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
        $filters = $request->only(['type', 'topic_id']);

        $resources = $this->resourceService->getResources($id, $teacher->employee->id, $filters);

        return $this->successResponse($resources);
    }

    /**
     * Upload resource
     *
     * @OA\Post(
     *     path="/api/v1/teacher/subjects/{id}/resources",
     *     tags={"Teacher - Resources"},
     *     summary="Upload a new resource",
     *     description="Uploads a new learning resource (file or link)",
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"title", "resource_type"},
     *                 @OA\Property(property="title", type="string", maxLength=255, example="Lecture Notes"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(
     *                     property="resource_type",
     *                     type="string",
     *                     enum={"file", "link", "video", "document", "presentation", "other"},
     *                     example="file"
     *                 ),
     *                 @OA\Property(property="topic_id", type="integer", nullable=true),
     *                 @OA\Property(property="url", type="string", format="url", description="Required if resource_type=link"),
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="Required if resource_type=file (max 50MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Resource uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Resource uploaded successfully"),
     *                 @OA\Property(property="resource", type="object")
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
            'resource_type' => 'required|in:file,link,video,document,presentation,other',
            'topic_id' => 'nullable|exists:e_subject_topic,id',
            'url' => 'required_if:resource_type,link|url',
            'file' => 'required_if:resource_type,file|file|max:51200', // 50MB
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $resource = $this->resourceService->uploadResource(
            $id,
            $teacher->employee->id,
            $request->all(),
            $request->file('file')
        );

        return $this->successResponse([
            'message' => 'Resource uploaded successfully',
            'resource' => $resource,
        ], 201);
    }

    /**
     * Update resource
     *
     * @OA\Put(
     *     path="/api/v1/teacher/resources/{id}",
     *     tags={"Teacher - Resources"},
     *     summary="Update a resource",
     *     description="Updates resource metadata. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Resource ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", nullable=true),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="topic_id", type="integer", nullable=true),
     *             @OA\Property(property="url", type="string", format="url", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resource updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Resource updated successfully"),
     *                 @OA\Property(property="resource", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Resource not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'topic_id' => 'nullable|exists:e_subject_topic,id',
            'url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $resource = $this->resourceService->updateResource($id, $teacher->employee->id, $request->all());

        return $this->successResponse([
            'message' => 'Resource updated successfully',
            'resource' => $resource,
        ]);
    }

    /**
     * Download resource
     *
     * @OA\Get(
     *     path="/api/v1/teacher/resources/{id}/download",
     *     tags={"Teacher - Resources"},
     *     summary="Download a resource file",
     *     description="Downloads the file for a resource",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Resource ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download",
     *         @OA\MediaType(
     *             mediaType="application/octet-stream",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Resource not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function download(Request $request, int $id)
    {
        $teacher = $request->user();

        $file = $this->resourceService->getResourceFile($id, $teacher->employee->id);

        return response()->download($file['path'], $file['name']);
    }

    /**
     * Delete resource
     *
     * @OA\Delete(
     *     path="/api/v1/teacher/resources/{id}",
     *     tags={"Teacher - Resources"},
     *     summary="Delete a resource",
     *     description="Permanently deletes a resource. Cannot be undone.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Resource ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resource deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="message", type="string"))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Resource not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $this->resourceService->deleteResource($id, $teacher->employee->id);

        return $this->successResponse(['message' => 'Resource deleted successfully']);
    }

    /**
     * Get resource types
     *
     * @OA\Get(
     *     path="/api/v1/teacher/resources/types",
     *     tags={"Teacher - Resources"},
     *     summary="Get available resource types",
     *     description="Returns a list of all available resource types",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="value", type="string", example="file"),
     *                     @OA\Property(property="label", type="string", example="File Upload")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function types(): JsonResponse
    {
        $types = $this->resourceService->getResourceTypes();

        return $this->successResponse($types);
    }
}
