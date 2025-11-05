<?php
namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\MenuService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    use ApiResponse;
    private MenuService $service;

    public function __construct(MenuService $service) { $this->service = $service; }

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getMenuData($request->user());
        return $this->successResponse($data);
    }
}
