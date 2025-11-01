<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Student Documents Controller
 *
 * Talaba xujjatlari: buyruqlar, sertifikatlar, ma'lumotnomalar, kontrakt
 *
 * @package App\Http\Controllers\Api\V1\Student
 */
class DocumentController extends Controller
{
    /**
     * Talaba buyruqlari ro'yxati
     *
     * Talabaga tegishli barcha buyruqlar ro'yxatini qaytaradi.
     * Buyruqlar yaratilgan sana bo'yicha tartiblangan.
     *
     * @OA\Get(
     *     path="/api/v1/student/decree",
     *     tags={"Student - Documents"},
     *     summary="Buyruqlar ro'yxati",
     *     description="Talabaga tegishli barcha buyruqlarni olish",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Buyruqlar muvaffaqiyatli qaytarildi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="number", type="string", example="123-A"),
     *                     @OA\Property(property="name", type="string", example="Stipendiya to'g'risida buyruq"),
     *                     @OA\Property(property="type", type="string", example="Stipendiya"),
     *                     @OA\Property(property="date", type="string", format="date", example="2024-10-15"),
     *                     @OA\Property(property="description", type="string", example="Stipendiya tayinlash haqida"),
     *                     @OA\Property(property="file_url", type="string", example="http://api.univer.uz/api/v1/student/decree-download/1")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Autentifikatsiya talab qilinadi",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     )
     * )
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function decree(Request $request)
    {
        $student = $request->user();

        $decrees = \App\Models\Academic\EDecreeStudent::where('_student', $student->id)
            ->with(['decree.decreeType'])
            ->whereHas('decree', function ($q) {
                $q->where('active', true);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $decrees->map(function ($decreeStudent) {
                return [
                    'id' => $decreeStudent->decree->id,
                    'number' => $decreeStudent->decree->number,
                    'name' => $decreeStudent->decree->name,
                    'type' => $decreeStudent->decree->decreeType->name ?? null,
                    'date' => $decreeStudent->decree->date,
                    'description' => $decreeStudent->decree->description,
                    'file_url' => $decreeStudent->decree->file
                        ? url("api/v1/student/decree-download/{$decreeStudent->decree->id}")
                        : null,
                ];
            }),
        ]);
    }

    /**
     * Get student certificates (sertifikatlar)
     *
     * GET /v1/student/certificate
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function certificate(Request $request)
    {
        $student = $request->user();

        $certificates = \App\Models\Student\EStudentCertificate::where('_student', $student->id)
            ->where('active', true)
            ->orderBy('issue_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $certificates->map(function ($cert) {
                return [
                    'id' => $cert->id,
                    'name' => $cert->name,
                    'type' => $cert->certificate_type,
                    'issue_date' => $cert->issue_date,
                    'expiry_date' => $cert->expiry_date,
                    'issuer' => $cert->issuer,
                    'certificate_number' => $cert->certificate_number,
                    'score' => $cert->score,
                    'level' => $cert->level,
                    'file_url' => $cert->file_path
                        ? Storage::url($cert->file_path)
                        : null,
                ];
            }),
        ]);
    }

    /**
     * Get student references (ma'lumotnomalar)
     *
     * GET /v1/student/reference
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reference(Request $request)
    {
        $student = $request->user();

        $references = \App\Models\Archive\EStudentReference::where('_student', $student->id)
            ->where('active', true)
            ->with(['semester'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $references->map(function ($ref) {
                return [
                    'id' => $ref->id,
                    'number' => $ref->number,
                    'semester' => $ref->semester ? [
                        'code' => $ref->semester->code,
                        'name' => $ref->semester->name,
                    ] : null,
                    'issue_date' => $ref->created_at->format('Y-m-d'),
                    'status' => $ref->status,
                    'download_url' => url("api/v1/student/reference-download/{$ref->id}"),
                ];
            }),
        ]);
    }

    /**
     * Generate new reference (ma'lumotnoma yaratish)
     *
     * GET /v1/student/reference-generate
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateReference(Request $request)
    {
        $student = $request->user();

        // Check if student can generate reference
        if (!$student->meta || !in_array($student->meta->_student_status, [11, 12, 13])) {
            return response()->json([
                'success' => false,
                'message' => 'Sizning statusingizda ma\'lumotnoma olish imkoni yo\'q',
            ], 400);
        }

        // Check if reference already exists for current semester
        $existingReference = \App\Models\Archive\EStudentReference::where('_student', $student->id)
            ->where('_semester', $student->_semestr)
            ->where('active', true)
            ->first();

        if ($existingReference) {
            return response()->json([
                'success' => false,
                'message' => 'Shu semestr uchun ma\'lumotnoma allaqachon yaratilgan',
                'data' => [
                    'id' => $existingReference->id,
                    'number' => $existingReference->number,
                    'download_url' => url("api/v1/student/reference-download/{$existingReference->id}"),
                ],
            ], 400);
        }

        // Generate new reference number
        $lastReference = \App\Models\Archive\EStudentReference::orderBy('id', 'desc')->first();
        $newNumber = $lastReference ? ($lastReference->id + 1) : 1;
        $referenceNumber = sprintf('REF-%04d-%s', $newNumber, date('Y'));

        // Create new reference
        $reference = \App\Models\Archive\EStudentReference::create([
            '_student' => $student->id,
            '_semester' => $student->_semestr,
            'number' => $referenceNumber,
            'status' => 'active',
            'active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ma\'lumotnoma muvaffaqiyatli yaratildi',
            'data' => [
                'id' => $reference->id,
                'number' => $reference->number,
                'download_url' => url("api/v1/student/reference-download/{$reference->id}"),
            ],
        ]);
    }

    /**
     * Get student documents (diplom, transkript, etc)
     *
     * GET /v1/student/document
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function document(Request $request)
    {
        $student = $request->user();
        $documents = [];

        // Diploma
        $diplomas = \App\Models\Archive\EStudentDiploma::where('_student', $student->id)
            ->where('active', true)
            ->where('accepted', true)
            ->get();

        foreach ($diplomas as $diploma) {
            $documents[] = [
                'id' => $diploma->id,
                'name' => 'Diplom',
                'type' => 'diploma',
                'file' => url("api/v1/student/document-download?id={$diploma->id}&type=diploma"),
                'attributes' => [
                    ['label' => 'Seriya', 'value' => $diploma->serial_number ?? 'N/A'],
                    ['label' => 'Raqam', 'value' => $diploma->diploma_number ?? 'N/A'],
                    ['label' => 'Berilgan sana', 'value' => $diploma->issue_date ?? 'N/A'],
                ],
            ];
        }

        // Academic Information (Akademik ma'lumotnoma)
        $academicInfo = \App\Models\Archive\EAcademicInformation::where('_student', $student->id)
            ->where('active', true)
            ->get();

        foreach ($academicInfo as $info) {
            $documents[] = [
                'id' => $info->id,
                'name' => 'Akademik ma\'lumotnoma',
                'type' => 'academic_info',
                'file' => url("api/v1/student/document-download?id={$info->id}&type=academic_info"),
                'attributes' => [
                    ['label' => 'Turi', 'value' => $info->information_type ?? 'N/A'],
                    ['label' => 'Sana', 'value' => $info->issue_date ?? 'N/A'],
                ],
            ];
        }

        // Academic Record (Transkript)
        $records = \App\Models\Archive\EAcademicRecord::where('_student', $student->id)
            ->where('active', true)
            ->get();

        foreach ($records as $record) {
            $documents[] = [
                'id' => $record->id,
                'name' => 'Transkript',
                'type' => 'transcript',
                'file' => url("api/v1/student/document-download?id={$record->id}&type=transcript"),
                'attributes' => [
                    ['label' => 'Turi', 'value' => $record->record_type ?? 'N/A'],
                    ['label' => 'Sana', 'value' => $record->issue_date ?? 'N/A'],
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $documents,
        ]);
    }

    /**
     * Get all student documents (combined)
     *
     * GET /v1/student/document-all
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function documentAll(Request $request)
    {
        $allDocuments = [];

        // Get decrees
        $decrees = $this->decree($request)->getData()->data;
        foreach ($decrees as $decree) {
            $allDocuments[] = [
                'id' => $decree->id,
                'name' => $decree->name ?? 'Buyruq',
                'type' => 'decree',
                'file' => $decree->file_url,
                'attributes' => [
                    ['label' => 'Raqam', 'value' => $decree->number],
                    ['label' => 'Sana', 'value' => $decree->date],
                ],
            ];
        }

        // Get documents
        $documents = $this->document($request)->getData()->data;
        $allDocuments = array_merge($allDocuments, $documents);

        // Get references
        $references = $this->reference($request)->getData()->data;
        foreach ($references as $ref) {
            $allDocuments[] = [
                'id' => $ref->id,
                'name' => 'Ma\'lumotnoma',
                'type' => 'reference',
                'file' => $ref->download_url,
                'attributes' => [
                    ['label' => 'Raqam', 'value' => $ref->number],
                    ['label' => 'Sana', 'value' => $ref->issue_date],
                ],
            ];
        }

        // Get contracts
        $contracts = $this->contractList($request)->getData()->data->items ?? [];
        foreach ($contracts as $contract) {
            $allDocuments[] = [
                'id' => $contract->id,
                'name' => 'Kontrakt',
                'type' => 'contract',
                'file' => url("api/v1/student/contract-download/{$contract->id}"),
                'attributes' => [
                    ['label' => 'Raqam', 'value' => $contract->number ?? 'N/A'],
                    ['label' => 'Sana', 'value' => $contract->contract_date ?? 'N/A'],
                    ['label' => 'Summa', 'value' => number_format($contract->amount ?? 0, 0, '.', ' ') . ' so\'m'],
                ],
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $allDocuments,
        ]);
    }

    /**
     * Get student contract list
     *
     * GET /v1/student/contract-list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function contractList(Request $request)
    {
        $student = $request->user();

        $contracts = \App\Models\Finance\EStudentContract::where('_student', $student->id)
            ->where('active', true)
            ->with(['educationYear'])
            ->orderBy('contract_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $contracts->map(function ($contract) {
                    return [
                        'id' => $contract->id,
                        'number' => $contract->number,
                        'contract_date' => $contract->contract_date,
                        'amount' => $contract->amount,
                        'education_year' => $contract->educationYear ? [
                            'code' => $contract->educationYear->code,
                            'name' => $contract->educationYear->name,
                        ] : null,
                        'status' => $contract->status,
                    ];
                }),
                'attributes' => [
                    'number' => 'Kontrakt raqami',
                    'contract_date' => 'Tuzilgan sana',
                    'amount' => 'Kontrakt summasi',
                    'status' => 'Holati',
                ],
            ],
        ]);
    }

    /**
     * Get current year contract (HEMIS API integration)
     *
     * GET /v1/student/contract
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function contract(Request $request)
    {
        $student = $request->user();

        // Get current education year contract
        $currentYear = date('Y');
        $contract = \App\Models\Finance\EStudentContract::where('_student', $student->id)
            ->whereHas('educationYear', function ($q) use ($currentYear) {
                $q->where('code', $currentYear);
            })
            ->where('active', true)
            ->first();

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Joriy o\'quv yili uchun kontrakt topilmadi',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $contract->id,
                'number' => $contract->number,
                'contract_date' => $contract->contract_date,
                'amount' => $contract->amount,
                'paid_amount' => $contract->paid_amount ?? 0,
                'remaining_amount' => ($contract->amount ?? 0) - ($contract->paid_amount ?? 0),
                'status' => $contract->status,
                'download_url' => url("api/v1/student/contract-download/{$contract->id}"),
            ],
        ]);
    }

    /**
     * Download document file
     *
     * GET /v1/student/document-download?id=X&type=diploma
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadDocument(Request $request)
    {
        $id = $request->get('id');
        $type = $request->get('type');
        $student = $request->user();

        if (!$id || !$type) {
            return response()->json([
                'success' => false,
                'message' => 'ID va type parametrlari talab qilinadi',
            ], 400);
        }

        try {
            switch ($type) {
                case 'diploma':
                    $document = \App\Models\Archive\EStudentDiploma::where('_student', $student->id)
                        ->where('id', $id)
                        ->firstOrFail();
                    $filePath = $document->file_path;
                    $fileName = "Diplom_{$student->student_id_number}.pdf";
                    break;

                case 'academic_info':
                    $document = \App\Models\Archive\EAcademicInformation::where('_student', $student->id)
                        ->where('id', $id)
                        ->firstOrFail();
                    $filePath = $document->file_path;
                    $fileName = "Academic_Info_{$student->student_id_number}.pdf";
                    break;

                case 'transcript':
                    $document = \App\Models\Archive\EAcademicRecord::where('_student', $student->id)
                        ->where('id', $id)
                        ->firstOrFail();
                    $filePath = $document->file_path;
                    $fileName = "Transcript_{$student->student_id_number}.pdf";
                    break;

                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Noto\'g\'ri document turi',
                    ], 400);
            }

            if (!$filePath || !Storage::exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fayl topilmadi',
                ], 404);
            }

            return Storage::download($filePath, $fileName);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xujjat topilmadi',
            ], 404);
        }
    }

    /**
     * Download decree file
     *
     * GET /v1/student/decree-download/{id}
     *
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadDecree(Request $request, $id)
    {
        $student = $request->user();

        $decreeStudent = \App\Models\Academic\EDecreeStudent::where('_student', $student->id)
            ->where('_decree', $id)
            ->with('decree')
            ->firstOrFail();

        $decree = $decreeStudent->decree;

        if (!$decree->file || !Storage::exists($decree->file)) {
            return response()->json([
                'success' => false,
                'message' => 'Fayl topilmadi',
            ], 404);
        }

        return Storage::download($decree->file, "{$decree->number}.pdf");
    }

    /**
     * Download contract file
     *
     * GET /v1/student/contract-download/{id}
     *
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
     */
    public function downloadContract(Request $request, $id)
    {
        $student = $request->user();

        $contract = \App\Models\Finance\EStudentContract::where('_student', $student->id)
            ->where('id', $id)
            ->firstOrFail();

        if (!$contract->file_path || !Storage::exists($contract->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'Fayl topilmadi',
            ], 404);
        }

        return Storage::download($contract->file_path, "Kontrakt_{$contract->number}.pdf");
    }

    /**
     * Download reference PDF
     *
     * GET /v1/student/reference-download/{id}
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function downloadReference(Request $request, $id)
    {
        $student = $request->user();

        $reference = \App\Models\Archive\EStudentReference::where('_student', $student->id)
            ->where('id', $id)
            ->with(['student.meta', 'student.group', 'student.specialty', 'semester'])
            ->firstOrFail();

        // Generate PDF using DomPDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.reference', [
            'reference' => $reference,
            'student' => $reference->student,
            'university' => \App\Models\Structure\EUniversity::first(),
        ]);

        return $pdf->download("Malumotnoma_{$reference->number}.pdf");
    }
}
