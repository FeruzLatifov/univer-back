<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ECurriculumSubject;

class SubjectController extends Controller
{
    /**
     * Get student subjects
     *
     * @OA\Get(
     *     path="/api/v1/student/subjects",
     *     tags={"Student - Subjects"},
     *     summary="Get student subjects",
     *     description="Returns a list of all subjects enrolled by the student for the current semester, including teacher and credit information",
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
     *                     @OA\Property(property="name", type="string", example="Mathematics"),
     *                     @OA\Property(property="code", type="string", example="MATH101"),
     *                     @OA\Property(property="credit", type="integer", example=3),
     *                     @OA\Property(property="total_acload", type="integer", example=45, description="Total academic load hours"),
     *                     @OA\Property(
     *                         property="teacher",
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=10),
     *                         @OA\Property(property="name", type="string", example="Dr. John Smith")
     *                     )
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

        $subjects = ECurriculumSubject::where('curriculum_id', $student->_curriculum)
            ->where('semester', $student->_semestr ?? 1)
            ->with(['subject', 'employee'])
            ->get()
            ->map(function ($cs) {
                return [
                    'id' => $cs->_subject,
                    'name' => $cs->subject->name ?? 'N/A',
                    'code' => $cs->subject->code ?? '',
                    'credit' => $cs->credit,
                    'total_acload' => $cs->total_acload,
                    'teacher' => [
                        'id' => $cs->_employee,
                        'name' => $cs->employee->full_name ?? 'N/A',
                    ],
                ];
            });

        return response()->json(['success' => true, 'data' => $subjects]);
    }

    /**
     * Get subject details
     *
     * @OA\Get(
     *     path="/api/v1/student/subjects/{id}",
     *     tags={"Student - Subjects"},
     *     summary="Get detailed subject information",
     *     description="Returns detailed information about a specific subject including resources, topics, and teacher details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Subject ID",
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
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Mathematics"),
     *                 @OA\Property(property="code", type="string", example="MATH101"),
     *                 @OA\Property(property="credit", type="integer", example=3),
     *                 @OA\Property(property="teacher", type="string", example="Dr. John Smith"),
     *                 @OA\Property(
     *                     property="resources",
     *                     type="array",
     *                     description="Study resources and materials",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Calculus Textbook"),
     *                         @OA\Property(property="type", type="string", example="pdf"),
     *                         @OA\Property(property="url", type="string", example="/storage/resources/calculus.pdf")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="topics",
     *                     type="array",
     *                     description="Subject topics/chapters",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="Differential Calculus"),
     *                         @OA\Property(property="order", type="integer", example=1)
     *                     )
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
     *         response=404,
     *         description="Subject not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Subject not found")
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
    public function show(Request $request, $id)
    {
        $student = $request->user();

        $subject = ECurriculumSubject::where('curriculum_id', $student->_curriculum)
            ->where('_subject', $id)
            ->with(['subject', 'employee', 'subject.resources', 'subject.topics'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $subject->_subject,
                'name' => $subject->subject->name ?? 'N/A',
                'code' => $subject->subject->code ?? '',
                'credit' => $subject->credit,
                'teacher' => $subject->employee->full_name ?? 'N/A',
                'resources' => $subject->subject->resources ?? [],
                'topics' => $subject->subject->topics ?? [],
            ],
        ]);
    }
}
