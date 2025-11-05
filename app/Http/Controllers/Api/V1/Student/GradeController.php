<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EGrade;

class GradeController extends Controller
{
    /**
     * Get student grades
     *
     * @OA\Get(
     *     path="/api/v1/student/grades",
     *     tags={"Student - Grades"},
     *     summary="Get student grades with statistics",
     *     description="Returns student's grades for the current semester including midterm, final, total scores, and overall statistics (GPA, average)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="grades",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="subject", type="string", example="Mathematics"),
     *                         @OA\Property(property="midterm", type="number", nullable=true, example=38.5),
     *                         @OA\Property(property="final", type="number", nullable=true, example=47.0),
     *                         @OA\Property(property="total", type="number", example=85.5),
     *                         @OA\Property(property="grade", type="string", example="A-"),
     *                         @OA\Property(property="credit", type="integer", example=3)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="statistics",
     *                     type="object",
     *                     @OA\Property(property="total_subjects", type="integer", example=8),
     *                     @OA\Property(property="average", type="number", example=82.5),
     *                     @OA\Property(property="gpa", type="number", example=4.13, description="GPA (Grade Point Average)")
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
