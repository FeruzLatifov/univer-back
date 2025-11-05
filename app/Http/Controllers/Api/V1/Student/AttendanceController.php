<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EAttendance;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Get student attendance records
     *
     * @OA\Get(
     *     path="/api/v1/student/attendance",
     *     tags={"Student - Attendance"},
     *     summary="Get student attendance records with statistics",
     *     description="Returns paginated attendance records for the authenticated student along with attendance statistics (present, absent, late, excused) and attendance rate",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number for pagination",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="attendances",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer", example=1),
     *                     @OA\Property(property="per_page", type="integer", example=20),
     *                     @OA\Property(property="total", type="integer", example=150),
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(
     *                             @OA\Property(property="id", type="integer", example=1),
     *                             @OA\Property(property="_student", type="integer", example=123),
     *                             @OA\Property(property="_date", type="string", format="date", example="2025-11-05"),
     *                             @OA\Property(property="_attendance_type", type="string", enum={"present", "absent", "late", "excused"}, example="present"),
     *                             @OA\Property(
     *                                 property="subject",
     *                                 type="object",
     *                                 @OA\Property(property="id", type="integer", example=5),
     *                                 @OA\Property(property="name", type="string", example="Mathematics")
     *                             )
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="statistics",
     *                     type="object",
     *                     @OA\Property(property="present", type="integer", example=120),
     *                     @OA\Property(property="absent", type="integer", example=10),
     *                     @OA\Property(property="late", type="integer", example=15),
     *                     @OA\Property(property="excused", type="integer", example=5),
     *                     @OA\Property(property="total", type="integer", example=150),
     *                     @OA\Property(property="rate", type="number", format="float", example=90.0, description="Attendance rate percentage")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $student = $request->user();

        $attendances = EAttendance::where('_student', $student->id)
            ->with(['subject'])
            ->orderBy('_date', 'desc')
            ->paginate(20);

        $stats = EAttendance::where('_student', $student->id)
            ->select('_attendance_type', DB::raw('count(*) as count'))
            ->groupBy('_attendance_type')
            ->get()
            ->pluck('count', '_attendance_type');

        $total = $stats->sum();
        $rate = $total > 0 ? round((($stats['present'] ?? 0) + ($stats['late'] ?? 0)) / $total * 100, 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'attendances' => $attendances,
                'statistics' => [
                    'present' => $stats['present'] ?? 0,
                    'absent' => $stats['absent'] ?? 0,
                    'late' => $stats['late'] ?? 0,
                    'excused' => $stats['excused'] ?? 0,
                    'total' => $total,
                    'rate' => $rate,
                ],
            ],
        ]);
    }
}
