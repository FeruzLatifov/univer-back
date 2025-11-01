<?php

namespace App\Http\Controllers\Api\V1\Compatibility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Backward Compatibility Layer for Yii2 API Format
 *
 * Bu controller eski univer-yii2 API formatidagi so'rovlarni
 * yangi Laravel formatiga o'zgartiradi va tegishli controller'larga yo'naltiradi.
 *
 * Maqsad: Tashqi integratsiyalarni buzmaslik
 *
 * Eski format: GET /v1/education/subject?subject=123&semester=12
 * Yangi format: GET /api/student/subjects/123
 *
 * @package App\Http\Controllers\Api\V1\Compatibility
 */
class EducationController extends Controller
{
    /**
     * Convert Yii2 subject format to Laravel RESTful format
     *
     * Eski: GET /v1/education/subject?subject=123&semester=12
     * Yangi: GET /api/student/subjects/123
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function subject(Request $request)
    {
        $subjectId = $request->get('subject');

        if (!$subjectId) {
            return response()->json([
                'success' => false,
                'message' => 'Subject ID is required',
            ], 400);
        }

        // Yangi controller'ga forward qilish
        $controller = app(\App\Http\Controllers\Api\V1\Student\SubjectController::class);
        return $controller->show($subjectId);
    }

    /**
     * Convert Yii2 task submit format to Laravel RESTful format
     *
     * Eski: POST /v1/education/task-submit?task=456
     * Yangi: POST /api/student/assignments/456/submit
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskSubmit(Request $request)
    {
        $taskId = $request->get('task');

        if (!$taskId) {
            return response()->json([
                'success' => false,
                'message' => 'Task ID is required',
            ], 400);
        }

        $controller = app(\App\Http\Controllers\Api\V1\Student\AssignmentController::class);
        return $controller->submit($request, $taskId);
    }

    /**
     * Get semesters list
     *
     * GET /v1/education/semesters
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function semesters(Request $request)
    {
        $student = $request->user();

        // Get semesters from curriculum
        $semesters = \App\Models\Curriculum\ECurriculumSemester::where('_curriculum', $student->_curriculum)
            ->where('active', true)
            ->with(['educationYear'])
            ->orderBy('code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $semesters->map(function ($semester) {
                return [
                    'id' => $semester->id,
                    'code' => $semester->code,
                    'name' => $semester->name,
                    'education_year' => $semester->educationYear ? [
                        'id' => $semester->educationYear->id,
                        'code' => $semester->educationYear->code,
                        'name' => $semester->educationYear->name,
                    ] : null,
                ];
            }),
        ]);
    }

    /**
     * Get grade types list
     *
     * GET /v1/education/grade-type-list
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function gradeTypes(Request $request)
    {
        $gradeTypes = \App\Models\System\HGradeType::where('active', true)
            ->orderBy('code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $gradeTypes->map(function ($type) {
                return [
                    'code' => $type->code,
                    'name' => $type->name,
                    'min_border' => $type->min_border,
                    'max_border' => $type->max_border,
                    'grade_point' => $type->grade_point,
                ];
            }),
        ]);
    }

    /**
     * Get subject resources (elektron resurslar)
     *
     * GET /v1/education/resources?subject=123&semester=12
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resources(Request $request)
    {
        $student = $request->user();
        $subjectId = $request->get('subject');
        $semesterId = $request->get('semester');

        $query = \App\Models\Curriculum\ESubjectResource::query()
            ->whereHas('subject.curriculumSubjects', function ($q) use ($student, $semesterId) {
                $q->where('_curriculum', $student->_curriculum);
                if ($semesterId) {
                    $q->where('_semester', $semesterId);
                }
            });

        if ($subjectId) {
            $query->where('_subject', $subjectId);
        }

        $resources = $query->with(['subject'])
            ->where('active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $resources->map(function ($resource) {
                return [
                    'id' => $resource->id,
                    'name' => $resource->name,
                    'type' => $resource->resource_type,
                    'subject' => $resource->subject ? [
                        'id' => $resource->subject->id,
                        'code' => $resource->subject->code,
                        'name' => $resource->subject->name,
                    ] : null,
                    'file_path' => $resource->file_path,
                    'file_size' => $resource->file_size,
                    'description' => $resource->description,
                    'download_url' => $resource->file_path ? url("api/resources/{$resource->id}/download") : null,
                ];
            }),
        ]);
    }

    /**
     * Get exams
     *
     * GET /v1/education/exams?semester=12
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function exams(Request $request)
    {
        $student = $request->user();
        $semesterId = $request->get('semester');

        $exams = \App\Models\Curriculum\EExam::whereHas('examGroup.curriculumSubject', function ($q) use ($student, $semesterId) {
                $q->where('_curriculum', $student->_curriculum);
                if ($semesterId) {
                    $q->where('_semester', $semesterId);
                }
            })
            ->with(['examGroup.curriculumSubject.subject', 'examType'])
            ->where('active', true)
            ->orderBy('exam_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $exams->map(function ($exam) use ($student) {
                $studentExam = \App\Models\Curriculum\EStudentExam::where('_student', $student->id)
                    ->where('_exam', $exam->id)
                    ->first();

                return [
                    'id' => $exam->id,
                    'subject' => $exam->examGroup->curriculumSubject->subject->name ?? null,
                    'exam_type' => $exam->examType->name ?? null,
                    'exam_date' => $exam->exam_date,
                    'start_time' => $exam->start_time,
                    'duration' => $exam->duration,
                    'max_ball' => $exam->max_ball,
                    'student_ball' => $studentExam->ball ?? null,
                    'status' => $studentExam ? 'taken' : 'scheduled',
                ];
            }),
        ]);
    }

    /**
     * Get student GPA (IPR)
     *
     * GET /v1/education/gpa?semester=12
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function gpa(Request $request)
    {
        $student = $request->user();
        $semesterId = $request->get('semester', $student->_semestr);

        // Get all grades for the semester
        $grades = \App\Models\Academic\EGrade::where('_student', $student->id)
            ->where('_semester', $semesterId)
            ->with(['subject'])
            ->get();

        $totalCredits = 0;
        $totalPoints = 0;
        $subjects = [];

        foreach ($grades as $grade) {
            $credit = $grade->subject->credit ?? 0;
            $gradePoint = $this->calculateGradePoint($grade->total);

            $totalCredits += $credit;
            $totalPoints += ($gradePoint * $credit);

            $subjects[] = [
                'subject' => $grade->subject->name ?? null,
                'credit' => $credit,
                'total' => $grade->total,
                'grade' => $grade->grade,
                'grade_point' => $gradePoint,
            ];
        }

        $gpa = $totalCredits > 0 ? round($totalPoints / $totalCredits, 2) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'semester' => $semesterId,
                'total_credits' => $totalCredits,
                'gpa' => $gpa,
                'subjects' => $subjects,
            ],
        ]);
    }

    /**
     * Calculate grade point from total score
     *
     * @param float $total
     * @return float
     */
    private function calculateGradePoint($total)
    {
        if ($total >= 86) return 4.0;
        if ($total >= 71) return 3.0;
        if ($total >= 55) return 2.0;
        return 0.0;
    }

    /**
     * Get task (assignment) detail
     *
     * Eski: GET /v1/education/task?task=456
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function task(Request $request)
    {
        $taskId = $request->get('task');

        if (!$taskId) {
            return response()->json([
                'success' => false,
                'message' => 'Task ID is required',
            ], 400);
        }

        $student = $request->user();

        $assignment = \App\Models\Teacher\EAssignment::with([
            'subject',
            'submissions' => function ($q) use ($student) {
                $q->where('_student', $student->id);
            }
        ])->find($taskId);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
            ], 404);
        }

        $submission = $assignment->submissions->first();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'subject' => [
                    'id' => $assignment->subject->id,
                    'name' => $assignment->subject->name,
                ],
                'deadline' => $assignment->deadline,
                'max_ball' => $assignment->max_ball,
                'status' => $submission ? 'submitted' : 'pending',
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'content' => $submission->content,
                    'submitted_at' => $submission->submitted_at,
                    'grade' => $submission->grade,
                    'feedback' => $submission->feedback,
                ] : null,
            ],
        ]);
    }
}
