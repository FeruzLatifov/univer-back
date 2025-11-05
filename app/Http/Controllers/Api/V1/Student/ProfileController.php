<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use App\Http\Requests\Student\UpdatePasswordRequest;
use App\Services\Student\ProfileService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Student Profile Controller
 *
 * MODULAR MONOLITH - Student Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Student
 */
class ProfileController extends Controller
{
    use ApiResponse;

    /**
     * Profile Service (injected)
     */
    private ProfileService $profileService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Get authenticated student profile
     *
     * @OA\Get(
     *     path="/api/v1/student/profile",
     *     tags={"Student - Profile"},
     *     summary="Get student profile",
     *     description="Returns complete profile information for the authenticated student including personal details, contact information, and academic data",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="student_id", type="string", example="20210001"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="middle_name", type="string", nullable=true, example="Michael"),
     *                 @OA\Property(property="email", type="string", example="john.doe@university.edu"),
     *                 @OA\Property(property="phone", type="string", nullable=true, example="+998901234567"),
     *                 @OA\Property(property="current_address", type="string", nullable=true, example="Tashkent, Uzbekistan"),
     *                 @OA\Property(property="telegram_username", type="string", nullable=true, example="@johndoe"),
     *                 @OA\Property(property="photo_url", type="string", nullable=true, example="/storage/photos/123.jpg"),
     *                 @OA\Property(property="specialty", type="string", example="Computer Science"),
     *                 @OA\Property(property="group", type="string", example="CS-101"),
     *                 @OA\Property(property="semester", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $student = auth('student-api')->user();

            // Delegate to service
            $profileData = $this->profileService->getProfile($student);

            return $this->successResponse($profileData);

        } catch (\Exception $e) {
            Log::error('Failed to fetch student profile', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return $this->serverErrorResponse('Profilni yuklashda xatolik yuz berdi');
        }
    }

    /**
     * Update student profile
     *
     * @OA\Put(
     *     path="/api/v1/student/profile",
     *     tags={"Student - Profile"},
     *     summary="Update student profile",
     *     description="Updates student's editable profile information (phone, email, address, telegram)",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="phone", type="string", nullable=true, example="+998901234567", description="Phone number"),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="newemail@university.edu", description="Email address"),
     *             @OA\Property(property="current_address", type="string", nullable=true, example="New Address, Tashkent", description="Current residential address"),
     *             @OA\Property(property="telegram_username", type="string", nullable=true, example="@newusername", description="Telegram username")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Profil muvaffaqiyatli yangilandi")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $student = auth('student-api')->user();

            // Delegate to service
            $updatedData = $this->profileService->updateProfile(
                $student,
                $request->only(['phone', 'email', 'current_address', 'telegram_username'])
            );

            return $this->successResponse($updatedData, 'Profil muvaffaqiyatli yangilandi');

        } catch (\Exception $e) {
            Log::error('Failed to update student profile', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Change student password
     *
     * @OA\Put(
     *     path="/api/v1/student/password",
     *     tags={"Student - Profile"},
     *     summary="Change student password",
     *     description="Updates the student's account password",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password", "password_confirmation"},
     *             @OA\Property(property="password", type="string", format="password", minLength=6, example="newPassword123", description="New password (min 6 characters)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="newPassword123", description="Password confirmation")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Password updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="message", type="string", example="Parol muvaffaqiyatli o'zgartirildi")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        try {
            $student = auth('student-api')->user();

            // Delegate to service
            $this->profileService->updatePassword($student, $request->password);

            return $this->successResponse([], 'Parol muvaffaqiyatli o\'zgartirildi');

        } catch (\Exception $e) {
            Log::error('Failed to update student password', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Upload student photo
     *
     * @OA\Post(
     *     path="/api/v1/student/photo",
     *     tags={"Student - Profile"},
     *     summary="Upload student profile photo",
     *     description="Uploads or updates the student's profile photo. Accepts JPG, JPEG, PNG formats, max 2MB",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"photo"},
     *                 @OA\Property(
     *                     property="photo",
     *                     type="string",
     *                     format="binary",
     *                     description="Profile photo (JPG, JPEG, PNG, max 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="photo_url", type="string", example="/storage/photos/123.jpg")
     *             ),
     *             @OA\Property(property="message", type="string", example="Rasm muvaffaqiyatli yuklandi")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'photo' => [
                    'required',
                    'image',
                    'mimes:jpg,jpeg,png',
                    'max:2048', // 2MB max
                ],
            ], [
                'photo.required' => 'Rasm tanlanmagan',
                'photo.image' => 'Faqat rasm fayl yuklash mumkin',
                'photo.mimes' => 'Faqat JPG, JPEG, PNG formatdagi rasmlar qabul qilinadi',
                'photo.max' => 'Rasm hajmi 2MB dan oshmasligi kerak',
            ]);

            $student = auth('student-api')->user();

            // Delegate to service
            $result = $this->profileService->uploadPhoto($student, $request->file('photo'));

            return $this->successResponse($result, 'Rasm muvaffaqiyatli yuklandi');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors());

        } catch (\Exception $e) {
            Log::error('Failed to upload student photo', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Delete student photo
     *
     * @OA\Delete(
     *     path="/api/v1/student/photo",
     *     tags={"Student - Profile"},
     *     summary="Delete student profile photo",
     *     description="Removes the student's profile photo",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Photo deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items()),
     *             @OA\Property(property="message", type="string", example="Rasm o'chirildi")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        try {
            $student = auth('student-api')->user();

            // Delegate to service
            $this->profileService->deletePhoto($student);

            return $this->successResponse([], 'Rasm o\'chirildi');

        } catch (\Exception $e) {
            Log::error('Failed to delete student photo', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
