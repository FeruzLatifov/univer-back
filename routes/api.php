<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health check (public, higher rate limit)
Route::middleware('throttle:public')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected'
        ]);
    });

    // System configuration for login page
    Route::get('/system/login-config', [\App\Http\Controllers\Api\V1\SystemController::class, 'getLoginConfig']);

    // Translations
    Route::get('/system/translations/{language?}', [\App\Http\Controllers\Api\V1\SystemController::class, 'getTranslations']);
    Route::get('/system/languages', [\App\Http\Controllers\Api\V1\SystemController::class, 'getLanguages']);

    // Language management (public - no auth required)
    Route::prefix('v1')->group(function () {
        Route::get('/languages', [\App\Http\Controllers\Api\V1\LanguageController::class, 'index']);
        Route::get('/languages/current', [\App\Http\Controllers\Api\V1\LanguageController::class, 'current']);
        Route::get('/languages/translations', [\App\Http\Controllers\Api\V1\LanguageController::class, 'getTranslations']);
        Route::get('/languages/{code}', [\App\Http\Controllers\Api\V1\LanguageController::class, 'show']);
        Route::post('/languages/set', [\App\Http\Controllers\Api\V1\LanguageController::class, 'setLanguage']);
    });
});

/*
|--------------------------------------------------------------------------
| Authentication Endpoints (Alohida)
|--------------------------------------------------------------------------
|
| Student va Admin uchun ALOHIDA endpoint'lar
| - /api/student/auth/* - Faqat talabalar
| - /api/admin/auth/* - Faqat admin/xodimlar
|
*/

// Student Auth (5 req/min)
Route::middleware('throttle:auth')->prefix('student/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'login']);

    Route::middleware('auth:student-api')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'logout']);
        Route::post('/refresh', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'refresh']);
        Route::get('/me', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'me']);
    });
});

// Student Password Reset (3 req/5min)
Route::middleware('throttle:password')->prefix('student/auth')->group(function () {
    Route::post('/forgot-password', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'resetPassword']);
});

// Student Portal Routes
Route::middleware(['auth:student-api', 'throttle:api'])->prefix('student')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Api\V1\Student\DashboardController::class, 'index']);

    // Profile
    Route::get('/profile', [\App\Http\Controllers\Api\V1\Student\ProfileController::class, 'show']);
    Route::put('/profile', [\App\Http\Controllers\Api\V1\Student\ProfileController::class, 'update']);
    Route::put('/password', [\App\Http\Controllers\Api\V1\Student\ProfileController::class, 'updatePassword']);
    Route::post('/photo', [\App\Http\Controllers\Api\V1\Student\ProfileController::class, 'uploadPhoto']);
    Route::delete('/photo', [\App\Http\Controllers\Api\V1\Student\ProfileController::class, 'deletePhoto']);

    Route::get('/subjects', [\App\Http\Controllers\Api\V1\Student\SubjectController::class, 'index']);
    Route::get('/subjects/{id}', [\App\Http\Controllers\Api\V1\Student\SubjectController::class, 'show']);

    Route::get('/assignments', [\App\Http\Controllers\Api\V1\Student\AssignmentController::class, 'index']);
    Route::post('/assignments/{id}/submit', [\App\Http\Controllers\Api\V1\Student\AssignmentController::class, 'submit']);

    Route::get('/tests', [\App\Http\Controllers\Api\V1\Student\TestController::class, 'index']);
    Route::get('/tests/results', [\App\Http\Controllers\Api\V1\Student\TestController::class, 'results']);

    Route::get('/grades', [\App\Http\Controllers\Api\V1\Student\GradeController::class, 'index']);

    Route::get('/attendance', [\App\Http\Controllers\Api\V1\Student\AttendanceController::class, 'index']);

    Route::get('/schedule', [\App\Http\Controllers\Api\V1\Student\ScheduleController::class, 'index']);
});

// Admin/Employee Auth (5 req/min)
Route::middleware('throttle:auth')->prefix('admin/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\V1\Staff\AuthController::class, 'login']);

    Route::middleware('auth:admin-api')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\V1\Staff\AuthController::class, 'logout']);
        Route::post('/refresh', [\App\Http\Controllers\Api\V1\Staff\AuthController::class, 'refresh']);
        Route::get('/me', [\App\Http\Controllers\Api\V1\Staff\AuthController::class, 'me']);
    });
});

// Admin/Staff Password Reset (3 req/5min)
Route::middleware('throttle:password')->prefix('admin/auth')->group(function () {
    Route::post('/forgot-password', [\App\Http\Controllers\Api\V1\Staff\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\V1\Staff\AuthController::class, 'resetPassword']);
});

/*
|--------------------------------------------------------------------------
| Protected Admin Routes
|--------------------------------------------------------------------------
|
| Faqat admin-api guard bilan himoyalangan
| Admin va xodimlar uchun CRUD operatsiyalar
|
*/
Route::middleware(['auth:admin-api', 'throttle:students'])->group(function () {
    // Student routes
    Route::prefix('students')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\StudentController::class, 'index']);
        Route::get('/{student}', [\App\Http\Controllers\Api\StudentController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\StudentController::class, 'store']);
        Route::put('/{student}', [\App\Http\Controllers\Api\StudentController::class, 'update']);
        Route::delete('/{student}', [\App\Http\Controllers\Api\StudentController::class, 'destroy']);
        Route::post('/{student}/upload-image', [\App\Http\Controllers\Api\StudentController::class, 'uploadImage']);
    });

    // Group routes
    Route::prefix('groups')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\GroupController::class, 'index']);
        Route::get('/{group}', [\App\Http\Controllers\Api\GroupController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\GroupController::class, 'store']);
        Route::put('/{group}', [\App\Http\Controllers\Api\GroupController::class, 'update']);
        Route::delete('/{group}', [\App\Http\Controllers\Api\GroupController::class, 'destroy']);
    });

    // Specialty routes
    Route::prefix('specialties')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SpecialtyController::class, 'index']);
        Route::get('/{specialty}', [\App\Http\Controllers\Api\SpecialtyController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\SpecialtyController::class, 'store']);
        Route::put('/{specialty}', [\App\Http\Controllers\Api\SpecialtyController::class, 'update']);
        Route::delete('/{specialty}', [\App\Http\Controllers\Api\SpecialtyController::class, 'destroy']);
    });

    // Department routes
    Route::prefix('departments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\DepartmentController::class, 'index']);
        Route::get('/{department}', [\App\Http\Controllers\Api\DepartmentController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\DepartmentController::class, 'store']);
        Route::put('/{department}', [\App\Http\Controllers\Api\DepartmentController::class, 'update']);
        Route::delete('/{department}', [\App\Http\Controllers\Api\DepartmentController::class, 'destroy']);
    });

    // Employee routes
    Route::prefix('employees')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\EmployeeController::class, 'index']);
        Route::get('/{employee}', [\App\Http\Controllers\Api\EmployeeController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\EmployeeController::class, 'store']);
        Route::put('/{employee}', [\App\Http\Controllers\Api\EmployeeController::class, 'update']);
        Route::delete('/{employee}', [\App\Http\Controllers\Api\EmployeeController::class, 'destroy']);
    });

    // HEMIS Integration routes (admin only)
    Route::prefix('hemis')->middleware('throttle:10,1')->group(function () {
        Route::get('/check', [\App\Http\Controllers\Api\HemisController::class, 'checkConnection']);
        Route::post('/sync/students', [\App\Http\Controllers\Api\HemisController::class, 'syncStudents']);
        Route::post('/push/student/{studentId}', [\App\Http\Controllers\Api\HemisController::class, 'pushStudent']);
        Route::get('/sync/status', [\App\Http\Controllers\Api\HemisController::class, 'getSyncStatus']);
    });
});

/*
|--------------------------------------------------------------------------
| Messaging & Notifications Routes
|--------------------------------------------------------------------------
|
| Available for both students and admins/teachers
| Works with multiple guards: student-api, admin-api
|
*/

// Messaging routes (accessible by both students and admins)
Route::middleware(['auth:student-api,admin-api', 'throttle:api'])->group(function () {

    // Messages
    Route::prefix('messages')->group(function () {
        Route::get('/inbox', [\App\Http\Controllers\Api\MessagingController::class, 'inbox']);
        Route::get('/sent', [\App\Http\Controllers\Api\MessagingController::class, 'sent']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\MessagingController::class, 'unreadCount']);
        Route::get('/{id}', [\App\Http\Controllers\Api\MessagingController::class, 'show']);
        Route::post('/send', [\App\Http\Controllers\Api\MessagingController::class, 'send']);
        Route::put('/{id}/read', [\App\Http\Controllers\Api\MessagingController::class, 'markAsRead']);
        Route::put('/{id}/unread', [\App\Http\Controllers\Api\MessagingController::class, 'markAsUnread']);
        Route::put('/{id}/archive', [\App\Http\Controllers\Api\MessagingController::class, 'archive']);
        Route::put('/{id}/star', [\App\Http\Controllers\Api\MessagingController::class, 'star']);
        Route::put('/{id}/unstar', [\App\Http\Controllers\Api\MessagingController::class, 'unstar']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\MessagingController::class, 'destroy']);
    });

    // Message attachments
    Route::get('/attachments/{id}/download', [\App\Http\Controllers\Api\MessagingController::class, 'downloadAttachment'])
        ->name('api.messages.attachments.download');

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::get('/unread', [\App\Http\Controllers\Api\NotificationController::class, 'unread']);
        Route::get('/recent', [\App\Http\Controllers\Api\NotificationController::class, 'recent']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
        Route::get('/stats', [\App\Http\Controllers\Api\NotificationController::class, 'stats']);
        Route::get('/{id}', [\App\Http\Controllers\Api\NotificationController::class, 'show']);
        Route::put('/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);
        Route::put('/{id}/unread', [\App\Http\Controllers\Api\NotificationController::class, 'markAsUnread']);
        Route::put('/mark-all-read', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);

        // Notification settings
        Route::get('/settings/all', [\App\Http\Controllers\Api\NotificationController::class, 'getSettings']);
        Route::put('/settings/bulk', [\App\Http\Controllers\Api\NotificationController::class, 'updateSettings']);
        Route::put('/settings/{type}', [\App\Http\Controllers\Api\NotificationController::class, 'updateSetting']);
        Route::put('/settings/{type}/enable-all', [\App\Http\Controllers\Api\NotificationController::class, 'enableAll']);
        Route::put('/settings/{type}/disable-all', [\App\Http\Controllers\Api\NotificationController::class, 'disableAll']);
        Route::post('/settings/reset', [\App\Http\Controllers\Api\NotificationController::class, 'resetSettings']);
    });
});

/*
|--------------------------------------------------------------------------
| Forum/Discussion Routes
|--------------------------------------------------------------------------
|
| Forum categories, topics, posts, likes, subscriptions
|
*/

Route::middleware(['auth:student-api,admin-api', 'throttle:api'])->group(function () {

    // Categories
    Route::prefix('forum')->group(function () {
        Route::get('/categories', [\App\Http\Controllers\Api\ForumController::class, 'getCategories']);
        Route::get('/categories/{id}', [\App\Http\Controllers\Api\ForumController::class, 'getCategory']);

        // Topics
        Route::get('/categories/{id}/topics', [\App\Http\Controllers\Api\ForumController::class, 'getTopics']);
        Route::get('/topics/{id}', [\App\Http\Controllers\Api\ForumController::class, 'getTopic']);
        Route::post('/topics', [\App\Http\Controllers\Api\ForumController::class, 'createTopic']);
        Route::put('/topics/{id}', [\App\Http\Controllers\Api\ForumController::class, 'updateTopic']);
        Route::delete('/topics/{id}', [\App\Http\Controllers\Api\ForumController::class, 'deleteTopic']);

        // Posts
        Route::post('/topics/{id}/posts', [\App\Http\Controllers\Api\ForumController::class, 'createPost']);
        Route::put('/posts/{id}', [\App\Http\Controllers\Api\ForumController::class, 'updatePost']);
        Route::delete('/posts/{id}', [\App\Http\Controllers\Api\ForumController::class, 'deletePost']);

        // Likes
        Route::post('/topics/{id}/like', [\App\Http\Controllers\Api\ForumController::class, 'toggleTopicLike']);
        Route::post('/posts/{id}/like', [\App\Http\Controllers\Api\ForumController::class, 'togglePostLike']);

        // Subscriptions
        Route::post('/topics/{id}/subscribe', [\App\Http\Controllers\Api\ForumController::class, 'subscribeToTopic']);
        Route::delete('/topics/{id}/subscribe', [\App\Http\Controllers\Api\ForumController::class, 'unsubscribeFromTopic']);
    });
});

/*
|--------------------------------------------------------------------------
| Export Routes (PDF & Excel)
|--------------------------------------------------------------------------
|
| Export students, reports, and statistics
|
*/

Route::middleware(['auth:admin-api', 'throttle:api'])->group(function () {

    Route::prefix('export')->group(function () {
        // Students
        Route::get('/students', [\App\Http\Controllers\Api\ExportController::class, 'exportStudentsList']);
        Route::get('/students/{id}/attendance', [\App\Http\Controllers\Api\ExportController::class, 'exportStudentAttendance']);
        Route::get('/students/{id}/grades', [\App\Http\Controllers\Api\ExportController::class, 'exportStudentGrades']);
        Route::get('/groups/{id}/students', [\App\Http\Controllers\Api\ExportController::class, 'exportGroupStudents']);

        // Reports
        Route::get('/reports/attendance-summary', [\App\Http\Controllers\Api\ExportController::class, 'exportAttendanceSummary']);
        Route::get('/reports/grades-summary', [\App\Http\Controllers\Api\ExportController::class, 'exportGradesSummary']);
        Route::get('/reports/teacher-workload/{id}', [\App\Http\Controllers\Api\ExportController::class, 'exportTeacherWorkload']);
        Route::get('/reports/students-performance', [\App\Http\Controllers\Api\ExportController::class, 'exportStudentsPerformance']);
        Route::get('/reports/monthly-stats', [\App\Http\Controllers\Api\ExportController::class, 'exportMonthlyStats']);
    });
});

/*
|--------------------------------------------------------------------------
| BACKWARD COMPATIBILITY LAYER - Yii2 API URLs
|--------------------------------------------------------------------------
|
| Eski univer-yii2 API URL'larini Laravel'ga mapping qilish
| Tashqi integratsiyalar uchun backward compatibility
|
| Maqsad: Zero downtime migration
| Eski URL'lar: /v1/education/*, /v1/student/*
| Yangi URL'lar: /api/student/*
|
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | V1 Student Authentication (Yii2 format)
    |--------------------------------------------------------------------------
    */
    Route::middleware('throttle:auth')->prefix('auth')->group(function () {
        Route::post('/student-login', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'login']);

        Route::middleware('auth:student-api')->group(function () {
            Route::post('/student-logout', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'logout']);
            Route::get('/student-profile', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'me']);
            Route::post('/student-refresh', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'refresh']);
        });
    });

    /*
    |--------------------------------------------------------------------------
    | V1 Education Endpoints (Yii2 format)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:student-api', 'throttle:api'])->prefix('education')->group(function () {

        // Semesters & Grade Types
        Route::get('/semesters', [\App\Http\Controllers\Api\V1\Compatibility\EducationController::class, 'semesters']);
        Route::get('/grade-type-list', [\App\Http\Controllers\Api\V1\Compatibility\EducationController::class, 'gradeTypes']);

        // Subjects
        Route::get('/subjects', [\App\Http\Controllers\Api\V1\Student\SubjectController::class, 'index']);
        Route::get('/subject-list', [\App\Http\Controllers\Api\V1\Student\SubjectController::class, 'index']);
        Route::get('/subject', [\App\Http\Controllers\Api\V1\Compatibility\EducationController::class, 'subject']);

        // Resources
        Route::get('/resources', [\App\Http\Controllers\Api\V1\Compatibility\EducationController::class, 'resources']);

        // Schedule
        Route::get('/schedule', [\App\Http\Controllers\Api\V1\Student\ScheduleController::class, 'index']);

        // Attendance
        Route::get('/attendance', [\App\Http\Controllers\Api\V1\Student\AttendanceController::class, 'index']);

        // Grades/Performance
        Route::get('/performance', [\App\Http\Controllers\Api\V1\Student\GradeController::class, 'index']);
        Route::get('/grades', [\App\Http\Controllers\Api\V1\Student\GradeController::class, 'index']);

        // Tasks (Assignments)
        Route::get('/tasks', [\App\Http\Controllers\Api\V1\Student\AssignmentController::class, 'index']);
        Route::get('/task', [\App\Http\Controllers\Api\V1\Compatibility\EducationController::class, 'task']);
        Route::post('/task-submit', [\App\Http\Controllers\Api\V1\Compatibility\EducationController::class, 'taskSubmit']);

        // Exams & GPA
        Route::get('/exams', [\App\Http\Controllers\Api\V1\Compatibility\EducationController::class, 'exams']);
        Route::get('/gpa', [\App\Http\Controllers\Api\V1\Compatibility\EducationController::class, 'gpa']);
    });

    /*
    |--------------------------------------------------------------------------
    | V1 Student Document Endpoints (Yii2 format)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:student-api', 'throttle:api'])->prefix('student')->group(function () {

        // Decree (Buyruqlar)
        Route::get('/decree', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'decree']);
        Route::get('/decree-download/{id}', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'downloadDecree']);

        // Certificate (Sertifikatlar)
        Route::get('/certificate', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'certificate']);

        // Reference (Ma'lumotnomalar)
        Route::get('/reference', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'reference']);
        Route::get('/reference-generate', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'generateReference']);
        Route::get('/reference-download/{id}', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'downloadReference']);

        // Documents (Diplom, Transkript, etc)
        Route::get('/document', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'document']);
        Route::get('/document-all', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'documentAll']);
        Route::get('/document-download', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'downloadDocument']);

        // Contract (Kontrakt)
        Route::get('/contract-list', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'contractList']);
        Route::get('/contract', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'contract']);
        Route::get('/contract-download/{id}', [\App\Http\Controllers\Api\V1\Student\DocumentController::class, 'downloadContract']);

        // Plagiarism (kept for compatibility, returns empty for now)
        Route::get('/plagiarism', function () {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Antiplagiat tizimi ishlamayapti',
            ]);
        });
    });
});
