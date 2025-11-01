<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdminResource;
use Illuminate\Http\Request;

/**
 * Staff Self-Service Controller
 *
 * API Version: 1.0
 * Purpose: Xodimlarning o'z profilini boshqarishi (Self-Service Portal)
 */
class StaffController extends Controller
{
    /**
     * Get authenticated staff's profile
     *
     * @route GET /api/v1/staff/profile
     */
    public function myProfile()
    {
        $admin = auth('staff-api')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Staff topilmadi',
            ], 404);
        }

        $admin->load('employee.structure');

        return response()->json([
            'success' => true,
            'data' => new AdminResource($admin),
        ]);
    }

    /**
     * Update authenticated staff's profile
     *
     * @route PUT /api/v1/staff/profile
     */
    public function updateProfile(Request $request)
    {
        $admin = auth('staff-api')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Staff topilmadi',
            ], 404);
        }

        // Staff can only update limited fields in their employee record
        $validated = $request->validate([
            'phone' => 'sometimes|string|max:50',
            'email' => 'sometimes|email|max:100',
            'telegram_username' => 'nullable|string|max:50',
        ]);

        // Update employee record if exists
        if ($admin->employee) {
            $admin->employee->update($validated);
        }

        $admin->load('employee.structure');

        return response()->json([
            'success' => true,
            'data' => new AdminResource($admin),
            'message' => 'Profil muvaffaqiyatli yangilandi',
        ]);
    }

    /**
     * Upload staff avatar
     *
     * @route POST /api/v1/staff/profile/avatar
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
        ]);

        $admin = auth('staff-api')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Staff topilmadi',
            ], 404);
        }

        if (!$admin->employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee ma\'lumoti topilmadi',
            ], 404);
        }

        $uploadService = app(\App\Services\FileUploadService::class);

        try {
            // Delete old image if exists
            if ($admin->employee->image) {
                $uploadService->deleteImage($admin->employee->image);
            }

            // Upload new image
            $path = $uploadService->uploadImage($request->file('image'), 'employee', $admin->employee->id);

            // Update employee record
            $admin->employee->update(['image' => $path]);

            return response()->json([
                'success' => true,
                'data' => [
                    'image' => $path,
                    'image_url' => $uploadService->getImageUrl($path),
                ],
                'message' => 'Rasm muvaffaqiyatli yuklandi',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Rasm yuklashda xatolik: ' . $e->getMessage(),
            ], 500);
        }
    }
}
