<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EGrade;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();

        $grades = EGrade::where('_student', $student->id)
            ->where('_semester', $student->_semestr ?? 1)
            ->with(['subject'])
            ->get()
            ->map(function ($grade) {
                return [
                    'id' => $grade->id,
                    'subject' => $grade->subject->name ?? 'N/A',
                    'midterm' => $grade->midterm_point,
                    'final' => $grade->final_point,
                    'total' => $grade->total_point,
                    'grade' => $this->getGradeLetter($grade->total_point),
                    'credit' => $grade->credit,
                ];
            });

        $gpa = $grades->avg('total') / 20;

        return response()->json([
            'success' => true,
            'data' => [
                'grades' => $grades,
                'statistics' => [
                    'total_subjects' => $grades->count(),
                    'average' => round($grades->avg('total'), 1),
                    'gpa' => round($gpa, 2),
                ],
            ],
        ]);
    }

    private function getGradeLetter($points)
    {
        if ($points >= 90) return 'A';
        if ($points >= 85) return 'A-';
        if ($points >= 80) return 'B+';
        if ($points >= 75) return 'B';
        if ($points >= 70) return 'B-';
        if ($points >= 65) return 'C+';
        if ($points >= 60) return 'C';
        if ($points >= 55) return 'C-';
        return 'F';
    }
}
