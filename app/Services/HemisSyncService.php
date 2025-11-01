<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HEMIS Integration Service
 *
 * This service handles synchronization with the HEMIS (Higher Education Management Information System)
 */
class HemisSyncService
{
    protected string $hemisUrl;
    protected string $hemisToken;
    protected int $timeout = 30;

    public function __construct()
    {
        $this->hemisUrl = config('services.hemis.url', 'https://hemis.uz/api');
        $this->hemisToken = config('services.hemis.token', '');
    }

    /**
     * Sync students from HEMIS
     *
     * @param array $filters
     * @return array
     */
    public function syncStudents(array $filters = []): array
    {
        try {
            $response = $this->makeRequest('GET', '/students', $filters);

            if (!$response['success']) {
                return [
                    'success' => false,
                    'message' => 'HEMIS dan ma\'lumot olishda xatolik',
                    'error' => $response['message'] ?? 'Unknown error',
                ];
            }

            $students = $response['data'] ?? [];
            $synced = 0;
            $errors = [];

            foreach ($students as $hemisStudent) {
                try {
                    $this->syncSingleStudent($hemisStudent);
                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'student_id' => $hemisStudent['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'success' => true,
                'synced' => $synced,
                'total' => count($students),
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            Log::error('HEMIS Sync Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Sinkronizatsiya xatosi: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync single student
     *
     * @param array $hemisData
     * @return \App\Models\EStudent
     */
    protected function syncSingleStudent(array $hemisData)
    {
        $student = \App\Models\EStudent::updateOrCreate(
            ['student_id_number' => $hemisData['student_id_number']],
            [
                'first_name' => $hemisData['first_name'],
                'second_name' => $hemisData['second_name'],
                'third_name' => $hemisData['third_name'] ?? null,
                'birth_date' => $hemisData['birth_date'],
                '_gender' => $hemisData['gender'],
                '_country' => $hemisData['country'] ?? '860', // Uzbekistan default
                'passport_number' => $hemisData['passport_number'] ?? null,
                'passport_pin' => $hemisData['passport_pin'] ?? null,
                'phone_number' => $hemisData['phone'] ?? null,
                'email' => $hemisData['email'] ?? null,
                'active' => $hemisData['active'] ?? true,
            ]
        );

        // Sync meta if provided
        if (isset($hemisData['meta'])) {
            $this->syncStudentMeta($student, $hemisData['meta']);
        }

        return $student;
    }

    /**
     * Sync student meta information
     *
     * @param \App\Models\EStudent $student
     * @param array $metaData
     * @return void
     */
    protected function syncStudentMeta($student, array $metaData): void
    {
        \App\Models\EStudentMeta::updateOrCreate(
            [
                '_student' => $student->id,
                'active' => true,
            ],
            [
                '_student_status' => $metaData['student_status'] ?? '11',
                '_education_type' => $metaData['education_type'] ?? null,
                '_education_form' => $metaData['education_form'] ?? null,
                '_level' => $metaData['level'] ?? null,
                '_group' => $metaData['group_id'] ?? null,
                '_specialty' => $metaData['specialty_id'] ?? null,
                '_department' => $metaData['department_id'] ?? null,
                '_curriculum' => $metaData['curriculum_id'] ?? null,
                '_payment_form' => $metaData['payment_form'] ?? null,
            ]
        );
    }

    /**
     * Push student data to HEMIS
     *
     * @param int $studentId
     * @return array
     */
    public function pushStudent(int $studentId): array
    {
        $student = \App\Models\EStudent::with('meta')->findOrFail($studentId);

        $data = [
            'student_id_number' => $student->student_id_number,
            'first_name' => $student->first_name,
            'second_name' => $student->second_name,
            'third_name' => $student->third_name,
            'birth_date' => $student->birth_date->format('Y-m-d'),
            'gender' => $student->_gender,
            'country' => $student->_country,
            'phone' => $student->phone_number,
            'email' => $student->email,
        ];

        if ($student->meta) {
            $data['meta'] = [
                'student_status' => $student->meta->_student_status,
                'education_type' => $student->meta->_education_type,
                'education_form' => $student->meta->_education_form,
                'level' => $student->meta->_level,
                'group_id' => $student->meta->_group,
                'specialty_id' => $student->meta->_specialty,
            ];
        }

        return $this->makeRequest('POST', '/students', $data);
    }

    /**
     * Make HTTP request to HEMIS API
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        try {
            $url = rtrim($this->hemisUrl, '/') . '/' . ltrim($endpoint, '/');

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->hemisToken,
                    'Accept' => 'application/json',
                ])
                ->$method($url, $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => $response->body(),
                'status' => $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check HEMIS API connection
     *
     * @return array
     */
    public function checkConnection(): array
    {
        try {
            $response = $this->makeRequest('GET', '/ping');

            return [
                'success' => $response['success'],
                'message' => $response['success'] ? 'HEMIS API ulanish muvaffaqiyatli' : 'Ulanish xatosi',
                'timestamp' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'HEMIS API ga ulanib bo\'lmadi: ' . $e->getMessage(),
            ];
        }
    }
}
