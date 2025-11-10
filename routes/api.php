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
        Route::post('/languages/set', [\App\Http\Controllers\Api\V1\LanguageController::class, 'setLanguage']);
        // Constrain {code} to only match valid ISO language codes (uz, oz, ru, en, etc)
        Route::get('/languages/{code}', [\App\Http\Controllers\Api\V1\LanguageController::class, 'show'])
            ->where('code', '^(uz|oz|ru|en|tj|kz|tm|ko|de|fr)$');
    });
});

/*
|--------------------------------------------------------------------------
| Authentication Endpoints - Best Practice Structure
|--------------------------------------------------------------------------
|
| URL Structure (Role-based separation):
| - /api/v1/student/*   → Student endpoints (guard: student-api)
| - /api/v1/employee/*  → Employee/Admin endpoints (guard: admin-api)
|
| Authentication:
| - POST /api/v1/student/auth/login   → Student login
| - POST /api/v1/employee/auth/login  → Employee/Admin login (Teacher, Admin, Employee, Rector, etc.)
|
| This structure provides:
| 1. Clear separation between student and employee APIs
| 2. Easy to apply different middlewares/permissions
| 3. Scalable for future endpoints
| 4. Matches database structure (e_student, e_employee, e_admin)
|
*/

// Student Auth (5 req/min)
Route::middleware('throttle:auth')->prefix('student/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'login']);
    Route::post('/refresh', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'refresh']);

    Route::middleware('auth:student-api')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\V1\Student\AuthController::class, 'logout']);
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

/*
|--------------------------------------------------------------------------
| Employee/Admin Authentication
|--------------------------------------------------------------------------
| Single endpoint for all staff authentication
| - Teachers, Admins, Employees, Rectors, etc.
| - Guard: admin-api
*/
Route::middleware('throttle:auth')->prefix('v1/employee/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'login']);
    Route::post('/refresh', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'refresh']);
    Route::post('/forgot-password', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'resetPassword']);

    Route::middleware('auth:employee-api')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'me']);
        Route::post('/role/switch', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'switchRole']);

        // ZERO TRUST: Permission endpoints (F12-proof)
        Route::get('/permissions', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'getPermissions']);
        Route::post('/permissions/check', [\App\Http\Controllers\Api\V1\Employee\AuthController::class, 'checkPermissions']);
    });
});

/*
|--------------------------------------------------------------------------
| OAuth2 Server Endpoints
|--------------------------------------------------------------------------
| OAuth2 Authorization Code Flow
| Compatible with Yii2 OAuth2 implementation
| Use for third-party application integration
*/
Route::prefix('v1/oauth')->group(function () {
    // Authorization endpoint (Step 1)
    Route::get('/authorize', [\App\Http\Controllers\Api\V1\OAuthController::class, 'authorize']);

    // Grant authorization (Step 2) - Requires authentication
    Route::middleware('auth:employee-api')->post('/authorize', [\App\Http\Controllers\Api\V1\OAuthController::class, 'grant']);

    // Token endpoint (Step 3) - Exchange code or refresh
    Route::post('/token', [\App\Http\Controllers\Api\V1\OAuthController::class, 'token']);

    // Revoke token
    Route::post('/revoke', [\App\Http\Controllers\Api\V1\OAuthController::class, 'revoke']);

    // User info endpoint (for resource servers)
    Route::get('/userinfo', [\App\Http\Controllers\Api\V1\OAuthController::class, 'userinfo']);
});

/*
|--------------------------------------------------------------------------
| Employee Dashboard & Resources
|--------------------------------------------------------------------------
| Endpoints for admin/teacher/employee dashboard and resources
| Guard: admin-api
*/
Route::middleware(['auth:employee-api', 'throttle:api'])->prefix('v1/employee')->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\V1\Employee\DashboardController::class, 'index']);

    // Document Signing
    Route::prefix('documents')->group(function () {
        // List documents to sign
        Route::get('/sign', [\App\Http\Controllers\Api\V1\Employee\DocumentController::class, 'index']);
        // Document details for viewing
        Route::get('/{hash}/view', [\App\Http\Controllers\Api\V1\Employee\DocumentController::class, 'view']);
        // Initiate sign action
        Route::post('/{hash}/sign', [\App\Http\Controllers\Api\V1\Employee\DocumentController::class, 'sign']);
        // Get sign status
        Route::get('/{hash}/status', [\App\Http\Controllers\Api\V1\Employee\DocumentController::class, 'status']);
    });
});

/*
|--------------------------------------------------------------------------
| Teacher Routes
|--------------------------------------------------------------------------
| Endpoints for teacher-specific functionality
| Guard: employee-api (teachers are employees with role=teacher)
*/
Route::middleware(['auth:employee-api', 'throttle:api'])->prefix('v1/teacher')->group(function () {
    // Dashboard
    Route::get('/dashboard', [\App\Http\Controllers\Api\V1\Teacher\DashboardController::class, 'index']);
    Route::get('/dashboard/activities', [\App\Http\Controllers\Api\V1\Teacher\DashboardController::class, 'activities']);
    Route::get('/dashboard/stats', [\App\Http\Controllers\Api\V1\Teacher\DashboardController::class, 'stats']);

    // Schedule & Timetable
    Route::prefix('schedule')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\ScheduleController::class, 'index']);
        Route::get('/today', [\App\Http\Controllers\Api\V1\Teacher\ScheduleController::class, 'today']);
        Route::get('/day/{day}', [\App\Http\Controllers\Api\V1\Teacher\ScheduleController::class, 'day']);
        Route::get('/workload', [\App\Http\Controllers\Api\V1\Teacher\ScheduleController::class, 'workload']);
    });

    // Groups & Students
    Route::get('/groups', [\App\Http\Controllers\Api\V1\Teacher\ScheduleController::class, 'groups']);
    Route::prefix('students')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\SubjectController::class, 'students']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V1\Teacher\SubjectController::class, 'student']);
    });

    // Subjects
    Route::prefix('subjects')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\SubjectController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V1\Teacher\SubjectController::class, 'show']);
    });

    // Attendance
    Route::prefix('attendance')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\AttendanceController::class, 'index']);
        Route::get('/{scheduleId}', [\App\Http\Controllers\Api\V1\Teacher\AttendanceController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\V1\Teacher\AttendanceController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\V1\Teacher\AttendanceController::class, 'update']);
    });

    // Grades & Performance
    Route::prefix('grades')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\GradeController::class, 'index']);
        Route::get('/{subjectId}', [\App\Http\Controllers\Api\V1\Teacher\GradeController::class, 'bySubject']);
        Route::post('/', [\App\Http\Controllers\Api\V1\Teacher\GradeController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\V1\Teacher\GradeController::class, 'update']);
    });

    // Assignments & Tasks
    Route::prefix('assignments')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\AssignmentController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V1\Teacher\AssignmentController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\V1\Teacher\AssignmentController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\V1\Teacher\AssignmentController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Teacher\AssignmentController::class, 'destroy']);
        Route::get('/{id}/submissions', [\App\Http\Controllers\Api\V1\Teacher\AssignmentController::class, 'submissions']);
        Route::post('/submissions/{id}/grade', [\App\Http\Controllers\Api\V1\Teacher\AssignmentController::class, 'gradeSubmission']);
    });

    // Exams
    Route::prefix('exams')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\ExamController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V1\Teacher\ExamController::class, 'show']);
        Route::get('/{id}/students', [\App\Http\Controllers\Api\V1\Teacher\ExamController::class, 'students']);
    });

    // Resources & Materials
    Route::prefix('resources')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\ResourceController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V1\Teacher\ResourceController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\V1\Teacher\ResourceController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\V1\Teacher\ResourceController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Teacher\ResourceController::class, 'destroy']);
    });

    // Topics (Mavzular)
    Route::prefix('topics')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\TopicController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V1\Teacher\TopicController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\V1\Teacher\TopicController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\V1\Teacher\TopicController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Teacher\TopicController::class, 'destroy']);
    });

    // Tests & Quizzes
    Route::prefix('tests')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\Teacher\TestController::class, 'index']);
        Route::get('/{id}', [\App\Http\Controllers\Api\V1\Teacher\TestController::class, 'show']);
        Route::post('/', [\App\Http\Controllers\Api\V1\Teacher\TestController::class, 'store']);
        Route::put('/{id}', [\App\Http\Controllers\Api\V1\Teacher\TestController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\V1\Teacher\TestController::class, 'destroy']);
        Route::get('/{id}/results', [\App\Http\Controllers\Api\V1\Teacher\TestController::class, 'results']);
    });
});


/*
|--------------------------------------------------------------------------
| Protected Admin Routes
|--------------------------------------------------------------------------
|
| SECURITY: Authentication + Permission middleware
| - auth:admin-api - Require logged in admin
| - permission:X - Require specific permission (Dual mode: Spatie + Yii2)
|
| Permission Format: resource.action
| - student.view, student.create, student.edit, student.delete
| - employee.view, employee.create, employee.edit, employee.delete
|
*/
Route::middleware(['auth:admin-api', 'throttle:students'])->group(function () {

    // ============================================
    // STUDENT ROUTES (CRUD with permissions)
    // ============================================
    Route::prefix('students')->group(function () {
        // View permissions
        Route::middleware('permission:student.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Admin\StudentController::class, 'index']);
            Route::get('/{student}', [\App\Http\Controllers\Api\V1\Admin\StudentController::class, 'show']);
        });

        // Create permission
        Route::middleware('permission:student.create')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\Admin\StudentController::class, 'store']);
            Route::post('/{student}/upload-image', [\App\Http\Controllers\Api\V1\Admin\StudentController::class, 'uploadImage']);
        });

        // Edit permission
        Route::middleware('permission:student.edit')->group(function () {
            Route::put('/{student}', [\App\Http\Controllers\Api\V1\Admin\StudentController::class, 'update']);
        });

        // Delete permission
        Route::middleware('permission:student.delete')->group(function () {
            Route::delete('/{student}', [\App\Http\Controllers\Api\V1\Admin\StudentController::class, 'destroy']);
        });
    });

    // ============================================
    // GROUP ROUTES (CRUD with permissions)
    // ============================================
    Route::prefix('groups')->group(function () {
        Route::middleware('permission:group.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Admin\GroupController::class, 'index']);
            Route::get('/{group}', [\App\Http\Controllers\Api\V1\Admin\GroupController::class, 'show']);
        });

        Route::middleware('permission:group.create')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\Admin\GroupController::class, 'store']);
        });

        Route::middleware('permission:group.edit')->group(function () {
            Route::put('/{group}', [\App\Http\Controllers\Api\V1\Admin\GroupController::class, 'update']);
        });

        Route::middleware('permission:group.delete')->group(function () {
            Route::delete('/{group}', [\App\Http\Controllers\Api\V1\Admin\GroupController::class, 'destroy']);
        });
    });

    // ============================================
    // SPECIALTY ROUTES (CRUD with permissions)
    // ============================================
    Route::prefix('specialties')->group(function () {
        Route::middleware('permission:specialty.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Admin\SpecialtyController::class, 'index']);
            Route::get('/{specialty}', [\App\Http\Controllers\Api\V1\Admin\SpecialtyController::class, 'show']);
        });

        Route::middleware('permission:specialty.create')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\Admin\SpecialtyController::class, 'store']);
        });

        Route::middleware('permission:specialty.edit')->group(function () {
            Route::put('/{specialty}', [\App\Http\Controllers\Api\V1\Admin\SpecialtyController::class, 'update']);
        });

        Route::middleware('permission:specialty.delete')->group(function () {
            Route::delete('/{specialty}', [\App\Http\Controllers\Api\V1\Admin\SpecialtyController::class, 'destroy']);
        });
    });

    // ============================================
    // DEPARTMENT ROUTES (CRUD with permissions)
    // ============================================
    Route::prefix('departments')->group(function () {
        Route::middleware('permission:department.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Admin\DepartmentController::class, 'index']);
            Route::get('/{department}', [\App\Http\Controllers\Api\V1\Admin\DepartmentController::class, 'show']);
        });

        Route::middleware('permission:department.create')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\Admin\DepartmentController::class, 'store']);
        });

        Route::middleware('permission:department.edit')->group(function () {
            Route::put('/{department}', [\App\Http\Controllers\Api\V1\Admin\DepartmentController::class, 'update']);
        });

        Route::middleware('permission:department.delete')->group(function () {
            Route::delete('/{department}', [\App\Http\Controllers\Api\V1\Admin\DepartmentController::class, 'destroy']);
        });
    });

    // ============================================
    // EMPLOYEE ROUTES (CRUD with permissions)
    // ============================================
    Route::prefix('employees')->group(function () {
        Route::middleware('permission:employee.view')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Admin\EmployeeController::class, 'index']);
            Route::get('/{employee}', [\App\Http\Controllers\Api\V1\Admin\EmployeeController::class, 'show']);
        });

        Route::middleware('permission:employee.create')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\V1\Admin\EmployeeController::class, 'store']);
        });

        Route::middleware('permission:employee.edit')->group(function () {
            Route::put('/{employee}', [\App\Http\Controllers\Api\V1\Admin\EmployeeController::class, 'update']);
        });

        Route::middleware('permission:employee.delete')->group(function () {
            Route::delete('/{employee}', [\App\Http\Controllers\Api\V1\Admin\EmployeeController::class, 'destroy']);
        });
    });

    // ============================================
    // HEMIS INTEGRATION ROUTES (Admin only)
    // ============================================
    Route::prefix('hemis')->middleware(['throttle:10,1', 'permission:hemis.sync'])->group(function () {
        Route::get('/check', [\App\Http\Controllers\Api\V1\Admin\HemisController::class, 'checkConnection']);
        Route::post('/sync/students', [\App\Http\Controllers\Api\V1\Admin\HemisController::class, 'syncStudents']);
        Route::post('/push/student/{studentId}', [\App\Http\Controllers\Api\V1\Admin\HemisController::class, 'pushStudent']);
        Route::get('/sync/status', [\App\Http\Controllers\Api\V1\Admin\HemisController::class, 'getSyncStatus']);
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
