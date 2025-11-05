<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'HEMIS Univer Backend API',
        'version' => '1.0.0',
        'architecture' => 'Single Source of Truth + Permission-based Filtering',
        'docs' => url('/docs/api'),
        'info' => [
            'documentation' => 'Complete API documentation (OpenAPI 3.0.3)',
            'regenerate' => 'php artisan docs:generate --all',
        ],
    ]);
});

// Main documentation endpoint - single entry point
Route::get('/docs', function () {
    return redirect('/docs/api');
});

// Main API Documentation - Shows complete API spec
Route::get('/docs/api', function () {
    $jsonPath = storage_path('api-docs/api-docs.json');

    if (!file_exists($jsonPath)) {
        return response()->json([
            'error' => 'Documentation not found',
            'message' => 'API documentation is not available.',
        ], 404);
    }

    return view('swagger.index', [
        'role' => 'api',
        'title' => 'HEMIS University - Complete API Documentation',
        'yamlUrl' => url('/docs/api/spec'),
    ]);
})->name('docs.api');

// Legacy role-specific routes (kept for backward compatibility)
Route::get('/docs/{role}', function ($role) {
    $validRoles = ['master', 'teacher', 'student', 'admin', 'integration'];

    if (!in_array($role, $validRoles)) {
        abort(404);
    }

    $yamlPath = storage_path("api-docs/{$role}-api.yaml");

    if (!file_exists($yamlPath)) {
        return response()->json([
            'error' => 'Documentation not found',
            'message' => "API documentation for '{$role}' role is not available yet.",
            'available_roles' => $validRoles
        ], 404);
    }

    return view('swagger.index', [
        'role' => $role,
        'title' => ucfirst($role) . ' API - HEMIS University',
        'yamlUrl' => url("/docs/{$role}/spec"),
    ]);
});

// Serve main API spec file (JSON)
Route::get('/docs/api/spec', function () {
    $jsonPath = storage_path('api-docs/api-docs.json');

    if (!file_exists($jsonPath)) {
        abort(404, 'API documentation not found.');
    }

    return response()->file($jsonPath, [
        'Content-Type' => 'application/json',
        'Content-Disposition' => 'inline',
    ]);
});

// Serve YAML spec file (role-specific, legacy)
Route::get('/docs/{role}/spec', function ($role) {
    $validRoles = ['master', 'teacher', 'student', 'admin', 'integration'];

    if (!in_array($role, $validRoles)) {
        abort(404);
    }

    $yamlPath = storage_path("api-docs/{$role}-api.yaml");

    if (!file_exists($yamlPath)) {
        abort(404, "API documentation for '{$role}' not found");
    }

    return response()->file($yamlPath, [
        'Content-Type' => 'application/x-yaml',
        'Content-Disposition' => 'inline',
    ]);
})->name('docs.spec');

Route::get('/docs/employee', function () {
    $path = public_path('docs/employee.html');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path);
});

// Legacy-compatible redirect for document signing/viewing
// Example: /document/sign-documents?document=HASH&view=1 or &sign-document=1
Route::get('/document/sign-documents', function (\Illuminate\Http\Request $request) {
    $legacyBase = rtrim((string) env('LEGACY_YII2_BASE_URL', ''), '/');

    if ($legacyBase === '') {
        return response()->json([
            'success' => false,
            'message' => 'LEGACY_YII2_BASE_URL is not configured',
            'hint' => 'Set LEGACY_YII2_BASE_URL in .env to your Yii2 base URL',
        ], 404);
    }

    $query = $request->getQueryString();
    $target = $legacyBase . '/document/sign-documents' . ($query ? ('?' . $query) : '');
    return redirect()->away($target);
});
