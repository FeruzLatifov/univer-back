<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\EExam;
use App\Models\EExamStudent;
use App\Models\ESubjectSchedule;
use App\Models\EStudent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Exam Controller
 *
 * Manages exams and exam results
 */
class ExamController extends Controller
{
    use ApiResponse;

    /**
     * Get all exams for teacher
     *
     * GET /api/v1/teacher/exams
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $status = $request->input('status'); // scheduled, completed, etc.

        $query = EExam::where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with(['subject', 'group']);

        if ($status) {
            $query->where('status', $status);
        }

        $exams = $query->orderBy('exam_date', 'desc')->get();

        $examList = $exams->map(function ($exam) {
            $resultsEntered = $exam->results()->whereNotNull('score')->count();
            $totalStudents = $exam->results()->count();

            return [
                'id' => $exam->id,
                'subject' => [
                    'id' => $exam->subject->id,
                    'name' => $exam->subject->name,
                    'code' => $exam->subject->code,
                ],
                'group' => [
                    'id' => $exam->group->id,
                    'name' => $exam->group->name,
                ],
                'exam_type' => $exam->_exam_type,
                'exam_type_name' => $exam->type_name,
                'exam_date' => $exam->exam_date->format('Y-m-d H:i'),
                'duration' => $exam->duration,
                'max_score' => $exam->max_score,
                'status' => $exam->status,
                'results_entered' => $resultsEntered,
                'total_students' => $totalStudents,
                'progress' => $totalStudents > 0 ? round(($resultsEntered / $totalStudents) * 100) : 0,
            ];
        });

        return $this->successResponse($examList, 'Imtihonlar ro\'yxati');
    }

    /**
     * Create new exam
     *
     * POST /api/v1/teacher/exam
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'group_id' => 'required|exists:e_group,id',
            'semester' => 'required|integer',
            'exam_type' => 'required|in:11,12', // midterm, final
            'exam_date' => 'required|date|after:now',
            'duration' => 'nullable|integer|min:30',
            'max_score' => 'nullable|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Verify teacher teaches this subject to this group
        $teachesSubject = ESubjectSchedule::where('_subject', $request->subject_id)
            ->where('_group', $request->group_id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu guruhga imtihon belgilash huquqi yo\'q');
        }

        try {
            DB::beginTransaction();

            // Create exam
            $exam = EExam::create([
                '_subject' => $request->subject_id,
                '_group' => $request->group_id,
                '_semester' => $request->semester,
                '_exam_type' => $request->exam_type,
                'exam_date' => $request->exam_date,
                '_employee' => $teacher->employee->id,
                'duration' => $request->duration ?? 90,
                'max_score' => $request->max_score ?? 100,
                'status' => EExam::STATUS_SCHEDULED,
                'notes' => $request->notes,
                'active' => true,
            ]);

            // Create exam records for all students in group
            $students = EStudent::where('_group', $request->group_id)
                ->where('active', true)
                ->get();

            foreach ($students as $student) {
                EExamStudent::create([
                    '_exam' => $exam->id,
                    '_student' => $student->id,
                    'max_score' => $exam->max_score,
                    'attended' => true,
                    'active' => true,
                ]);
            }

            DB::commit();

            return $this->createdResponse([
                'id' => $exam->id,
                'exam_date' => $exam->exam_date->format('Y-m-d H:i'),
                'total_students' => $students->count(),
            ], 'Imtihon belgilandi');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Get exam details with student list
     *
     * GET /api/v1/teacher/exam/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $exam = EExam::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->with(['subject', 'group', 'results.student'])
            ->firstOrFail();

        $students = $exam->results->map(function ($result) {
            return [
                'id' => $result->id,
                'student_id' => $result->student->student_id_number,
                'full_name' => $result->student->full_name,
                'photo' => $result->student->image,
                'attended' => $result->attended,
                'score' => $result->score,
                'max_score' => $result->max_score,
                'percentage' => $result->score ? $result->percentage : null,
                'grade' => $result->grade,
                'letter_grade' => $result->letter_grade,
                'passed' => $result->passed,
                'comment' => $result->comment,
            ];
        });

        return $this->successResponse([
            'id' => $exam->id,
            'subject' => $exam->subject->name,
            'group' => $exam->group->name,
            'exam_type_name' => $exam->type_name,
            'exam_date' => $exam->exam_date->format('Y-m-d H:i'),
            'duration' => $exam->duration,
            'max_score' => $exam->max_score,
            'status' => $exam->status,
            'students' => $students,
        ], 'Imtihon ma\'lumotlari');
    }

    /**
     * Enter exam results
     *
     * POST /api/v1/teacher/exam/{id}/results
     *
     * @param Request $request
     * @param int $id Exam ID
     * @return JsonResponse
     */
    public function enterResults(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $exam = EExam::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'results' => 'required|array',
            'results.*.student_id' => 'required|exists:e_student,id',
            'results.*.score' => 'nullable|numeric|min:0',
            'results.*.attended' => 'required|boolean',
            'results.*.comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            $updated = 0;

            foreach ($request->results as $resultData) {
                $examStudent = EExamStudent::where('_exam', $id)
                    ->where('_student', $resultData['student_id'])
                    ->first();

                if (!$examStudent) continue;

                $score = $resultData['score'] ?? null;
                $attended = $resultData['attended'];

                // Calculate grades if score provided
                if ($score !== null && $attended) {
                    $examStudent->score = $score;
                    $examStudent->max_score = $exam->max_score;
                    $examStudent->letter_grade = $examStudent->calculateLetterGrade();
                    $examStudent->grade = $examStudent->calculateNumericGrade();
                    $examStudent->passed = $examStudent->grade !== '2';
                    $examStudent->graded_at = now();
                }

                $examStudent->attended = $attended;
                $examStudent->comment = $resultData['comment'] ?? null;
                $examStudent->save();

                $updated++;
            }

            // Update exam status if all results entered
            $totalResults = EExamStudent::where('_exam', $id)->count();
            $enteredResults = EExamStudent::where('_exam', $id)->whereNotNull('score')->count();

            if ($enteredResults >= $totalResults) {
                $exam->update(['status' => EExam::STATUS_COMPLETED]);
            }

            DB::commit();

            return $this->successResponse([
                'updated' => $updated,
                'total' => $totalResults,
                'exam_status' => $exam->status,
            ], 'Natijalar saqlandi');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Get exam statistics
     *
     * GET /api/v1/teacher/exam/{id}/statistics
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function statistics(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $exam = EExam::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->with('results')
            ->firstOrFail();

        $results = $exam->results()->whereNotNull('score')->get();

        if ($results->isEmpty()) {
            return $this->successResponse([
                'message' => 'Hali natijalar kiritilmagan',
            ]);
        }

        $scores = $results->pluck('percentage');

        $statistics = [
            'total_students' => $exam->results()->count(),
            'attended' => $exam->results()->where('attended', true)->count(),
            'absent' => $exam->results()->where('attended', false)->count(),
            'graded' => $results->count(),
            'passed' => $results->where('passed', true)->count(),
            'failed' => $results->where('passed', false)->count(),
            'average_score' => round($scores->avg(), 2),
            'highest_score' => $scores->max(),
            'lowest_score' => $scores->min(),
            'grade_distribution' => [
                '5' => $results->where('grade', '5')->count(),
                '4' => $results->where('grade', '4')->count(),
                '3' => $results->where('grade', '3')->count(),
                '2' => $results->where('grade', '2')->count(),
            ],
            'letter_distribution' => [
                'A' => $results->where('letter_grade', 'A')->count(),
                'B' => $results->where('letter_grade', 'B')->count(),
                'C' => $results->where('letter_grade', 'C')->count(),
                'D' => $results->where('letter_grade', 'D')->count(),
                'E' => $results->where('letter_grade', 'E')->count(),
                'F' => $results->where('letter_grade', 'F')->count(),
            ],
        ];

        return $this->successResponse($statistics, 'Imtihon statistikasi');
    }
}
