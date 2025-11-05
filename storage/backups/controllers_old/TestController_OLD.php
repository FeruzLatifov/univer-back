<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ESubjectTest;
use App\Models\ESubjectTestQuestion;
use App\Models\ESubjectTestAnswer;
use App\Models\EStudentTestAttempt;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Teacher Test Controller
 *
 * O'qituvchilar uchun test yaratish, tahrirlash, import/export
 *
 * @package App\Http\Controllers\Api\V1\Teacher
 */
class TestController extends Controller
{
    use ApiResponse;

    /**
     * Get all tests for teacher's subjects
     *
     * GET /api/v1/teacher/tests
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search');
        $subjectId = $request->input('subject_id');
        $status = $request->input('status'); // active, draft, archived

        $query = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.active', true)
            ->with(['subject', 'questions'])
            ->distinct();

        // Search by name
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('e_subject_test.name', 'ilike', "%{$search}%")
                  ->orWhere('e_subject_test.description', 'ilike', "%{$search}%");
            });
        }

        // Filter by subject
        if ($subjectId) {
            $query->where('e_subject_test._subject', $subjectId);
        }

        // Filter by status
        if ($status === 'draft') {
            $query->where('e_subject_test.status', 10); // draft
        } elseif ($status === 'active') {
            $query->where('e_subject_test.status', 20); // published
        }

        $tests = $query->orderBy('e_subject_test.updated_at', 'desc')
            ->paginate($perPage);

        // Add statistics
        $tests->getCollection()->transform(function ($test) {
            $test->questions_count = $test->questions->count();
            $test->attempts_count = EStudentTestAttempt::where('_subject_test', $test->id)->count();
            $test->avg_score = EStudentTestAttempt::where('_subject_test', $test->id)
                ->where('finished', true)
                ->avg('correct_answers');

            return $test;
        });

        return $this->successResponse($tests, 'Testlar ro\'yxati');
    }

    /**
     * Get test details with questions
     *
     * GET /api/v1/teacher/tests/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->with(['subject', 'questions.answers'])
            ->firstOrFail();

        // Statistics
        $test->statistics = [
            'total_questions' => $test->questions->count(),
            'total_attempts' => EStudentTestAttempt::where('_subject_test', $test->id)->count(),
            'finished_attempts' => EStudentTestAttempt::where('_subject_test', $test->id)->where('finished', true)->count(),
            'avg_score' => EStudentTestAttempt::where('_subject_test', $test->id)->where('finished', true)->avg('correct_answers'),
            'avg_time' => EStudentTestAttempt::where('_subject_test', $test->id)->where('finished', true)->avg('duration'),
        ];

        return $this->successResponse($test, 'Test ma\'lumotlari');
    }

    /**
     * Create new test
     *
     * POST /api/v1/teacher/tests
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:e_subject,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'required|integer|min:1|max:180', // minutes
            'max_attempts' => 'required|integer|min:1|max:10',
            'passing_score' => 'required|integer|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results' => 'boolean',
            'status' => 'required|in:10,20', // 10: draft, 20: published
        ]);

        $teacher = $request->user();

        // Verify teacher has access to this subject
        $hasAccess = DB::table('e_subject_schedule')
            ->where('_subject', $validated['subject_id'])
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$hasAccess) {
            return $this->errorResponse('Sizda bu fanga kirish huquqi yo\'q', 403);
        }

        $test = ESubjectTest::create([
            '_subject' => $validated['subject_id'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'duration' => $validated['duration'],
            'max_attempts' => $validated['max_attempts'],
            'passing_score' => $validated['passing_score'],
            'shuffle_questions' => $validated['shuffle_questions'] ?? false,
            'shuffle_answers' => $validated['shuffle_answers'] ?? false,
            'show_results' => $validated['show_results'] ?? true,
            'status' => $validated['status'],
            'active' => true,
            '_employee' => $teacher->employee->id,
        ]);

        return $this->successResponse($test, 'Test yaratildi', 201);
    }

    /**
     * Update test
     *
     * PUT /api/v1/teacher/tests/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'duration' => 'sometimes|required|integer|min:1|max:180',
            'max_attempts' => 'sometimes|required|integer|min:1|max:10',
            'passing_score' => 'sometimes|required|integer|min:0|max:100',
            'shuffle_questions' => 'boolean',
            'shuffle_answers' => 'boolean',
            'show_results' => 'boolean',
            'status' => 'sometimes|required|in:10,20',
        ]);

        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->firstOrFail();

        $test->update($validated);

        return $this->successResponse($test, 'Test yangilandi');
    }

    /**
     * Delete test
     *
     * DELETE /api/v1/teacher/tests/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->firstOrFail();

        // Soft delete
        $test->update(['active' => false]);

        return $this->successResponse(null, 'Test o\'chirildi');
    }

    /**
     * Publish test (make it available to students)
     *
     * POST /api/v1/teacher/tests/{id}/publish
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->with('questions')
            ->firstOrFail();

        // Validate test has questions
        if ($test->questions->count() < 1) {
            return $this->errorResponse('Testda savollar yo\'q. Kamida 1 ta savol qo\'shing.', 400);
        }

        $test->update(['status' => 20]); // published

        return $this->successResponse($test, 'Test nashr etildi');
    }

    /**
     * Unpublish test (make it draft)
     *
     * POST /api/v1/teacher/tests/{id}/unpublish
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function unpublish(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->firstOrFail();

        $test->update(['status' => 10]); // draft

        return $this->successResponse($test, 'Test qoralama holatiga o\'tkazildi');
    }

    /**
     * Get test questions
     *
     * GET /api/v1/teacher/tests/{id}/questions
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function questions(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->firstOrFail();

        $questions = ESubjectTestQuestion::where('_subject_test', $test->id)
            ->where('active', true)
            ->with('answers')
            ->orderBy('order_number')
            ->get();

        return $this->successResponse($questions, 'Test savollari');
    }

    /**
     * Add question to test
     *
     * POST /api/v1/teacher/tests/{id}/questions
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function storeQuestion(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'question_text' => 'required|string',
            'question_type' => 'required|in:10,20,30', // 10: single choice, 20: multiple choice, 30: text
            'points' => 'required|integer|min:1|max:100',
            'answers' => 'required|array|min:2',
            'answers.*.answer_text' => 'required|string',
            'answers.*.is_correct' => 'required|boolean',
        ]);

        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->firstOrFail();

        // Validate at least one correct answer
        $correctCount = collect($validated['answers'])->where('is_correct', true)->count();
        if ($correctCount < 1) {
            return $this->errorResponse('Kamida 1 ta to\'g\'ri javob bo\'lishi kerak', 400);
        }

        DB::beginTransaction();
        try {
            // Get next order number
            $maxOrder = ESubjectTestQuestion::where('_subject_test', $test->id)->max('order_number') ?? 0;

            $question = ESubjectTestQuestion::create([
                '_subject_test' => $test->id,
                'question_text' => $validated['question_text'],
                'question_type' => $validated['question_type'],
                'points' => $validated['points'],
                'order_number' => $maxOrder + 1,
                'active' => true,
            ]);

            // Create answers
            foreach ($validated['answers'] as $index => $answer) {
                ESubjectTestAnswer::create([
                    '_subject_test_question' => $question->id,
                    'answer_text' => $answer['answer_text'],
                    'is_correct' => $answer['is_correct'],
                    'order_number' => $index + 1,
                    'active' => true,
                ]);
            }

            DB::commit();

            $question->load('answers');
            return $this->successResponse($question, 'Savol qo\'shildi', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Savol qo\'shishda xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update question
     *
     * PUT /api/v1/teacher/tests/{testId}/questions/{questionId}
     *
     * @param Request $request
     * @param int $testId
     * @param int $questionId
     * @return JsonResponse
     */
    public function updateQuestion(Request $request, int $testId, int $questionId): JsonResponse
    {
        $validated = $request->validate([
            'question_text' => 'sometimes|required|string',
            'question_type' => 'sometimes|required|in:10,20,30',
            'points' => 'sometimes|required|integer|min:1|max:100',
            'answers' => 'sometimes|required|array|min:2',
            'answers.*.id' => 'nullable|integer',
            'answers.*.answer_text' => 'required|string',
            'answers.*.is_correct' => 'required|boolean',
        ]);

        $teacher = $request->user();

        // Verify access
        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $testId)
            ->firstOrFail();

        $question = ESubjectTestQuestion::where('_subject_test', $test->id)
            ->where('id', $questionId)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            // Update question
            $question->update([
                'question_text' => $validated['question_text'] ?? $question->question_text,
                'question_type' => $validated['question_type'] ?? $question->question_type,
                'points' => $validated['points'] ?? $question->points,
            ]);

            // Update answers if provided
            if (isset($validated['answers'])) {
                // Delete old answers
                ESubjectTestAnswer::where('_subject_test_question', $question->id)->delete();

                // Create new answers
                foreach ($validated['answers'] as $index => $answer) {
                    ESubjectTestAnswer::create([
                        '_subject_test_question' => $question->id,
                        'answer_text' => $answer['answer_text'],
                        'is_correct' => $answer['is_correct'],
                        'order_number' => $index + 1,
                        'active' => true,
                    ]);
                }
            }

            DB::commit();

            $question->load('answers');
            return $this->successResponse($question, 'Savol yangilandi');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Savol yangilashda xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete question
     *
     * DELETE /api/v1/teacher/tests/{testId}/questions/{questionId}
     *
     * @param Request $request
     * @param int $testId
     * @param int $questionId
     * @return JsonResponse
     */
    public function destroyQuestion(Request $request, int $testId, int $questionId): JsonResponse
    {
        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $testId)
            ->firstOrFail();

        $question = ESubjectTestQuestion::where('_subject_test', $test->id)
            ->where('id', $questionId)
            ->firstOrFail();

        // Soft delete
        $question->update(['active' => false]);

        return $this->successResponse(null, 'Savol o\'chirildi');
    }

    /**
     * Reorder questions
     *
     * POST /api/v1/teacher/tests/{id}/questions/reorder
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function reorderQuestions(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'questions' => 'required|array',
            'questions.*.id' => 'required|integer|exists:e_subject_test_question,id',
            'questions.*.order' => 'required|integer|min:1',
        ]);

        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->firstOrFail();

        DB::beginTransaction();
        try {
            foreach ($validated['questions'] as $item) {
                ESubjectTestQuestion::where('_subject_test', $test->id)
                    ->where('id', $item['id'])
                    ->update(['order_number' => $item['order']]);
            }

            DB::commit();

            return $this->successResponse(null, 'Savollar tartibi o\'zgartirildi');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Xatolik: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get test results/attempts
     *
     * GET /api/v1/teacher/tests/{id}/results
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function results(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();
        $perPage = $request->input('per_page', 20);

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->firstOrFail();

        $results = EStudentTestAttempt::where('_subject_test', $test->id)
            ->with(['student'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->successResponse($results, 'Test natijalari');
    }

    /**
     * Get test statistics
     *
     * GET /api/v1/teacher/tests/{id}/statistics
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function statistics(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->with('questions')
            ->firstOrFail();

        $attempts = EStudentTestAttempt::where('_subject_test', $test->id);

        $statistics = [
            'total_questions' => $test->questions->count(),
            'total_points' => $test->questions->sum('points'),
            'total_attempts' => $attempts->count(),
            'finished_attempts' => $attempts->where('finished', true)->count(),
            'in_progress_attempts' => $attempts->where('finished', false)->count(),
            'avg_score' => $attempts->where('finished', true)->avg('correct_answers'),
            'max_score' => $attempts->where('finished', true)->max('correct_answers'),
            'min_score' => $attempts->where('finished', true)->min('correct_answers'),
            'avg_duration' => $attempts->where('finished', true)->avg('duration'), // seconds
            'pass_rate' => $attempts->where('finished', true)->count() > 0
                ? ($attempts->where('finished', true)->where('correct_answers', '>=', $test->passing_score)->count() / $attempts->where('finished', true)->count()) * 100
                : 0,
        ];

        // Score distribution
        $statistics['score_distribution'] = $attempts->where('finished', true)
            ->get()
            ->groupBy(function ($item) {
                if ($item->correct_answers >= 90) return '90-100';
                if ($item->correct_answers >= 80) return '80-89';
                if ($item->correct_answers >= 70) return '70-79';
                if ($item->correct_answers >= 60) return '60-69';
                return '0-59';
            })
            ->map->count();

        return $this->successResponse($statistics, 'Test statistikasi');
    }

    /**
     * Import questions from Excel
     *
     * POST /api/v1/teacher/tests/{id}/import
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function import(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:5120', // 5MB
        ]);

        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->firstOrFail();

        try {
            $file = $request->file('file');
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();

            $rows = $worksheet->toArray();

            // Skip header row
            array_shift($rows);

            $imported = 0;
            $errors = [];

            DB::beginTransaction();

            $maxOrder = ESubjectTestQuestion::where('_subject_test', $test->id)->max('order_number') ?? 0;

            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 because we skipped header and array is 0-indexed

                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                // Expected format: Question | Type | Points | Answer1 | Answer2 | Answer3 | Answer4 | Correct (1,2,3,4)
                $questionText = $row[0] ?? null;
                $questionType = $row[1] ?? '10'; // default single choice
                $points = $row[2] ?? 1;
                $answer1 = $row[3] ?? null;
                $answer2 = $row[4] ?? null;
                $answer3 = $row[5] ?? null;
                $answer4 = $row[6] ?? null;
                $correctAnswers = $row[7] ?? '1';

                // Validate
                if (empty($questionText) || empty($answer1) || empty($answer2)) {
                    $errors[] = "Qator {$rowNumber}: Savol va kamida 2 ta javob bo'lishi kerak";
                    continue;
                }

                // Create question
                $question = ESubjectTestQuestion::create([
                    '_subject_test' => $test->id,
                    'question_text' => $questionText,
                    'question_type' => $questionType,
                    'points' => $points,
                    'order_number' => ++$maxOrder,
                    'active' => true,
                ]);

                // Create answers
                $answers = array_filter([$answer1, $answer2, $answer3, $answer4]);
                $correctIndexes = array_map('trim', explode(',', $correctAnswers));

                foreach ($answers as $i => $answerText) {
                    ESubjectTestAnswer::create([
                        '_subject_test_question' => $question->id,
                        'answer_text' => $answerText,
                        'is_correct' => in_array((string)($i + 1), $correctIndexes),
                        'order_number' => $i + 1,
                        'active' => true,
                    ]);
                }

                $imported++;
            }

            DB::commit();

            $message = "{$imported} ta savol import qilindi";
            if (!empty($errors)) {
                $message .= ". Xatolar: " . implode("; ", $errors);
            }

            return $this->successResponse([
                'imported' => $imported,
                'errors' => $errors,
            ], $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Import xatoligi: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export test questions to Excel
     *
     * GET /api/v1/teacher/tests/{id}/export
     *
     * @param Request $request
     * @param int $id
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request, int $id)
    {
        $teacher = $request->user();

        $test = ESubjectTest::query()
            ->select('e_subject_test.*')
            ->join('e_subject_schedule', 'e_subject_test._subject', '=', 'e_subject_schedule._subject')
            ->where('e_subject_schedule._employee', $teacher->employee->id)
            ->where('e_subject_test.id', $id)
            ->with(['questions.answers'])
            ->firstOrFail();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Savol');
        $sheet->setCellValue('B1', 'Turi (10=Bitta, 20=Ko\'p)');
        $sheet->setCellValue('C1', 'Ball');
        $sheet->setCellValue('D1', 'Javob 1');
        $sheet->setCellValue('E1', 'Javob 2');
        $sheet->setCellValue('F1', 'Javob 3');
        $sheet->setCellValue('G1', 'Javob 4');
        $sheet->setCellValue('H1', 'To\'g\'ri javoblar (1,2,3,4)');

        // Data
        $row = 2;
        foreach ($test->questions as $question) {
            $sheet->setCellValue('A' . $row, $question->question_text);
            $sheet->setCellValue('B' . $row, $question->question_type);
            $sheet->setCellValue('C' . $row, $question->points);

            $answers = $question->answers->sortBy('order_number')->values();
            $correctIndexes = [];

            foreach ($answers as $index => $answer) {
                $col = chr(68 + $index); // D, E, F, G
                $sheet->setCellValue($col . $row, $answer->answer_text);

                if ($answer->is_correct) {
                    $correctIndexes[] = $index + 1;
                }
            }

            $sheet->setCellValue('H' . $row, implode(',', $correctIndexes));
            $row++;
        }

        // Auto size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'test-' . $test->id . '-' . time() . '.xlsx';
        $tempFile = storage_path('app/temp/' . $filename);

        // Ensure temp directory exists
        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Download import template
     *
     * GET /api/v1/teacher/tests/import-template
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function importTemplate()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Savol');
        $sheet->setCellValue('B1', 'Turi (10=Bitta, 20=Ko\'p)');
        $sheet->setCellValue('C1', 'Ball');
        $sheet->setCellValue('D1', 'Javob 1');
        $sheet->setCellValue('E1', 'Javob 2');
        $sheet->setCellValue('F1', 'Javob 3');
        $sheet->setCellValue('G1', 'Javob 4');
        $sheet->setCellValue('H1', 'To\'g\'ri javoblar (1,2,3,4)');

        // Example row
        $sheet->setCellValue('A2', 'O\'zbekiston poytaxti qayer?');
        $sheet->setCellValue('B2', '10');
        $sheet->setCellValue('C2', '1');
        $sheet->setCellValue('D2', 'Toshkent');
        $sheet->setCellValue('E2', 'Samarqand');
        $sheet->setCellValue('F2', 'Buxoro');
        $sheet->setCellValue('G2', 'Xiva');
        $sheet->setCellValue('H2', '1');

        // Auto size
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'test-import-template.xlsx';
        $tempFile = storage_path('app/temp/' . $filename);

        if (!file_exists(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer->save($tempFile);

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
