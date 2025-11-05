<?php
namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\DashboardService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse;
    private DashboardService $service;

    public function __construct(DashboardService $service) { $this->service = $service; }

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getDashboardData($request->user());
        return $this->successResponse($data);
    }
}
