<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Services\Employee\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Employee Document Controller
 *
 * HTTP LAYER - Clean Architecture
 * ===============================
 *
 * Handles e-document signing for employees
 */
class DocumentController extends Controller
{
    public function __construct(
        private DocumentService $documentService
    ) {}

    /**
     * Get list of documents to sign
     *
     * @OA\Get(
     *     path="/api/v1/employee/documents/sign",
     *     tags={"Employee - Documents"},
     *     summary="Get documents to sign",
     *     description="Returns paginated list of documents that need to be signed by the authenticated employee",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by document title, employee name, or position",
     *         required=false,
     *         @OA\Schema(type="string", example="Buyruq")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by signing status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"pending", "signed"}, example="pending")
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by signer type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"reviewer", "approver"}, example="approver")
     *     ),
     *     @OA\Parameter(
     *         name="document_type",
     *         in="query",
     *         description="Filter by document type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter from date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter to date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2025-12-31")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="items",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="document_title", type="string"),
     *                         @OA\Property(property="document_type", type="string"),
     *                         @OA\Property(property="status", type="string"),
     *                         @OA\Property(property="type", type="string"),
     *                         @OA\Property(property="priority", type="integer"),
     *                         @OA\Property(property="employee_name", type="string"),
     *                         @OA\Property(property="employee_position", type="string"),
     *                         @OA\Property(property="signed_at", type="string", format="datetime", nullable=true),
     *                         @OA\Property(property="created_at", type="string", format="datetime")
     *                     )
     *                 ),
     *                 @OA\Property(property="pagination", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('employee-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $filters = $request->only([
            'search',
            'status',
            'type',
            'document_type',
            'date_from',
            'date_to',
            'per_page',
        ]);

        $documents = $this->documentService->getDocumentsToSign($user, $filters);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $documents->items(),
                'pagination' => [
                    'current_page' => $documents->currentPage(),
                    'last_page' => $documents->lastPage(),
                    'per_page' => $documents->perPage(),
                    'total' => $documents->total(),
                    'from' => $documents->firstItem(),
                    'to' => $documents->lastItem(),
                ],
            ],
        ]);
    }

    /**
     * View specific document
     *
     * @OA\Get(
     *     path="/api/v1/employee/documents/{hash}/view",
     *     tags={"Employee - Documents"},
     *     summary="View document details",
     *     description="Get detailed information about a specific document including all signers",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         description="Document unique hash",
     *         required=true,
     *         @OA\Schema(type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="hash", type="string"),
     *                 @OA\Property(property="document_title", type="string"),
     *                 @OA\Property(property="document_type", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="provider", type="string"),
     *                 @OA\Property(property="signers", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Document not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function view(string $hash, Request $request): JsonResponse
    {
        $user = auth('employee-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $document = $this->documentService->getDocumentByHash($hash, $user);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Hujjat topilmadi',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $document,
        ]);
    }

    /**
     * Sign document
     *
     * @OA\Post(
     *     path="/api/v1/employee/documents/{hash}/sign",
     *     tags={"Employee - Documents"},
     *     summary="Sign document",
     *     description="Sign a document (local provider only)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         description="Document unique hash",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Document signed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hujjat muvaffaqqiyatli imzolandi")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Cannot sign document"),
     *     @OA\Response(response=404, description="Document not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function sign(string $hash, Request $request): JsonResponse
    {
        $user = auth('employee-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        try {
            $result = $this->documentService->signDocument($hash, $user);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get document sign status
     *
     * @OA\Get(
     *     path="/api/v1/employee/documents/{hash}/status",
     *     tags={"Employee - Documents"},
     *     summary="Get document sign status",
     *     description="Check if user can sign and current signing status",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="hash",
     *         in="path",
     *         description="Document unique hash",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="can_sign", type="boolean"),
     *                 @OA\Property(property="already_signed", type="boolean"),
     *                 @OA\Property(property="provider", type="string"),
     *                 @OA\Property(property="signed_count", type="integer"),
     *                 @OA\Property(property="total_signers", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function status(string $hash, Request $request): JsonResponse
    {
        $user = auth('employee-api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $status = $this->documentService->getSignStatus($hash, $user);

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }
}
