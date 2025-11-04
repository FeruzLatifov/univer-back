<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Models\EAdmin;
use App\Models\EDocument;
use App\Models\EDocumentSigner;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    /**
     * GET /api/v1/employee/documents/sign
     * List documents for current employee to sign (or already signed)
     * Filters: status (pending|signed), q (title), type (document_type), date_from, date_to
     */
    public function index(Request $request)
    {
        /** @var EAdmin $user */
        $user = auth('admin-api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $status = $request->string('status')->toString();
        $q = $request->string('q')->toString();
        $type = $request->string('type')->toString();
        $from = $request->date('date_from');
        $to = $request->date('date_to');

        $employeeId = optional($user->employee)->id;

        $query = EDocumentSigner::query()
            ->select(['e_document_signer.*'])
            ->join('e_document', 'e_document.id', '=', 'e_document_signer._document')
            ->join('e_employee_meta', 'e_employee_meta.id', '=', 'e_document_signer._employee_meta')
            ->when($status, fn($q2) => $q2->where('e_document_signer.status', $status))
            ->when($type, fn($q2) => $q2->where('e_document.document_type', $type))
            ->when($q, fn($q2) => $q2->where('e_document.document_title', 'ILIKE', "%$q%"))
            ->when($from, fn($q2) => $q2->where('e_document.created_at', '>=', $from))
            ->when($to, fn($q2) => $q2->where('e_document.created_at', '<=', $to))
            ->when($employeeId, fn($q2) => $q2->where('e_employee_meta._employee', $employeeId)->where('e_employee_meta.active', true))
            ->orderBy('e_document_signer.priority');

        // Only documents assigned to this employee (by passport_pin match or role-based linking)
        // If your schema has direct employee id, filter here accordingly. For now, filter by admin role id if available.
        // Adjust once exact schema available.

        $perPage = (int) $request->input('per_page', 20);
        $paginator = $query->paginate($perPage);

        $items = [];
        foreach ($paginator->items() as $signer) {
            /** @var EDocumentSigner $signer */
            $doc = $signer->document()->first();
            if (!$doc) continue;
            $items[] = [
                'id' => $doc->id,
                'hash' => $doc->hash,
                'title' => $doc->document_title,
                'type' => $doc->document_type,
                'created_at' => optional($doc->created_at)->toDateTimeString(),
                'status' => $signer->status,
                'can_sign' => $signer->status === EDocumentSigner::STATUS_PENDING,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    /**
     * GET /api/v1/employee/documents/{hash}/view
     * Returns URL to view/print the document.
     * As Yii2 used render of printable link, here we expose a legacy-compatible URL which frontend can open.
     */
    public function view(Request $request, string $hash)
    {
        $doc = EDocument::query()->where('hash', $hash)->first();
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        // Legacy-compatible absolute URL (configurable base)
        $legacyBase = rtrim((string) env('LEGACY_YII2_BASE_URL', ''), '/');
        $url = $legacyBase
            ? $legacyBase . '/document/sign-documents?document=' . urlencode($doc->hash) . '&view=1'
            : url('/document/sign-documents?document=' . urlencode($doc->hash) . '&view=1');

        return response()->json([
            'success' => true,
            'data' => [
                'view_url' => $url,
                'title' => $doc->document_title,
            ],
        ]);
    }

    /**
     * POST /api/v1/employee/documents/{hash}/sign
     * Prepares sign request and returns redirect URL for external provider.
     * For now, use legacy-compatible path that triggers provider redirect flow.
     */
    public function sign(Request $request, string $hash)
    {
        $doc = EDocument::query()->where('hash', $hash)->first();
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        $legacyBase = rtrim((string) env('LEGACY_YII2_BASE_URL', ''), '/');
        $redirectUrl = $legacyBase
            ? $legacyBase . '/document/sign-documents?document=' . urlencode($doc->hash) . '&sign-document=1'
            : url('/document/sign-documents?document=' . urlencode($doc->hash) . '&sign-document=1');
        return response()->json([
            'success' => true,
            'data' => [
                'redirect_url' => $redirectUrl,
            ],
        ]);
    }

    /**
     * POST /api/v1/employee/documents/{hash}/status
     * Returns latest sign status for this document.
     */
    public function status(Request $request, string $hash)
    {
        $doc = EDocument::query()->where('hash', $hash)->first();
        if (!$doc) {
            return response()->json(['success' => false, 'message' => 'Document not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $doc->status,
                'updated_at' => optional($doc->updated_at)->toDateTimeString(),
            ],
        ]);
    }
}


