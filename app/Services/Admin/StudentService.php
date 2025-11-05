<?php

namespace App\Services\Admin;

use App\Models\EStudent;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Admin Student Service
 *
 * BUSINESS LOGIC LAYER
 * ======================
 *
 * Modular Monolith Architecture - Admin Module
 * Contains all business logic for student management (Admin CRUD operations)
 *
 * Controller → Service → Repository → Model
 *
 * @package App\Services\Admin
 */
class StudentService
{
    private FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Get paginated list of students with filters
     *
     * @param array $queryParams
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getStudentsList(array $queryParams): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = $queryParams['per_page'] ?? 20;

        return QueryBuilder::for(EStudent::class)
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
            ->paginate($perPage);
    }

    /**
     * Get single student with relationships
     *
     * @param int $studentId
     * @return EStudent
     */
    public function getStudent(int $studentId): EStudent
    {
        return QueryBuilder::for(EStudent::class)
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
            ->findOrFail($studentId);
    }

    /**
     * Create new student
     *
     * @param array $data
     * @return EStudent
     */
    public function createStudent(array $data): EStudent
    {
        // Hash password (Yii2 compatible bcrypt)
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $student = EStudent::create($data);
        $student->load('meta.specialty', 'meta.group');

        logger()->info('Student created by admin', [
            'student_id' => $student->id,
            'student_number' => $student->student_id_number,
        ]);

        return $student;
    }

    /**
     * Update student
     *
     * @param int $studentId
     * @param array $data
     * @return EStudent
     */
    public function updateStudent(int $studentId, array $data): EStudent
    {
        $student = EStudent::findOrFail($studentId);

        // Hash password if provided (Yii2 compatible bcrypt)
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $student->update($data);
        $student->load('meta.specialty', 'meta.group');

        logger()->info('Student updated by admin', [
            'student_id' => $student->id,
            'updated_fields' => array_keys($data),
        ]);

        return $student;
    }

    /**
     * Soft delete student (deactivate)
     *
     * @param int $studentId
     * @return void
     */
    public function deleteStudent(int $studentId): void
    {
        $student = EStudent::findOrFail($studentId);
        $student->update(['active' => false]);

        logger()->info('Student deactivated by admin', [
            'student_id' => $student->id,
            'student_number' => $student->student_id_number,
        ]);
    }

    /**
     * Upload student image
     *
     * @param int $studentId
     * @param UploadedFile $image
     * @return array
     * @throws \Exception
     */
    public function uploadStudentImage(int $studentId, UploadedFile $image): array
    {
        $student = EStudent::findOrFail($studentId);

        // Delete old image if exists
        if ($student->image) {
            $this->fileUploadService->deleteImage($student->image);
        }

        // Upload new image
        $path = $this->fileUploadService->uploadImage($image, 'student', $student->id);

        // Update student record
        $student->update(['image' => $path]);

        logger()->info('Student image uploaded', [
            'student_id' => $student->id,
            'image_path' => $path,
        ]);

        return [
            'image' => $path,
            'image_url' => $this->fileUploadService->getImageUrl($path),
        ];
    }

    /**
     * Get student statistics
     *
     * @return array
     */
    public function getStudentStatistics(): array
    {
        return [
            'total_students' => EStudent::where('active', true)->count(),
            'inactive_students' => EStudent::where('active', false)->count(),
            'by_gender' => EStudent::where('active', true)
                ->selectRaw('_gender, COUNT(*) as count')
                ->groupBy('_gender')
                ->pluck('count', '_gender')
                ->toArray(),
        ];
    }
}
