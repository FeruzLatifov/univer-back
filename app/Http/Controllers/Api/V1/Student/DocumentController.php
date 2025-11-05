<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Services\Student\DocumentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Student Documents Controller
 *
 * MODULAR MONOLITH - Student Module
 * HTTP LAYER ONLY - No business logic!
 *
 * Clean Architecture:
 * Controller → Service → Repository → Model
 *
 * @package App\Http\Controllers\Api\V1\Student
 */
class DocumentController extends Controller
{
    use ApiResponse;

    /**
     * Document Service (injected)
     */
    private DocumentService $documentService;

    /**
     * Constructor with dependency injection
     */
    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Get student decrees list
     *
     * @OA\Get(
     *     path="/api/v1/student/decree",
     *     tags={"Student - Documents"},
     *     summary="Get student decrees",
     *     description="Returns a list of all official decrees related to the authenticated student",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="decree_number", type="string", example="123/2025"),
     *                     @OA\Property(property="title", type="string", example="Enrollment Decree"),
     *                     @OA\Property(property="date", type="string", format="date", example="2025-09-01"),
     *                     @OA\Property(property="file_path", type="string", nullable=true, example="/storage/decrees/123.pdf")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function decree(Request $request): JsonResponse
    {
        $student = $request->user();
        $decrees = $this->documentService->getDecrees($student);

        return $this->successResponse($decrees);
    }

    /**
     * Get student certificates
     *
     * @OA\Get(
     *     path="/api/v1/student/certificate",
     *     tags={"Student - Documents"},
     *     summary="Get student certificates",
     *     description="Returns a list of all certificates issued to the authenticated student",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="certificate_type", type="string", example="Academic Excellence"),
     *                     @OA\Property(property="issue_date", type="string", format="date", example="2025-06-15"),
     *                     @OA\Property(property="file_path", type="string", nullable=true)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function certificate(Request $request): JsonResponse
    {
        $student = $request->user();
        $certificates = $this->documentService->getCertificates($student);

        return $this->successResponse($certificates);
    }

    /**
     * Get student references
     *
     * @OA\Get(
     *     path="/api/v1/student/reference",
     *     tags={"Student - Documents"},
     *     summary="Get student references",
     *     description="Returns a list of all reference documents generated for the authenticated student",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="reference_number", type="string", example="REF-2025-001"),
     *                     @OA\Property(property="created_date", type="string", format="date", example="2025-11-01"),
     *                     @OA\Property(property="status", type="string", example="issued")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function reference(Request $request): JsonResponse
    {
        $student = $request->user();
        $references = $this->documentService->getReferences($student);

        return $this->successResponse($references);
    }

    /**
     * Generate new reference
     *
     * @OA\Post(
     *     path="/api/v1/student/reference-generate",
     *     tags={"Student - Documents"},
     *     summary="Generate a new reference document",
     *     description="Generates a new official reference document for the authenticated student",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Reference generated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=15),
     *                 @OA\Property(property="reference_number", type="string", example="REF-2025-015"),
     *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-11-05 10:30:00")
     *             ),
     *             @OA\Property(property="message", type="string", example="Ma'lumotnoma muvaffaqiyatli yaratildi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request or generation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Cannot generate reference at this time")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function generateReference(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $reference = $this->documentService->generateReference($student);

            return $this->successResponse($reference, 'Ma\'lumotnoma muvaffaqiyatli yaratildi');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get student academic documents
     *
     * @OA\Get(
     *     path="/api/v1/student/document",
     *     tags={"Student - Documents"},
     *     summary="Get student academic documents",
     *     description="Returns a list of academic documents such as diplomas, transcripts, and academic records",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="document_type", type="string", example="diploma"),
     *                     @OA\Property(property="title", type="string", example="Bachelor Diploma"),
     *                     @OA\Property(property="issue_date", type="string", format="date", example="2025-07-01")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function document(Request $request): JsonResponse
    {
        $student = $request->user();
        $documents = $this->documentService->getAcademicDocuments($student);

        return $this->successResponse($documents);
    }

    /**
     * Get all student documents (combined)
     *
     * @OA\Get(
     *     path="/api/v1/student/document-all",
     *     tags={"Student - Documents"},
     *     summary="Get all student documents",
     *     description="Returns a combined list of all types of documents (decrees, certificates, references, academic documents, contracts)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="decrees", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="certificates", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="references", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="academic_documents", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="contracts", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function documentAll(Request $request): JsonResponse
    {
        $student = $request->user();
        $allDocuments = $this->documentService->getAllDocuments($student);

        return $this->successResponse($allDocuments);
    }

    /**
     * Get student contract list
     *
     * @OA\Get(
     *     path="/api/v1/student/contract-list",
     *     tags={"Student - Documents"},
     *     summary="Get student contract list",
     *     description="Returns a list of all student contracts (tuition agreements, enrollment contracts, etc.)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="contract_number", type="string", example="CT-2025-001"),
     *                     @OA\Property(property="academic_year", type="string", example="2024-2025"),
     *                     @OA\Property(property="amount", type="number", example=5000000),
     *                     @OA\Property(property="status", type="string", example="active")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function contractList(Request $request): JsonResponse
    {
        $student = $request->user();
        $contracts = $this->documentService->getContractList($student);

        return $this->successResponse($contracts);
    }

    /**
     * Get current year contract
     *
     * @OA\Get(
     *     path="/api/v1/student/contract",
     *     tags={"Student - Documents"},
     *     summary="Get current academic year contract",
     *     description="Returns the contract details for the current academic year",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="contract_number", type="string", example="CT-2025-001"),
     *                 @OA\Property(property="academic_year", type="string", example="2024-2025"),
     *                 @OA\Property(property="amount", type="number", example=5000000),
     *                 @OA\Property(property="status", type="string", example="active"),
     *                 @OA\Property(property="signed_date", type="string", format="date", example="2024-09-01")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=404,
     *         description="Contract not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No contract found for current year")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function contract(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $contract = $this->documentService->getCurrentContract($student);

            return $this->successResponse($contract);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Download document file
     *
     * @OA\Get(
     *     path="/api/v1/student/document-download",
     *     tags={"Student - Documents"},
     *     summary="Download a document file",
     *     description="Downloads a specific document file by ID and type",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         required=true,
     *         description="Document ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=true,
     *         description="Document type",
     *         @OA\Schema(type="string", enum={"diploma", "transcript", "certificate"}, example="diploma")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Missing parameters",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="ID va type parametrlari talab qilinadi")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Document not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function downloadDocument(Request $request)
    {
        $id = $request->get('id');
        $type = $request->get('type');
        $student = $request->user();

        if (!$id || !$type) {
            return $this->errorResponse('ID va type parametrlari talab qilinadi', 400);
        }

        try {
            return $this->documentService->downloadDocument($student, $id, $type);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * Download decree file
     *
     * @OA\Get(
     *     path="/api/v1/student/decree-download/{id}",
     *     tags={"Student - Documents"},
     *     summary="Download decree file",
     *     description="Downloads the PDF file of a specific decree",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Decree ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=404,
     *         description="File not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Fayl topilmadi")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function downloadDecree(Request $request, $id)
    {
        try {
            $student = $request->user();
            return $this->documentService->downloadDecree($student, $id);

        } catch (\Exception $e) {
            return $this->errorResponse('Fayl topilmadi', 404);
        }
    }

    /**
     * Download contract file
     *
     * @OA\Get(
     *     path="/api/v1/student/contract-download/{id}",
     *     tags={"Student - Documents"},
     *     summary="Download contract file",
     *     description="Downloads the PDF file of a specific contract",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Contract ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=404,
     *         description="File not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Fayl topilmadi")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function downloadContract(Request $request, $id)
    {
        try {
            $student = $request->user();
            return $this->documentService->downloadContract($student, $id);

        } catch (\Exception $e) {
            return $this->errorResponse('Fayl topilmadi', 404);
        }
    }

    /**
     * Download reference PDF
     *
     * @OA\Get(
     *     path="/api/v1/student/reference-download/{id}",
     *     tags={"Student - Documents"},
     *     summary="Download reference PDF",
     *     description="Downloads the generated PDF file of a specific reference document",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Reference ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File download",
     *         @OA\MediaType(mediaType="application/pdf", @OA\Schema(type="string", format="binary"))
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(
     *         response=404,
     *         description="Reference not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ma'lumotnoma topilmadi")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function downloadReference(Request $request, $id)
    {
        try {
            $student = $request->user();
            return $this->documentService->downloadReference($student, $id);

        } catch (\Exception $e) {
            return $this->errorResponse('Ma\'lumotnoma topilmadi', 404);
        }
    }
}
