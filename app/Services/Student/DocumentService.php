<?php

namespace App\Services\Student;

use App\Models\System\EStudent;
use App\Models\Academic\EDecreeStudent;
use App\Models\Student\EStudentCertificate;
use App\Models\Archive\EStudentReference;
use App\Models\Archive\EStudentDiploma;
use App\Models\Archive\EAcademicInformation;
use App\Models\Archive\EAcademicRecord;
use App\Models\Finance\EStudentContract;
use App\Models\Structure\EUniversity;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Student Document Service
 *
 * BUSINESS LOGIC LAYER
 * ======================
 *
 * Modular Monolith Architecture - Student Module
 * Contains all business logic for student documents
 *
 * Controller → Service → Repository → Model
 *
 * @package App\Services\Student
 */
class DocumentService
{
    /**
     * Get student decrees
     *
     * @param EStudent $student
     * @return array
     */
    public function getDecrees(EStudent $student): array
    {
        $decrees = EDecreeStudent::where('_student', $student->id)
            ->with(['decree.decreeType'])
            ->whereHas('decree', function ($q) {
                $q->where('active', true);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return $decrees->map(function ($decreeStudent) {
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
        })->toArray();
    }

    /**
     * Get student certificates
     *
     * @param EStudent $student
     * @return array
     */
    public function getCertificates(EStudent $student): array
    {
        $certificates = EStudentCertificate::where('_student', $student->id)
            ->where('active', true)
            ->orderBy('issue_date', 'desc')
            ->get();

        return $certificates->map(function ($cert) {
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
        })->toArray();
    }

    /**
     * Get student references
     *
     * @param EStudent $student
     * @return array
     */
    public function getReferences(EStudent $student): array
    {
        $references = EStudentReference::where('_student', $student->id)
            ->where('active', true)
            ->with(['semester'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $references->map(function ($ref) {
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
        })->toArray();
    }

    /**
     * Generate new reference
     *
     * @param EStudent $student
     * @return array
     * @throws \Exception
     */
    public function generateReference(EStudent $student): array
    {
        // Check if student can generate reference
        if (!$student->meta || !in_array($student->meta->_student_status, [11, 12, 13])) {
            throw new \Exception('Sizning statusingizda ma\'lumotnoma olish imkoni yo\'q');
        }

        // Check if reference already exists for current semester
        $existingReference = EStudentReference::where('_student', $student->id)
            ->where('_semester', $student->_semestr)
            ->where('active', true)
            ->first();

        if ($existingReference) {
            throw new \Exception('Shu semestr uchun ma\'lumotnoma allaqachon yaratilgan');
        }

        // Generate new reference number
        $lastReference = EStudentReference::orderBy('id', 'desc')->first();
        $newNumber = $lastReference ? ($lastReference->id + 1) : 1;
        $referenceNumber = sprintf('REF-%04d-%s', $newNumber, date('Y'));

        // Create new reference
        $reference = EStudentReference::create([
            '_student' => $student->id,
            '_semester' => $student->_semestr,
            'number' => $referenceNumber,
            'status' => 'active',
            'active' => true,
        ]);

        return [
            'id' => $reference->id,
            'number' => $reference->number,
            'download_url' => url("api/v1/student/reference-download/{$reference->id}"),
        ];
    }

    /**
     * Get student academic documents (diploma, transcript, etc)
     *
     * @param EStudent $student
     * @return array
     */
    public function getAcademicDocuments(EStudent $student): array
    {
        $documents = [];

        // Diplomas
        $diplomas = EStudentDiploma::where('_student', $student->id)
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

        // Academic Information
        $academicInfo = EAcademicInformation::where('_student', $student->id)
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

        // Transcripts
        $records = EAcademicRecord::where('_student', $student->id)
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

        return $documents;
    }

    /**
     * Get all student documents (combined)
     *
     * @param EStudent $student
     * @return array
     */
    public function getAllDocuments(EStudent $student): array
    {
        $allDocuments = [];

        // Decrees
        $decrees = $this->getDecrees($student);
        foreach ($decrees as $decree) {
            $allDocuments[] = [
                'id' => $decree['id'],
                'name' => $decree['name'] ?? 'Buyruq',
                'type' => 'decree',
                'file' => $decree['file_url'],
                'attributes' => [
                    ['label' => 'Raqam', 'value' => $decree['number']],
                    ['label' => 'Sana', 'value' => $decree['date']],
                ],
            ];
        }

        // Academic documents
        $documents = $this->getAcademicDocuments($student);
        $allDocuments = array_merge($allDocuments, $documents);

        // References
        $references = $this->getReferences($student);
        foreach ($references as $ref) {
            $allDocuments[] = [
                'id' => $ref['id'],
                'name' => 'Ma\'lumotnoma',
                'type' => 'reference',
                'file' => $ref['download_url'],
                'attributes' => [
                    ['label' => 'Raqam', 'value' => $ref['number']],
                    ['label' => 'Sana', 'value' => $ref['issue_date']],
                ],
            ];
        }

        // Contracts
        $contracts = $this->getContractList($student);
        foreach ($contracts['items'] as $contract) {
            $allDocuments[] = [
                'id' => $contract['id'],
                'name' => 'Kontrakt',
                'type' => 'contract',
                'file' => url("api/v1/student/contract-download/{$contract['id']}"),
                'attributes' => [
                    ['label' => 'Raqam', 'value' => $contract['number'] ?? 'N/A'],
                    ['label' => 'Sana', 'value' => $contract['contract_date'] ?? 'N/A'],
                    ['label' => 'Summa', 'value' => number_format($contract['amount'] ?? 0, 0, '.', ' ') . ' so\'m'],
                ],
            ];
        }

        return $allDocuments;
    }

    /**
     * Get student contracts list
     *
     * @param EStudent $student
     * @return array
     */
    public function getContractList(EStudent $student): array
    {
        $contracts = EStudentContract::where('_student', $student->id)
            ->where('active', true)
            ->with(['educationYear'])
            ->orderBy('contract_date', 'desc')
            ->get();

        return [
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
            })->toArray(),
            'attributes' => [
                'number' => 'Kontrakt raqami',
                'contract_date' => 'Tuzilgan sana',
                'amount' => 'Kontrakt summasi',
                'status' => 'Holati',
            ],
        ];
    }

    /**
     * Get current year contract
     *
     * @param EStudent $student
     * @return array
     * @throws \Exception
     */
    public function getCurrentContract(EStudent $student): array
    {
        $currentYear = date('Y');
        $contract = EStudentContract::where('_student', $student->id)
            ->whereHas('educationYear', function ($q) use ($currentYear) {
                $q->where('code', $currentYear);
            })
            ->where('active', true)
            ->first();

        if (!$contract) {
            throw new \Exception('Joriy o\'quv yili uchun kontrakt topilmadi');
        }

        return [
            'id' => $contract->id,
            'number' => $contract->number,
            'contract_date' => $contract->contract_date,
            'amount' => $contract->amount,
            'paid_amount' => $contract->paid_amount ?? 0,
            'remaining_amount' => ($contract->amount ?? 0) - ($contract->paid_amount ?? 0),
            'status' => $contract->status,
            'download_url' => url("api/v1/student/contract-download/{$contract->id}"),
        ];
    }

    /**
     * Download document file
     *
     * @param EStudent $student
     * @param int $id
     * @param string $type
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Exception
     */
    public function downloadDocument(EStudent $student, int $id, string $type)
    {
        switch ($type) {
            case 'diploma':
                $document = EStudentDiploma::where('_student', $student->id)
                    ->where('id', $id)
                    ->firstOrFail();
                $filePath = $document->file_path;
                $fileName = "Diplom_{$student->student_id_number}.pdf";
                break;

            case 'academic_info':
                $document = EAcademicInformation::where('_student', $student->id)
                    ->where('id', $id)
                    ->firstOrFail();
                $filePath = $document->file_path;
                $fileName = "Academic_Info_{$student->student_id_number}.pdf";
                break;

            case 'transcript':
                $document = EAcademicRecord::where('_student', $student->id)
                    ->where('id', $id)
                    ->firstOrFail();
                $filePath = $document->file_path;
                $fileName = "Transcript_{$student->student_id_number}.pdf";
                break;

            default:
                throw new \Exception('Noto\'g\'ri document turi');
        }

        if (!$filePath || !Storage::exists($filePath)) {
            throw new \Exception('Fayl topilmadi');
        }

        return Storage::download($filePath, $fileName);
    }

    /**
     * Download decree file
     *
     * @param EStudent $student
     * @param int $decreeId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Exception
     */
    public function downloadDecree(EStudent $student, int $decreeId)
    {
        $decreeStudent = EDecreeStudent::where('_student', $student->id)
            ->where('_decree', $decreeId)
            ->with('decree')
            ->firstOrFail();

        $decree = $decreeStudent->decree;

        if (!$decree->file || !Storage::exists($decree->file)) {
            throw new \Exception('Fayl topilmadi');
        }

        return Storage::download($decree->file, "{$decree->number}.pdf");
    }

    /**
     * Download contract file
     *
     * @param EStudent $student
     * @param int $contractId
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Exception
     */
    public function downloadContract(EStudent $student, int $contractId)
    {
        $contract = EStudentContract::where('_student', $student->id)
            ->where('id', $contractId)
            ->firstOrFail();

        if (!$contract->file_path || !Storage::exists($contract->file_path)) {
            throw new \Exception('Fayl topilmadi');
        }

        return Storage::download($contract->file_path, "Kontrakt_{$contract->number}.pdf");
    }

    /**
     * Download reference PDF
     *
     * @param EStudent $student
     * @param int $referenceId
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function downloadReference(EStudent $student, int $referenceId)
    {
        $reference = EStudentReference::where('_student', $student->id)
            ->where('id', $referenceId)
            ->with(['student.meta', 'student.group', 'student.specialty', 'semester'])
            ->firstOrFail();

        // Generate PDF using DomPDF
        $pdf = Pdf::loadView('exports.reference', [
            'reference' => $reference,
            'student' => $reference->student,
            'university' => EUniversity::first(),
        ]);

        return $pdf->download("Malumotnoma_{$reference->number}.pdf");
    }
}
