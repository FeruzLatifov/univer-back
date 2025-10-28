<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\HemisSyncService;
use Illuminate\Http\Request;

/**
 * HEMIS Integration Controller (Admin Panel)
 *
 * API Version: 1.0
 * Purpose: HEMIS tizimi bilan integratsiya (sync, push, status)
 */
class HemisController extends Controller
{
    protected HemisSyncService $hemisService;

    public function __construct(HemisSyncService $hemisService)
    {
        $this->hemisService = $hemisService;
    }

    /**
     * Check HEMIS API connection
     *
     * @route GET /api/v1/admin/hemis/check
     */
    public function checkConnection()
    {
        $result = $this->hemisService->checkConnection();

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Sync students from HEMIS
     *
     * @route POST /api/v1/admin/hemis/sync/students
     */
    public function syncStudents(Request $request)
    {
        $filters = $request->only(['department_id', 'specialty_id', 'level', 'education_year']);

        $result = $this->hemisService->syncStudents($filters);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Push single student to HEMIS
     *
     * @route POST /api/v1/admin/hemis/push/student/{studentId}
     */
    public function pushStudent($studentId)
    {
        $result = $this->hemisService->pushStudent($studentId);

        return response()->json($result, $result['success'] ? 200 : 500);
    }

    /**
     * Get sync status/history
     *
     * @route GET /api/v1/admin/hemis/sync/status
     */
    public function getSyncStatus()
    {
        // This would typically query a sync_logs table
        // For now, return a simple response

        return response()->json([
            'success' => true,
            'data' => [
                'last_sync' => null, // Would come from sync_logs table
                'status' => 'idle',
                'message' => 'Sinkronizatsiya tarixini ko\'rish uchun sync_logs jadvali kerak',
            ],
        ]);
    }
}
