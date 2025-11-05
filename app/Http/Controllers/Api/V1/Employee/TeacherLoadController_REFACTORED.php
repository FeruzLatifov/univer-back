<?php
namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\TeacherLoadService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherLoadController extends Controller
{
    use ApiResponse;
    private TeacherLoadService $service;

    public function __construct(TeacherLoadService $service) { $this->service = $service; }

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getTeacherLoadData($request->user());
        return $this->successResponse($data);
    }
}
