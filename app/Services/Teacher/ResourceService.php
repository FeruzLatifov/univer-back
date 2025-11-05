<?php

namespace App\Services\Teacher;

use App\Models\ESubjectResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

/**
 * Teacher Resource Service
 *
 * Manages learning resources (files, links, materials)
 */
class ResourceService
{
    /**
     * Get resources for subject
     */
    public function getResources(int $subjectId, int $teacherId, array $filters = []): array
    {
        $this->verifyTeacherSubject($subjectId, $teacherId);

        $query = ESubjectResource::where('_subject', $subjectId)
            ->where('active', true);

        if (!empty($filters['type'])) {
            $query->where('resource_type', $filters['type']);
        }

        if (!empty($filters['topic_id'])) {
            $query->where('_topic', $filters['topic_id']);
        }

        $resources = $query->orderBy('created_at', 'desc')->get();

        return $resources->map(function ($resource) {
            return [
                'id' => $resource->id,
                'title' => $resource->title,
                'description' => $resource->description,
                'resource_type' => $resource->resource_type,
                'file_path' => $resource->file_path,
                'file_size' => $resource->file_size,
                'file_type' => $resource->file_type,
                'url' => $resource->url,
                'topic_id' => $resource->_topic,
                'downloads_count' => $resource->downloads_count ?? 0,
                'created_at' => $resource->created_at,
            ];
        })->toArray();
    }

    /**
     * Upload new resource
     */
    public function uploadResource(int $subjectId, int $teacherId, array $data, $file = null): ESubjectResource
    {
        $this->verifyTeacherSubject($subjectId, $teacherId);

        $resourceData = [
            '_subject' => $subjectId,
            '_topic' => $data['topic_id'] ?? null,
            '_employee' => $teacherId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'resource_type' => $data['resource_type'],
            'active' => true,
        ];

        if ($data['resource_type'] === 'file' && $file) {
            $path = $file->store('resources/' . $subjectId, 'public');
            $resourceData['file_path'] = $path;
            $resourceData['file_size'] = $file->getSize();
            $resourceData['file_type'] = $file->getMimeType();
        } elseif ($data['resource_type'] === 'link') {
            $resourceData['url'] = $data['url'];
        }

        return ESubjectResource::create($resourceData);
    }

    /**
     * Update resource
     */
    public function updateResource(int $resourceId, int $teacherId, array $data): ESubjectResource
    {
        $resource = ESubjectResource::findOrFail($resourceId);

        $this->verifyTeacherSubject($resource->_subject, $teacherId);

        $resource->update([
            'title' => $data['title'] ?? $resource->title,
            'description' => $data['description'] ?? $resource->description,
            '_topic' => $data['topic_id'] ?? $resource->_topic,
            'url' => $data['url'] ?? $resource->url,
        ]);

        return $resource;
    }

    /**
     * Delete resource
     */
    public function deleteResource(int $resourceId, int $teacherId): bool
    {
        $resource = ESubjectResource::findOrFail($resourceId);

        $this->verifyTeacherSubject($resource->_subject, $teacherId);

        // Delete file if exists
        if ($resource->file_path) {
            Storage::disk('public')->delete($resource->file_path);
        }

        return $resource->delete();
    }

    /**
     * Get resource file path for download
     */
    public function getResourceFile(int $resourceId, int $teacherId): array
    {
        $resource = ESubjectResource::findOrFail($resourceId);

        $this->verifyTeacherSubject($resource->_subject, $teacherId);

        // Increment downloads count
        $resource->increment('downloads_count');

        if (!$resource->file_path) {
            throw new \Exception('This resource does not have a file');
        }

        $filePath = storage_path('app/public/' . $resource->file_path);

        if (!file_exists($filePath)) {
            throw new \Exception('File not found');
        }

        return [
            'path' => $filePath,
            'name' => $resource->title . '.' . pathinfo($resource->file_path, PATHINFO_EXTENSION),
        ];
    }

    /**
     * Get resource types
     */
    public function getResourceTypes(): array
    {
        return [
            ['value' => 'file', 'label' => 'File Upload'],
            ['value' => 'link', 'label' => 'External Link'],
            ['value' => 'video', 'label' => 'Video'],
            ['value' => 'document', 'label' => 'Document'],
            ['value' => 'presentation', 'label' => 'Presentation'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }

    /**
     * Verify teacher has access to subject
     */
    protected function verifyTeacherSubject(int $subjectId, int $teacherId): void
    {
        $hasAccess = DB::table('e_subject_schedule')
            ->where('_subject', $subjectId)
            ->where('_employee', $teacherId)
            ->exists();

        if (!$hasAccess) {
            throw new \Exception('You do not have access to this subject');
        }
    }
}
