<?php

namespace App\Services\Student;

use App\Models\System\EStudent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Student Profile Service
 *
 * BUSINESS LOGIC LAYER
 * ======================
 *
 * Modular Monolith Architecture - Student Module
 * Contains all business logic for student profile management
 *
 * Controller → Service → Repository → Model
 *
 * @package App\Services\Student
 */
class ProfileService
{
    /**
     * Get student profile data
     *
     * @param EStudent $student
     * @return array
     */
    public function getProfile(EStudent $student): array
    {
        return [
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
        ];
    }

    /**
     * Update student profile
     *
     * @param EStudent $student
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function updateProfile(EStudent $student, array $data): array
    {
        DB::beginTransaction();

        try {
            // Update only allowed fields
            $student->update([
                'phone' => $data['phone'] ?? $student->phone,
                'email' => $data['email'] ?? $student->email,
                'current_address' => $data['current_address'] ?? $student->current_address,
                'telegram_username' => $data['telegram_username'] ?? $student->telegram_username,
            ]);

            DB::commit();

            return [
                'phone' => $student->phone,
                'email' => $student->email,
                'current_address' => $student->current_address,
                'telegram_username' => $student->telegram_username,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Profilni yangilashda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Update student password
     *
     * @param EStudent $student
     * @param string $newPassword
     * @return void
     * @throws \Exception
     */
    public function updatePassword(EStudent $student, string $newPassword): void
    {
        DB::beginTransaction();

        try {
            $student->password = Hash::make($newPassword);
            $student->save();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Parolni o\'zgartirishda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Upload student photo
     *
     * @param EStudent $student
     * @param UploadedFile $photo
     * @return array
     * @throws \Exception
     */
    public function uploadPhoto(EStudent $student, UploadedFile $photo): array
    {
        DB::beginTransaction();

        try {
            // Delete old photo if exists
            if ($student->image && Storage::disk('public')->exists($student->image)) {
                Storage::disk('public')->delete($student->image);
            }

            // Store new photo
            $path = $photo->store('students/photos', 'public');

            // Update student image path
            $student->image = $path;
            $student->save();

            DB::commit();

            return [
                'image' => $path,
                'url' => Storage::disk('public')->url($path),
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Rasmni yuklashda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Delete student photo
     *
     * @param EStudent $student
     * @return void
     * @throws \Exception
     */
    public function deletePhoto(EStudent $student): void
    {
        if (!$student->image) {
            throw new \Exception('Rasm mavjud emas');
        }

        DB::beginTransaction();

        try {
            // Delete photo from storage
            if (Storage::disk('public')->exists($student->image)) {
                Storage::disk('public')->delete($student->image);
            }

            // Clear image field
            $student->image = null;
            $student->save();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Rasmni o\'chirishda xatolik: ' . $e->getMessage());
        }
    }
}
