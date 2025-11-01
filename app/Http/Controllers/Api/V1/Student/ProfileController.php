<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use App\Http\Requests\Student\UpdatePasswordRequest;
use App\Models\System\EStudent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Student Profile Controller
 *
 * Handles student profile operations:
 * - View profile
 * - Update profile (phone, email, address)
 * - Change password
 * - Upload photo
 */
class ProfileController extends Controller
{
    /**
     * Get authenticated student profile
     *
     * GET /api/student/profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $student = auth('student-api')->user();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $student->id,
                    'student_id_number' => $student->student_id_number,
                    'first_name' => $student->first_name,
                    'second_name' => $student->second_name,
                    'third_name' => $student->third_name,
                    'full_name' => $student->getFullNameAttribute(),
                    'passport_number' => $student->passport_number,
                    'passport_pin' => $student->passport_pin,
                    'birth_date' => $student->birth_date,
                    'gender' => $student->gender,
                    'phone' => $student->phone,
                    'email' => $student->email,
                    'current_address' => $student->current_address,
                    'telegram_username' => $student->telegram_username,
                    'image' => $student->image,
                    '_faculty' => $student->_faculty,
                    '_specialty' => $student->_specialty,
                    '_group' => $student->_group,
                    '_curriculum' => $student->_curriculum,
                    '_level' => $student->_level,
                    '_education_type' => $student->_education_type,
                    '_education_form' => $student->_education_form,
                    '_payment_form' => $student->_payment_form,
                    'faculty' => $student->faculty,
                    'specialty' => $student->specialty,
                    'group' => $student->group,
                    'level' => $student->level,
                    'education_type' => $student->educationType,
                    'education_form' => $student->educationForm,
                    'payment_form' => $student->paymentForm,
                    'year_of_enter' => $student->year_of_enter,
                    'status' => $student->status,
                    'created_at' => $student->created_at,
                    'updated_at' => $student->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch student profile', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Profilni yuklashda xatolik yuz berdi',
            ], 500);
        }
    }

    /**
     * Update student profile
     *
     * PUT /api/student/profile
     *
     * @param UpdateProfileRequest $request
     * @return JsonResponse
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $student = auth('student-api')->user();

            DB::beginTransaction();

            // Update only allowed fields
            $student->update($request->only([
                'phone',
                'email',
                'current_address',
                'telegram_username',
            ]));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Profil muvaffaqiyatli yangilandi',
                'data' => [
                    'phone' => $student->phone,
                    'email' => $student->email,
                    'current_address' => $student->current_address,
                    'telegram_username' => $student->telegram_username,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update student profile', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Profilni yangilashda xatolik yuz berdi',
            ], 500);
        }
    }

    /**
     * Change student password
     *
     * PUT /api/student/password
     *
     * @param UpdatePasswordRequest $request
     * @return JsonResponse
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        try {
            $student = auth('student-api')->user();

            DB::beginTransaction();

            // Update password
            $student->password = Hash::make($request->password);
            $student->save();

            // Invalidate all other tokens (optional - for security)
            // This will logout user from all other devices
            // DB::table('student_api_tokens')
            //     ->where('student_id', $student->id)
            //     ->where('token', '!=', $request->bearerToken())
            //     ->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Parol muvaffaqiyatli o\'zgartirildi',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update student password', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Parolni o\'zgartirishda xatolik yuz berdi',
            ], 500);
        }
    }

    /**
     * Upload student photo
     *
     * POST /api/student/photo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function uploadPhoto(Request $request): JsonResponse
    {
        try {
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

            DB::beginTransaction();

            // Delete old photo if exists
            if ($student->image && Storage::disk('public')->exists($student->image)) {
                Storage::disk('public')->delete($student->image);
            }

            // Store new photo
            $path = $request->file('photo')->store('students/photos', 'public');

            // Update student image path
            $student->image = $path;
            $student->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rasm muvaffaqiyatli yuklandi',
                'data' => [
                    'image' => $path,
                    'url' => Storage::disk('public')->url($path),
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validatsiya xatosi',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to upload student photo', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Rasmni yuklashda xatolik yuz berdi',
            ], 500);
        }
    }

    /**
     * Delete student photo
     *
     * DELETE /api/student/photo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deletePhoto(Request $request): JsonResponse
    {
        try {
            $student = auth('student-api')->user();

            if (!$student->image) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rasm mavjud emas',
                ], 404);
            }

            DB::beginTransaction();

            // Delete photo from storage
            if (Storage::disk('public')->exists($student->image)) {
                Storage::disk('public')->delete($student->image);
            }

            // Clear image field
            $student->image = null;
            $student->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rasm o\'chirildi',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete student photo', [
                'error' => $e->getMessage(),
                'student_id' => auth('student-api')->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Rasmni o\'chirishda xatolik yuz berdi',
            ], 500);
        }
    }
}
