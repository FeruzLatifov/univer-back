<?php
namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\DocumentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DocumentController extends Controller
{
    use ApiResponse;
    private DocumentService $service;

    public function __construct(DocumentService $service) { $this->service = $service; }

    public function index(Request $request): JsonResponse
    {
        $data = $this->service->getDocumentData($request->user());
        return $this->successResponse($data);
    }
}
