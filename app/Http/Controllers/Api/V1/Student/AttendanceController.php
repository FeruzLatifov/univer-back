<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EAttendance;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
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
