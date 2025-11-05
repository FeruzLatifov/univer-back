<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Employee\AuthController as EmployeeAuthController;
use App\Http\Controllers\Api\V1\Student\AuthController as StudentAuthController;
use App\Http\Controllers\Api\V1\Student\DocumentController;
use App\Http\Controllers\Api\V1\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Api\V1\Admin\GroupController;
use App\Http\Controllers\Api\V1\Admin\SpecialtyController;
use App\Http\Controllers\Api\V1\Admin\DepartmentController;
use App\Http\Controllers\Api\V1\Admin\EmployeeController;
use App\Http\Controllers\Api\V1\Admin\HemisController;
use App\Http\Controllers\Api\V1\Teacher\SubjectController as TeacherSubjectController;
use App\Http\Controllers\Api\V1\Teacher\ScheduleController as TeacherScheduleController;
use App\Http\Controllers\Api\V1\Teacher\AttendanceController as TeacherAttendanceController;
use App\Http\Controllers\Api\V1\Teacher\GradeController as TeacherGradeController;
use App\Http\Controllers\Api\V1\Teacher\ResourceController as TeacherResourceController;
use App\Http\Controllers\Api\V1\Teacher\TopicController as TeacherTopicController;
use App\Http\Controllers\Api\V1\Teacher\ExamController as TeacherExamController;
use App\Http\Controllers\Api\V1\Teacher\AssignmentController as TeacherAssignmentController;
use App\Http\Controllers\Api\V1\Teacher\TestController as TeacherTestController;
use App\Http\Controllers\Api\V1\LanguageController;
use App\Http\Controllers\Api\Admin\TranslationController;
use App\Http\Controllers\Api\V1\Employee\DocumentController as EmployeeDocumentController;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
|
| Version 1 of the API
| Base URL: /api/v1/*
|
*/

// ==========================================
// PUBLIC ENDPOINTS
// ==========================================
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});

// Language/Localization (Public)
Route::prefix('languages')->group(function () {
    Route::get('/', [LanguageController::class, 'index']);
    Route::get('/current', [LanguageController::class, 'current']);
    Route::get('/{code}', [LanguageController::class, 'show']);
    Route::post('/set', [LanguageController::class, 'setLanguage']);
});


// New preferred prefix: employee (aliases to the same controllers/guards)
Route::prefix('employee')->group(function () {
    // Auth
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/login', [EmployeeAuthController::class, 'login']);
        Route::post('/forgot-password', [EmployeeAuthController::class, 'forgotPassword'])->middleware('throttle:password');
        Route::post('/reset-password', [EmployeeAuthController::class, 'resetPassword'])->middleware('throttle:password');

        Route::middleware('auth:employee-api')->group(function () {
            Route::post('/logout', [EmployeeAuthController::class, 'logout']);
            Route::post('/refresh', [EmployeeAuthController::class, 'refresh']);
            Route::get('/me', [EmployeeAuthController::class, 'me']);
            Route::post('/role/switch', [EmployeeAuthController::class, 'switchRole']);
        });
    });

    // Protected Employee Routes (Self-Service Portal)
    Route::middleware('auth:employee-api')->group(function () {
        // TODO: Implement Employee\ProfileController for employee self-service
        // Route::get('/profile', [EmployeeProfileController::class, 'show']);
        // Route::put('/profile', [EmployeeProfileController::class, 'update']);
        // Route::post('/profile/avatar', [EmployeeProfileController::class, 'uploadAvatar']);

        // Teacher Load
        Route::prefix('teacher-load')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\V1\Employee\TeacherLoadController::class, 'index']);
            Route::get('/{id}', [\App\Http\Controllers\Api\V1\Employee\TeacherLoadController::class, 'show']);
            Route::get('/{id}/download', [\App\Http\Controllers\Api\V1\Employee\TeacherLoadController::class, 'download']);
        });

        // E-Documents (Sign Documents)
        Route::prefix('documents')->group(function () {
            Route::get('/sign', [EmployeeDocumentController::class, 'index']);
            Route::get('/{hash}/view', [EmployeeDocumentController::class, 'view']);
            Route::post('/{hash}/sign', [EmployeeDocumentController::class, 'sign']);
            Route::get('/{hash}/status', [EmployeeDocumentController::class, 'status']);
        });
    });
});

// ==========================================
// USER PERMISSIONS (for background refresh in frontend)
// ==========================================
Route::middleware(['auth:employee-api,admin-api,student-api'])->get('/user/permissions', function (Request $request) {
    $user = auth('employee-api')->user() ?? auth('admin-api')->user() ?? auth('student-api')->user();

    // Default empty permissions
    $permissions = [];

    if ($user && method_exists($user, 'getAllPermissions')) {
        try {
            $permissions = $user->getAllPermissions();
        } catch (\Throwable $e) {
            // Fail silently; frontend handles empty array
            logger()->warning('user/permissions failed', ['error' => $e->getMessage()]);
        }
    }

    return response()->json([
        'permissions' => $permissions,
    ]);
});

// ==========================================
// STUDENT (Talabalar) - Authentication & Self Service
// ==========================================
Route::prefix('student')->group(function () {
    // Auth
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('/login', [StudentAuthController::class, 'login']);

        // Password reset (student)
        Route::post('/forgot-password', [StudentAuthController::class, 'forgotPassword'])->middleware('throttle:password');
        Route::post('/reset-password', [StudentAuthController::class, 'resetPassword'])->middleware('throttle:password');

        // Optional 2FA endpoints (enabled via env)
        Route::post('/2fa/challenge', [StudentAuthController::class, 'twoFAChallenge']);
        Route::post('/2fa/verify', [StudentAuthController::class, 'twoFAVerify']);

        Route::middleware('auth:student-api')->group(function () {
            Route::post('/logout', [StudentAuthController::class, 'logout']);
            Route::post('/refresh', [StudentAuthController::class, 'refresh']);
            Route::get('/me', [StudentAuthController::class, 'me']);
        });
    });

    // Protected Student Routes (Self-Service Portal)
    Route::middleware('auth:student-api')->group(function () {
        // Profile
        Route::get('/profile', [AdminStudentController::class, 'myProfile']);
        Route::put('/profile', [AdminStudentController::class, 'updateProfile']);
        Route::post('/profile/avatar', [AdminStudentController::class, 'uploadAvatar']);

        // Documents (Hujjatlar)
        Route::get('/decree', [DocumentController::class, 'decree']);
        Route::get('/certificate', [DocumentController::class, 'certificate']);
        Route::get('/reference', [DocumentController::class, 'reference']);
        Route::get('/reference-generate', [DocumentController::class, 'generateReference']);
        Route::get('/document', [DocumentController::class, 'document']);
        Route::get('/document-all', [DocumentController::class, 'documentAll']);

        // Contracts (Kontrakt)
        Route::get('/contract-list', [DocumentController::class, 'contractList']);
        Route::get('/contract', [DocumentController::class, 'contract']);

        // Downloads
        Route::get('/document-download', [DocumentController::class, 'downloadDocument']);
        Route::get('/decree-download/{id}', [DocumentController::class, 'downloadDecree']);
        Route::get('/contract-download/{id}', [DocumentController::class, 'downloadContract']);
        Route::get('/reference-download/{id}', [DocumentController::class, 'downloadReference']);
    });
});

// ==========================================
// TEACHER (O'qituvchilar) - Teaching Portal
// ==========================================
Route::prefix('teacher')->middleware('auth:employee-api')->group(function () {

    // Subjects & Teaching Load
    Route::get('/subjects', [TeacherSubjectController::class, 'index']);
    Route::get('/subject/{id}', [TeacherSubjectController::class, 'show']);
    Route::get('/subject/{id}/students', [TeacherSubjectController::class, 'students']);

    // Schedule & Workload
    Route::get('/schedule', [TeacherScheduleController::class, 'index']);
    Route::get('/schedule/day/{day}', [TeacherScheduleController::class, 'day']);
    Route::get('/workload', [TeacherScheduleController::class, 'workload']);
    Route::get('/groups', [TeacherScheduleController::class, 'groups']);

    // Attendance Management
    Route::get('/subject/{id}/attendance', [TeacherAttendanceController::class, 'index']);
    Route::post('/attendance/mark', [TeacherAttendanceController::class, 'mark']);
    Route::put('/attendance/{id}', [TeacherAttendanceController::class, 'update']);
    Route::get('/attendance/report', [TeacherAttendanceController::class, 'report']);

    // Grading & Performance
    Route::get('/subject/{id}/grades', [TeacherGradeController::class, 'index']);
    Route::post('/grade', [TeacherGradeController::class, 'store']);
    Route::put('/grade/{id}', [TeacherGradeController::class, 'update']);
    Route::get('/grade/report', [TeacherGradeController::class, 'report']);

    // Course Materials & Resources
    Route::get('/subject/{id}/resources', [TeacherResourceController::class, 'index']);
    Route::post('/subject/{id}/resource', [TeacherResourceController::class, 'store']);
    Route::put('/resource/{id}', [TeacherResourceController::class, 'update']);
    Route::get('/resource/{id}/download', [TeacherResourceController::class, 'download'])->name('api.teacher.resource.download');
    Route::delete('/resource/{id}', [TeacherResourceController::class, 'destroy']);
    Route::get('/resource/types', [TeacherResourceController::class, 'types']);

    // Syllabus & Topics
    Route::get('/subject/{id}/topics', [TeacherTopicController::class, 'index']);
    Route::post('/subject/{id}/topic', [TeacherTopicController::class, 'store']);
    Route::put('/subject/{subjectId}/topic/{topicId}', [TeacherTopicController::class, 'update']);
    Route::delete('/subject/{subjectId}/topic/{topicId}', [TeacherTopicController::class, 'destroy']);
    Route::post('/subject/{id}/topics/reorder', [TeacherTopicController::class, 'reorder']);
    Route::get('/subject/{id}/syllabus', [TeacherTopicController::class, 'syllabus']);

    // Exam Management
    Route::get('/exams', [TeacherExamController::class, 'index']);
    Route::post('/exam', [TeacherExamController::class, 'store']);
    Route::get('/exam/{id}', [TeacherExamController::class, 'show']);
    Route::post('/exam/{id}/results', [TeacherExamController::class, 'enterResults']);
    Route::get('/exam/{id}/statistics', [TeacherExamController::class, 'statistics']);

    // Assignment/Task Management
    Route::get('/assignments', [TeacherAssignmentController::class, 'index']);
    Route::post('/assignment', [TeacherAssignmentController::class, 'store']);
    Route::get('/assignment/{id}', [TeacherAssignmentController::class, 'show']);
    Route::put('/assignment/{id}', [TeacherAssignmentController::class, 'update']);
    Route::delete('/assignment/{id}', [TeacherAssignmentController::class, 'destroy']);
    Route::post('/assignment/{id}/publish', [TeacherAssignmentController::class, 'publish']);
    Route::post('/assignment/{id}/unpublish', [TeacherAssignmentController::class, 'unpublish']);
    Route::get('/assignment/{id}/submissions', [TeacherAssignmentController::class, 'submissions']);
    Route::get('/assignment/{id}/statistics', [TeacherAssignmentController::class, 'statistics']);
    Route::get('/assignment/{id}/activities', [TeacherAssignmentController::class, 'activities']);
    Route::get('/submission/{id}', [TeacherAssignmentController::class, 'submissionDetail']);
    Route::post('/submission/{id}/grade', [TeacherAssignmentController::class, 'gradeSubmission']);
    Route::get('/submission/{id}/download/{fileIndex?}', [TeacherAssignmentController::class, 'downloadSubmissionFile']);

    // Helper endpoints for dropdowns
    Route::get('/my-subjects', [TeacherAssignmentController::class, 'mySubjects']);
    Route::get('/my-groups', [TeacherAssignmentController::class, 'myGroups']);

    // Test/Quiz Management
    Route::get('/tests', [TeacherTestController::class, 'index']);
    Route::post('/test', [TeacherTestController::class, 'store']);
    Route::get('/test/{id}', [TeacherTestController::class, 'show']);
    Route::put('/test/{id}', [TeacherTestController::class, 'update']);
    Route::delete('/test/{id}', [TeacherTestController::class, 'destroy']);
    Route::post('/test/{id}/duplicate', [TeacherTestController::class, 'duplicate']);
    Route::post('/test/{id}/publish', [TeacherTestController::class, 'publish']);
    Route::post('/test/{id}/unpublish', [TeacherTestController::class, 'unpublish']);

    // Question Management
    Route::get('/test/{testId}/questions', [TeacherTestController::class, 'getQuestions']);
    Route::post('/test/{testId}/question', [TeacherTestController::class, 'addQuestion']);
    Route::get('/test/{testId}/question/{id}', [TeacherTestController::class, 'getQuestion']);
    Route::put('/test/{testId}/question/{id}', [TeacherTestController::class, 'updateQuestion']);
    Route::delete('/test/{testId}/question/{id}', [TeacherTestController::class, 'deleteQuestion']);
    Route::post('/test/{testId}/questions/reorder', [TeacherTestController::class, 'reorderQuestions']);
    Route::post('/test/{testId}/question/{id}/duplicate', [TeacherTestController::class, 'duplicateQuestion']);

    // Answer Options (for Multiple Choice questions)
    Route::post('/test/{testId}/question/{questionId}/answer', [TeacherTestController::class, 'addAnswer']);
    Route::put('/test/{testId}/question/{questionId}/answer/{id}', [TeacherTestController::class, 'updateAnswer']);
    Route::delete('/test/{testId}/question/{questionId}/answer/{id}', [TeacherTestController::class, 'deleteAnswer']);

    // Test Results & Grading
    Route::get('/test/{testId}/results', [TeacherTestController::class, 'getResults']);
    Route::get('/test/{testId}/attempt/{attemptId}', [TeacherTestController::class, 'getAttempt']);
    Route::post('/test/{testId}/attempt/{attemptId}/grade', [TeacherTestController::class, 'gradeAttempt']);
});

// ==========================================
// MENU & PERMISSIONS API
// ==========================================
use App\Http\Controllers\Api\V1\Employee\MenuController;

// Removed legacy /staff/* routes

// Employee prefix (preferred) for Menu endpoints
Route::prefix('employee')->middleware(['auth:employee-api'])->group(function () {
    Route::get('/menu', [MenuController::class, 'index']);
    Route::post('/menu/check-access', [MenuController::class, 'checkAccess']);
    Route::post('/menu/clear-cache', [MenuController::class, 'clearCache']);
    Route::get('/menu/structure', [MenuController::class, 'structure']);
});

// ==========================================
// ADMIN PANEL - Management/CRUD
// ==========================================
Route::prefix('admin')->middleware(['auth:employee-api', 'throttle:students'])->group(function () {

    // Students Management
    Route::apiResource('students', AdminStudentController::class);
    Route::post('students/{id}/image', [AdminStudentController::class, 'uploadImage']);

    // Employees Management
    Route::apiResource('employees', EmployeeController::class);

    // Groups Management
    Route::apiResource('groups', GroupController::class);

    // Specialties Management
    Route::apiResource('specialties', SpecialtyController::class);

    // Departments Management
    Route::apiResource('departments', DepartmentController::class);

    // HEMIS Integration
    Route::prefix('hemis')->middleware('throttle:10,1')->group(function () {
        Route::get('/check', [HemisController::class, 'checkConnection']);
        Route::post('/sync/students', [HemisController::class, 'syncStudents']);
        Route::post('/push/student/{studentId}', [HemisController::class, 'pushStudent']);
        Route::get('/sync/status', [HemisController::class, 'getSyncStatus']);
    });

    // Translation Management (Tarjimalar boshqaruvi)
    Route::prefix('translations')->group(function () {
        Route::get('/', [TranslationController::class, 'index']);
        Route::post('/', [TranslationController::class, 'store']);
        Route::get('/categories', [TranslationController::class, 'categories']);
        Route::get('/stats', [TranslationController::class, 'stats']);
        Route::post('/clear-cache', [TranslationController::class, 'clearCache']);
        Route::get('/{id}', [TranslationController::class, 'show']);
        Route::put('/{id}', [TranslationController::class, 'update']);
        Route::delete('/{id}', [TranslationController::class, 'destroy']);
    });
});
