<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ESubject;
use App\Models\ESubjectSchedule;
use App\Models\ESubjectResource;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Teacher Resource Controller
 *
 * Manages course materials and file resources
 */
class ResourceController extends Controller
{
    use ApiResponse;

    // Resource type constants
    const TYPE_LECTURE = '11';      // Ma'ruza
    const TYPE_PRACTICE = '12';     // Amaliyot
    const TYPE_ASSIGNMENT = '13';   // Topshiriq
    const TYPE_REFERENCE = '14';    // Qo'shimcha adabiyot
    const TYPE_EXAM = '15';         // Imtihon materiallari

    /**
     * Get all resources for a subject
     *
     * GET /api/v1/teacher/subject/{id}/resources
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        $resources = ESubjectResource::where('_subject', $id)
            ->where('active', true)
            ->with('teacher')
            ->orderBy('created_at', 'desc')
            ->get();

        $resourceList = $resources->map(function ($resource) {
            return [
                'id' => $resource->id,
                'name' => $resource->name,
                'description' => $resource->description,
                'filename' => $resource->filename,
                'size' => $resource->size,
                'formatted_size' => $resource->formatted_size,
                'mime_type' => $resource->mime_type,
                'type' => $resource->_resource_type,
                'type_name' => $resource->type_name,
                'uploaded_by' => $resource->teacher ? $resource->teacher->full_name : null,
                'uploaded_at' => $resource->created_at->format('Y-m-d H:i'),
                'download_url' => route('api.teacher.resource.download', $resource->id),
            ];
        });

        return $this->successResponse($resourceList, 'Resurslar ro\'yxati');
    }

    /**
     * Upload new resource
     *
     * POST /api/v1/teacher/subject/{id}/resource
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu fanga fayl yuklash huquqi yo\'q');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:256',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:11,12,13,14,15', // lecture, practice, assignment, reference, exam
            'file' => 'required|file|max:51200', // Max 50MB
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $file = $request->file('file');

            // Generate unique filename
            $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME))
                      . '.' . $file->getClientOriginalExtension();

            // Store file in subject-specific directory
            $path = $file->storeAs(
                'subjects/' . $id . '/resources',
                $filename,
                'public'
            );

            // Create resource record
            $resource = ESubjectResource::create([
                '_subject' => $id,
                '_employee' => $teacher->employee->id,
                'name' => $request->name,
                'description' => $request->description,
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                '_resource_type' => $request->type,
                'active' => true,
            ]);

            return $this->createdResponse([
                'id' => $resource->id,
                'name' => $resource->name,
                'filename' => $resource->filename,
                'size' => $resource->formatted_size,
                'type_name' => $resource->type_name,
                'download_url' => route('api.teacher.resource.download', $resource->id),
            ], 'Fayl muvaffaqiyatli yuklandi');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Fayl yuklashda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Update resource details
     *
     * PUT /api/v1/teacher/resource/{id}
     *
     * @param Request $request
     * @param int $id Resource ID
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $resource = ESubjectResource::findOrFail($id);

        // Verify teacher teaches this subject or owns this resource
        $hasAccess = ESubjectSchedule::where('_subject', $resource->_subject)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$hasAccess) {
            return $this->forbiddenResponse('Sizda bu resursni o\'zgartirish huquqi yo\'q');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:256',
            'description' => 'nullable|string|max:1000',
            'type' => 'sometimes|required|in:11,12,13,14,15',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $resource->update($request->only(['name', 'description', '_resource_type']));

        return $this->successResponse([
            'id' => $resource->id,
            'name' => $resource->name,
            'description' => $resource->description,
            'type_name' => $resource->type_name,
        ], 'Resurs yangilandi');
    }

    /**
     * Download resource
     *
     * GET /api/v1/teacher/resource/{id}/download
     *
     * @param Request $request
     * @param int $id Resource ID
     * @return mixed
     */
    public function download(Request $request, int $id)
    {
        $teacher = $request->user();

        $resource = ESubjectResource::findOrFail($id);

        // Verify teacher teaches this subject
        $hasAccess = ESubjectSchedule::where('_subject', $resource->_subject)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$hasAccess) {
            return $this->forbiddenResponse('Sizda bu faylni yuklab olish huquqi yo\'q');
        }

        // Check if file exists
        if (!Storage::disk('public')->exists($resource->path)) {
            return $this->notFoundResponse('Fayl topilmadi');
        }

        return Storage::disk('public')->download($resource->path, $resource->filename);
    }

    /**
     * Delete resource
     *
     * DELETE /api/v1/teacher/resource/{id}
     *
     * @param Request $request
     * @param int $id Resource ID
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $resource = ESubjectResource::findOrFail($id);

        // Verify teacher owns this resource
        if ($resource->_employee !== $teacher->employee->id) {
            return $this->forbiddenResponse('Siz faqat o\'zingiz yuklagan fayllarni o\'chira olasiz');
        }

        try {
            // Delete physical file
            if (Storage::disk('public')->exists($resource->path)) {
                Storage::disk('public')->delete($resource->path);
            }

            // Soft delete record
            $resource->update(['active' => false]);

            return $this->successResponse(null, 'Fayl o\'chirildi');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Fayl o\'chirishda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Get resource types list
     *
     * GET /api/v1/teacher/resource/types
     *
     * @return JsonResponse
     */
    public function types(): JsonResponse
    {
        $types = [
            ['value' => self::TYPE_LECTURE, 'label' => 'Ma\'ruza'],
            ['value' => self::TYPE_PRACTICE, 'label' => 'Amaliyot'],
            ['value' => self::TYPE_ASSIGNMENT, 'label' => 'Topshiriq'],
            ['value' => self::TYPE_REFERENCE, 'label' => 'Qo\'shimcha adabiyot'],
            ['value' => self::TYPE_EXAM, 'label' => 'Imtihon materiallari'],
        ];

        return $this->successResponse($types, 'Resurs turlari');
    }
}
