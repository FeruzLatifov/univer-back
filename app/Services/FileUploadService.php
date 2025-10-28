<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class FileUploadService
{
    /**
     * Upload student/employee image
     *
     * @param UploadedFile $file
     * @param string $type ('student' or 'employee')
     * @param int|null $userId
     * @return string Path to uploaded file
     */
    public function uploadImage(UploadedFile $file, string $type = 'student', ?int $userId = null): string
    {
        // Validate image
        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg'])) {
            throw new \InvalidArgumentException('Faqat JPG va PNG rasmlar qabul qilinadi');
        }

        // Max 5MB
        if ($file->getSize() > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('Rasm hajmi 5MB dan oshmasligi kerak');
        }

        // Generate unique filename
        $extension = $file->getClientOriginalExtension();
        $filename = $type . '_' . ($userId ?? Str::random(10)) . '_' . time() . '.' . $extension;

        // Define storage path
        $directory = 'images/' . $type . 's/' . date('Y/m');
        $path = $directory . '/' . $filename;

        // Resize image (max 800x800, maintain aspect ratio)
        $image = Image::make($file->getRealPath());
        $image->resize(800, 800, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Save to storage
        Storage::disk('public')->put($path, (string) $image->encode());

        return $path;
    }

    /**
     * Delete image from storage
     *
     * @param string $path
     * @return bool
     */
    public function deleteImage(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Get full URL for image
     *
     * @param string|null $path
     * @return string|null
     */
    public function getImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Upload any file (documents, etc.)
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return string
     */
    public function uploadFile(UploadedFile $file, string $directory = 'documents'): string
    {
        // Max 10MB for documents
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new \InvalidArgumentException('Fayl hajmi 10MB dan oshmasligi kerak');
        }

        $extension = $file->getClientOriginalExtension();
        $filename = Str::random(40) . '_' . time() . '.' . $extension;

        $path = $directory . '/' . date('Y/m') . '/' . $filename;

        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }
}
