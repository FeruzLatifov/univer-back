<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\EStudent;
use Symfony\Component\HttpFoundation\Response;

class CheckStudentStatus
{
    /**
     * Handle an incoming request.
     *
     * Student statuslarini tekshirish:
     * - 11: O'qimoqda (Active)
     * - 12: Akademik ta'tilga chiqqan (Academic leave)
     * - 13: O'qishdan chetlashtirilgan (Expelled)
     * - 14: Tamomlagan (Graduated)
     * - 15: O'qishni to'xtatgan (Dropped out)
     */
    public function handle(Request $request, Closure $next, ...$allowedStatuses): Response
    {
        $user = $request->user();

        // If user is admin, allow all operations
        if ($user instanceof \App\Models\EAdmin) {
            return $next($request);
        }

        // If user is student, check their status
        if ($user instanceof EStudent) {
            $meta = $user->meta;

            if (!$meta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Talaba meta ma\'lumotlari topilmadi',
                    'error' => 'Student meta not found',
                ], 404);
            }

            $studentStatus = $meta->_student_status;

            // If no specific statuses are required, allow by default
            if (empty($allowedStatuses)) {
                return $next($request);
            }

            // Check if student's status is allowed
            if (!in_array($studentStatus, $allowedStatuses)) {
                $statusMessages = [
                    '11' => 'O\'qimoqda',
                    '12' => 'Akademik ta\'tilga chiqqan',
                    '13' => 'O\'qishdan chetlashtirilgan',
                    '14' => 'Tamomlagan',
                    '15' => 'O\'qishni to\'xtatgan',
                ];

                $currentStatusName = $statusMessages[$studentStatus] ?? 'Noma\'lum status';

                return response()->json([
                    'success' => false,
                    'message' => "Bu amalni bajarish uchun sizning statusingiz mos emas. Hozirgi status: {$currentStatusName}",
                    'error' => 'Invalid student status',
                    'current_status' => $studentStatus,
                    'current_status_name' => $currentStatusName,
                    'allowed_statuses' => $allowedStatuses,
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Get status code constants
     */
    public const STATUS_STUDYING = '11';
    public const STATUS_ACADEMIC_LEAVE = '12';
    public const STATUS_EXPELLED = '13';
    public const STATUS_GRADUATED = '14';
    public const STATUS_DROPPED_OUT = '15';
}
