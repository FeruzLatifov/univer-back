<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreStudentRequest;
use App\Http\Requests\Api\V1\UpdateStudentRequest;
use App\Http\Resources\Api\V1\StudentResource;
use App\Models\EStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Student Management Controller (Admin Panel)
 *
 * API Version: 1.0
 * Purpose: Admin xodimlar tomonidan talabalarni boshqarish (CRUD)
 */
class StudentController extends Controller
{
    /**
     * Get students list with filters
     *
     * @route GET /api/v1/admin/students
     */
    public function index(Request $request)
    {
        $students = QueryBuilder::for(EStudent::class)
            ->allowedFilters([
                AllowedFilter::exact('id'),
                AllowedFilter::partial('first_name'),
                AllowedFilter::partial('second_name'),
                AllowedFilter::partial('student_id_number'),
                AllowedFilter::exact('_gender'),
                AllowedFilter::exact('active'),
            ])
            ->allowedIncludes([
                'meta',
                'meta.group',
                'meta.specialty',
                'meta.department',
                'country',
                'gender',
            ])
            ->allowedSorts([
                'id',
                'second_name',
                'first_name',
                'student_id_number',
                'created_at',
                'updated_at',
            ])
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => StudentResource::collection($students->items()),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
            ],
        ]);
    }

    /**
     * Get single student
     *
     * @route GET /api/v1/admin/students/{id}
     */
    public function show($id)
    {
        $student = QueryBuilder::for(EStudent::class)
            ->allowedIncludes([
                'meta',
                'meta.group',
                'meta.specialty',
                'meta.department',
                'meta.educationType',
                'meta.educationForm',
                'meta.paymentForm',
                'country',
                'gender',
                'allMeta',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
        ]);
    }

    /**
     * Create new student
     *
     * @route POST /api/v1/admin/students
     */
    public function store(StoreStudentRequest $request)
    {
        $validated = $request->validated();

        // Hash password (Yii2 compatible bcrypt)
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $student = EStudent::create($validated);
        $student->load('meta.specialty', 'meta.group');

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
            'message' => 'Student muvaffaqiyatli yaratildi',
        ], 201);
    }

    /**
     * Update student
     *
     * @route PUT /api/v1/admin/students/{id}
     */
    public function update(UpdateStudentRequest $request, $id)
    {
        $student = EStudent::findOrFail($id);
        $validated = $request->validated();

        // Hash password if provided (Yii2 compatible bcrypt)
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $student->update($validated);
        $student->load('meta.specialty', 'meta.group');

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
            'message' => 'Student muvaffaqiyatli yangilandi',
        ]);
    }

    /**
     * Delete student (soft delete)
     *
     * @route DELETE /api/v1/admin/students/{id}
     */
    public function destroy($id)
    {
        $student = EStudent::findOrFail($id);
        $student->update(['active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Student o\'chirildi (deactivated)',
        ]);
    }

    /**
     * Upload student image
     *
     * @route POST /api/v1/admin/students/{id}/image
     */
    public function uploadImage(Request $request, $id)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
        ]);

        $student = EStudent::findOrFail($id);

        $uploadService = app(\App\Services\FileUploadService::class);

        try {
            // Delete old image if exists
            if ($student->image) {
                $uploadService->deleteImage($student->image);
            }

            // Upload new image
            $path = $uploadService->uploadImage($request->file('image'), 'student', $student->id);

            // Update student record
            $student->update(['image' => $path]);

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

    /**
     * Get authenticated student's profile (Self-Service)
     *
     * @route GET /api/v1/student/profile
     */
    public function myProfile()
    {
        $student = auth('student-api')->user();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student topilmadi',
            ], 404);
        }

        $student->load('meta.specialty', 'meta.group', 'meta.department');

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
        ]);
    }

    /**
     * Update authenticated student's profile (Self-Service)
     *
     * @route PUT /api/v1/student/profile
     */
    public function updateProfile(Request $request)
    {
        $student = auth('student-api')->user();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student topilmadi',
            ], 404);
        }

        // Students can only update limited fields
        $validated = $request->validate([
            'phone' => 'sometimes|string|max:50',
            'phone_secondary' => 'nullable|string|max:50',
            'email' => 'sometimes|email|max:100',
            'telegram_username' => 'nullable|string|max:50',
        ]);

        $student->update($validated);
        $student->load('meta.specialty', 'meta.group');

        return response()->json([
            'success' => true,
            'data' => new StudentResource($student),
            'message' => 'Profil muvaffaqiyatli yangilandi',
        ]);
    }

    /**
     * Upload student avatar (Self-Service)
     *
     * @route POST /api/v1/student/profile/avatar
     */
    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
        ]);

        $student = auth('student-api')->user();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student topilmadi',
            ], 404);
        }

        $uploadService = app(\App\Services\FileUploadService::class);

        try {
            // Delete old image if exists
            if ($student->image) {
                $uploadService->deleteImage($student->image);
            }

            // Upload new image
            $path = $uploadService->uploadImage($request->file('image'), 'student', $student->id);

            // Update student record
            $student->update(['image' => $path]);

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
