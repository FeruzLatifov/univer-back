<?php

namespace App\Services\Employee;

use App\Models\EDocument;
use App\Models\EDocumentSigner;
use App\Models\EAdmin;
use App\Models\EEmployee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Document Service
 *
 * BUSINESS LOGIC LAYER
 * ====================
 *
 * Handles e-document signing logic for employees
 *
 * Controller → Service → Repository → Model
 */
class DocumentService
{
    /**
     * Get documents for employee to sign
     *
     * @param EAdmin $user Current authenticated user
     * @param array $filters Filter parameters
     * @return LengthAwarePaginator
     */
    public function getDocumentsToSign(EAdmin $user, array $filters = []): LengthAwarePaginator
    {
        // Get employee from admin user
        $employee = $user->employee;

        if (!$employee) {
            return new LengthAwarePaginator([], 0, 15);
        }

        // Build query
        $query = EDocumentSigner::query()
            ->with([
                'document',
                'employeeMeta.department',
                'employeeMeta.employee',
            ])
            ->join('e_employee_meta', 'e_document_signer._employee_meta', '=', 'e_employee_meta.id')
            ->where('e_employee_meta._employee', $employee->id);

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('document', function($q2) use ($search) {
                    $q2->where('document_title', 'ilike', "%{$search}%");
                })
                ->orWhere('employee_name', 'ilike', "%{$search}%")
                ->orWhere('employee_position', 'ilike', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('e_document_signer.status', $filters['status']);
        }

        if (!empty($filters['type'])) {
            $query->where('e_document_signer.type', $filters['type']);
        }

        if (!empty($filters['document_type'])) {
            $query->whereHas('document', function($q) use ($filters) {
                $q->where('document_type', $filters['document_type']);
            });
        }

        if (!empty($filters['date_from'])) {
            $query->where('e_document_signer.created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }

        if (!empty($filters['date_to'])) {
            $query->where('e_document_signer.created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        // Paginate
        $perPage = $filters['per_page'] ?? 15;
        return $query
            ->orderBy('e_document_signer.created_at', 'desc')
            ->select('e_document_signer.*')
            ->paginate($perPage);
    }

    /**
     * Get document by hash
     *
     * @param string $hash Document hash
     * @param EAdmin $user Current user
     * @return EDocument|null
     */
    public function getDocumentByHash(string $hash, EAdmin $user): ?EDocument
    {
        return EDocument::with([
            'signers.employeeMeta.department',
            'signers.employeeMeta.employee',
            'admin',
        ])
        ->where('hash', $hash)
        ->first();
    }

    /**
     * Sign document (local provider)
     *
     * @param string $hash Document hash
     * @param EAdmin $user Current user
     * @return array
     * @throws \Exception
     */
    public function signDocument(string $hash, EAdmin $user): array
    {
        $employee = $user->employee;

        if (!$employee) {
            throw new \Exception('Xodim topilmadi');
        }

        // Find document
        $document = EDocument::where('hash', $hash)->first();

        if (!$document) {
            throw new \Exception('Hujjat topilmadi');
        }

        // Check if local provider
        if ($document->provider !== EDocument::PROVIDER_LOCAL) {
            throw new \Exception('Ushbu hujjat faqat ' . $document->getProviderLabel() . ' orqali imzolanishi mumkin');
        }

        // Find signer record for this employee
        $signer = EDocumentSigner::where('_document', $document->id)
            ->whereHas('employeeMeta', function($q) use ($employee) {
                $q->where('_employee', $employee->id);
            })
            ->where('status', EDocumentSigner::STATUS_PENDING)
            ->first();

        if (!$signer) {
            throw new \Exception('Siz ushbu hujjatni imzolash huquqiga ega emassiz yoki allaqachon imzolagansiz');
        }

        // Update signer status
        DB::transaction(function() use ($signer, $document, $employee) {
            $signer->update([
                'status' => EDocumentSigner::STATUS_SIGNED,
                'signed_at' => now(),
                '_sign_data' => [
                    'signedAt' => now()->format('d.m.Y H:i:s'),
                    'employeeName' => $employee->first_name . ' ' . $employee->second_name,
                    'provider' => 'local',
                ],
            ]);

            // Check if all signers have signed
            if ($document->isSignedByAll()) {
                $document->update([
                    'status' => EDocument::STATUS_SIGNED,
                    'updated_at' => now(),
                ]);
            }
        });

        return [
            'success' => true,
            'message' => 'Hujjat muvaffaqqiyatli imzolandi',
            'document' => $document->fresh(),
        ];
    }

    /**
     * Get sign status
     *
     * @param string $hash Document hash
     * @param EAdmin $user Current user
     * @return array
     */
    public function getSignStatus(string $hash, EAdmin $user): array
    {
        $document = $this->getDocumentByHash($hash, $user);

        if (!$document) {
            return [
                'status' => 'not_found',
                'message' => 'Hujjat topilmadi',
            ];
        }

        $employee = $user->employee;
        $canSign = false;
        $alreadySigned = false;

        if ($employee) {
            $signer = EDocumentSigner::where('_document', $document->id)
                ->whereHas('employeeMeta', function($q) use ($employee) {
                    $q->where('_employee', $employee->id);
                })
                ->first();

            if ($signer) {
                $alreadySigned = $signer->isSigned();
                $canSign = !$alreadySigned && $document->provider === EDocument::PROVIDER_LOCAL;
            }
        }

        return [
            'status' => $document->status,
            'can_sign' => $canSign,
            'already_signed' => $alreadySigned,
            'provider' => $document->provider,
            'signed_count' => $document->signedSigners()->count(),
            'total_signers' => $document->signers()->count(),
        ];
    }
}
