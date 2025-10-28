<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ESubjectTest;
use App\Models\ESubjectTestQuestion;
use App\Models\ESubjectTestAnswer;
use App\Models\EStudentTestAttempt;
use App\Models\EStudentTestAnswer as StudentAnswer;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    /**
     * Notification service instance
     *
     * @var NotificationService
     */
    protected $notificationService;

    /**
     * Create a new controller instance
     *
     * @param NotificationService $notificationService
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    // ==========================================
    // TEST CRUD OPERATIONS
    // ==========================================

    /**
     * List all tests
     * GET /api/v1/tests
     */
    public function index(Request $request)
    {
        try {
            $query = ESubjectTest::with(['subject', 'employee', 'group', 'topic']);

            // Apply filters
            if ($request->has('_subject')) {
                $query->where('_subject', $request->_subject);
            }

            if ($request->has('_employee')) {
                $query->where('_employee', $request->_employee);
            }

            if ($request->has('_group')) {
                $query->where('_group', $request->_group);
            }

            if ($request->has('is_published')) {
                $query->where('is_published', $request->boolean('is_published'));
            }

            if ($request->has('status')) {
                switch ($request->status) {
                    case 'available':
                        $query->available();
                        break;
                    case 'upcoming':
                        $query->upcoming();
                        break;
                    case 'expired':
                        $query->expired();
                        break;
                }
            }

            // Only active tests
            $query->active()->orderBy('position')->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 20);
            $tests = $query->paginate($perPage);

            // Add computed attributes
            $tests->getCollection()->transform(function ($test) {
                return [
                    'id' => $test->id,
                    '_subject' => $test->_subject,
                    '_employee' => $test->_employee,
                    '_group' => $test->_group,
                    '_subject_topic' => $test->_subject_topic,
                    'title' => $test->title,
                    'description' => $test->description,
                    'duration' => $test->duration,
                    'duration_formatted' => $test->duration_formatted,
                    'passing_score' => $test->passing_score,
                    'max_score' => $test->max_score,
                    'question_count' => $test->question_count,
                    'attempt_limit' => $test->attempt_limit,
                    'start_date' => $test->start_date,
                    'end_date' => $test->end_date,
                    'is_published' => $test->is_published,
                    'published_at' => $test->published_at,
                    'is_available' => $test->is_available,
                    'is_expired' => $test->is_expired,
                    'days_until_end' => $test->days_until_end,
                    'attempt_stats' => $test->attempt_stats,
                    'subject' => $test->subject,
                    'employee' => $test->employee,
                    'group' => $test->group,
                    'topic' => $test->topic,
                    'created_at' => $test->created_at,
                    'updated_at' => $test->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $tests,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tests',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new test
     * POST /api/v1/tests
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                '_subject' => 'required|exists:curriculum_subject,id',
                '_employee' => 'required|exists:e_employee,id',
                '_group' => 'nullable|exists:e_group,id',
                '_subject_topic' => 'nullable|exists:e_subject_topic,id',
                'title' => 'required|string|max:256',
                'description' => 'nullable|string',
                'instructions' => 'nullable|string',
                'duration' => 'nullable|integer|min:1',
                'passing_score' => 'nullable|numeric|min:0|max:100',
                'randomize_questions' => 'boolean',
                'randomize_answers' => 'boolean',
                'show_correct_answers' => 'boolean',
                'attempt_limit' => 'integer|min:1',
                'allow_review' => 'boolean',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'position' => 'integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $test = ESubjectTest::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Test created successfully',
                'data' => $test->load(['subject', 'employee', 'group', 'topic']),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create test',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get test details
     * GET /api/v1/tests/{id}
     */
    public function show($id)
    {
        try {
            $test = ESubjectTest::with(['subject', 'employee', 'group', 'topic', 'questions.answers'])
                ->findOrFail($id);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $test->id,
                    '_subject' => $test->_subject,
                    '_employee' => $test->_employee,
                    '_group' => $test->_group,
                    '_subject_topic' => $test->_subject_topic,
                    'title' => $test->title,
                    'description' => $test->description,
                    'instructions' => $test->instructions,
                    'duration' => $test->duration,
                    'duration_formatted' => $test->duration_formatted,
                    'passing_score' => $test->passing_score,
                    'max_score' => $test->max_score,
                    'question_count' => $test->question_count,
                    'randomize_questions' => $test->randomize_questions,
                    'randomize_answers' => $test->randomize_answers,
                    'show_correct_answers' => $test->show_correct_answers,
                    'attempt_limit' => $test->attempt_limit,
                    'allow_review' => $test->allow_review,
                    'start_date' => $test->start_date,
                    'end_date' => $test->end_date,
                    'is_published' => $test->is_published,
                    'published_at' => $test->published_at,
                    'is_available' => $test->is_available,
                    'is_expired' => $test->is_expired,
                    'days_until_end' => $test->days_until_end,
                    'position' => $test->position,
                    'attempt_stats' => $test->attempt_stats,
                    'average_score' => $test->average_score,
                    'subject' => $test->subject,
                    'employee' => $test->employee,
                    'group' => $test->group,
                    'topic' => $test->topic,
                    'questions' => $test->questions,
                    'created_at' => $test->created_at,
                    'updated_at' => $test->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Test not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update test
     * PUT /api/v1/tests/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $test = ESubjectTest::findOrFail($id);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                '_subject' => 'exists:curriculum_subject,id',
                '_employee' => 'exists:e_employee,id',
                '_group' => 'nullable|exists:e_group,id',
                '_subject_topic' => 'nullable|exists:e_subject_topic,id',
                'title' => 'string|max:256',
                'description' => 'nullable|string',
                'instructions' => 'nullable|string',
                'duration' => 'nullable|integer|min:1',
                'passing_score' => 'nullable|numeric|min:0|max:100',
                'randomize_questions' => 'boolean',
                'randomize_answers' => 'boolean',
                'show_correct_answers' => 'boolean',
                'attempt_limit' => 'integer|min:1',
                'allow_review' => 'boolean',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after:start_date',
                'position' => 'integer|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $test->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Test updated successfully',
                'data' => $test->load(['subject', 'employee', 'group', 'topic']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update test',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete test (soft delete via active flag)
     * DELETE /api/v1/tests/{id}
     */
    public function destroy($id)
    {
        try {
            $test = ESubjectTest::findOrFail($id);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            // Check if test has submitted attempts
            $hasSubmittedAttempts = $test->attempts()
                ->whereNotNull('submitted_at')
                ->exists();

            if ($hasSubmittedAttempts) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete test with submitted attempts',
                ], 400);
            }

            $test->active = false;
            $test->save();

            return response()->json([
                'success' => true,
                'message' => 'Test deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete test',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate test
     * POST /api/v1/tests/{id}/duplicate
     */
    public function duplicate($id)
    {
        try {
            $test = ESubjectTest::findOrFail($id);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            DB::beginTransaction();

            $newTest = $test->duplicate();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Test duplicated successfully',
                'data' => $newTest->load(['subject', 'employee', 'group', 'topic', 'questions.answers']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate test',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Publish test
     * POST /api/v1/tests/{id}/publish
     */
    public function publish($id)
    {
        try {
            $test = ESubjectTest::findOrFail($id);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            // Validation before publishing
            if ($test->questions()->active()->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot publish test without questions',
                ], 400);
            }

            $test->publish();

            // Send notification to students
            $this->notificationService->notifyTestPublished($test);

            return response()->json([
                'success' => true,
                'message' => 'Test published successfully',
                'data' => $test,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish test',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unpublish test
     * POST /api/v1/tests/{id}/unpublish
     */
    public function unpublish($id)
    {
        try {
            $test = ESubjectTest::findOrFail($id);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            $test->unpublish();

            return response()->json([
                'success' => true,
                'message' => 'Test unpublished successfully',
                'data' => $test,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to unpublish test',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==========================================
    // QUESTION MANAGEMENT
    // ==========================================

    /**
     * List questions for a test
     * GET /api/v1/tests/{testId}/questions
     */
    public function getQuestions($testId)
    {
        try {
            $test = ESubjectTest::findOrFail($testId);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            $questions = $test->questions()
                ->with('answers')
                ->active()
                ->orderBy('position')
                ->get();

            // Add statistics for each question
            $questions = $questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    '_test' => $question->_test,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'position' => $question->position,
                    'is_required' => $question->is_required,
                    'allow_multiple' => $question->allow_multiple,
                    'case_sensitive' => $question->case_sensitive,
                    'word_limit' => $question->word_limit,
                    'explanation' => $question->explanation,
                    'image_path' => $question->image_path,
                    'is_multiple_choice' => $question->is_multiple_choice,
                    'is_true_false' => $question->is_true_false,
                    'is_short_answer' => $question->is_short_answer,
                    'is_essay' => $question->is_essay,
                    'can_auto_grade' => $question->can_auto_grade,
                    'requires_manual_grading' => $question->requires_manual_grading,
                    'answers' => $question->answers,
                    'statistics' => $question->getStatistics(),
                    'created_at' => $question->created_at,
                    'updated_at' => $question->updated_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $questions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch questions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add question to test
     * POST /api/v1/tests/{testId}/questions
     */
    public function addQuestion(Request $request, $testId)
    {
        try {
            $test = ESubjectTest::findOrFail($testId);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'question_text' => 'required|string',
                'question_type' => 'required|in:multiple_choice,true_false,short_answer,essay',
                'points' => 'required|numeric|min:0',
                'position' => 'integer|min:0',
                'is_required' => 'boolean',
                'image_path' => 'nullable|string|max:512',
                'explanation' => 'nullable|string',

                // Multiple choice specific
                'allow_multiple' => 'boolean',
                'correct_answers' => 'required_if:question_type,multiple_choice|array',

                // True/False specific
                'correct_answer_boolean' => 'required_if:question_type,true_false|boolean',

                // Short answer specific
                'correct_answer_text' => 'required_if:question_type,short_answer|string',
                'case_sensitive' => 'boolean',

                // Essay specific
                'word_limit' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $data = $validator->validated();
            $data['_test'] = $testId;

            // Convert correct_answers array to JSON for multiple choice
            if (isset($data['correct_answers'])) {
                $data['correct_answers'] = json_encode($data['correct_answers']);
            }

            $question = ESubjectTestQuestion::create($data);

            // Update test's question count and max score
            $test->updateQuestionCount();
            $test->max_score = $test->calculateTotalScore();
            $test->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question added successfully',
                'data' => $question->load('answers'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get question details
     * GET /api/v1/tests/{testId}/questions/{id}
     */
    public function getQuestion($testId, $id)
    {
        try {
            $question = ESubjectTestQuestion::with('answers')
                ->where('_test', $testId)
                ->where('id', $id)
                ->where('active', true)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $question->id,
                    '_test' => $question->_test,
                    'question_text' => $question->question_text,
                    'question_type' => $question->question_type,
                    'points' => $question->points,
                    'position' => $question->position,
                    'is_required' => $question->is_required,
                    'correct_answers' => $question->correct_answers,
                    'correct_answers_array' => $question->correct_answers_array,
                    'allow_multiple' => $question->allow_multiple,
                    'correct_answer_text' => $question->correct_answer_text,
                    'case_sensitive' => $question->case_sensitive,
                    'correct_answer_boolean' => $question->correct_answer_boolean,
                    'word_limit' => $question->word_limit,
                    'explanation' => $question->explanation,
                    'image_path' => $question->image_path,
                    'is_multiple_choice' => $question->is_multiple_choice,
                    'is_true_false' => $question->is_true_false,
                    'is_short_answer' => $question->is_short_answer,
                    'is_essay' => $question->is_essay,
                    'can_auto_grade' => $question->can_auto_grade,
                    'requires_manual_grading' => $question->requires_manual_grading,
                    'answers' => $question->answers,
                    'statistics' => $question->getStatistics(),
                    'created_at' => $question->created_at,
                    'updated_at' => $question->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update question
     * PUT /api/v1/tests/{testId}/questions/{id}
     */
    public function updateQuestion(Request $request, $testId, $id)
    {
        try {
            $question = ESubjectTestQuestion::where('_test', $testId)
                ->where('id', $id)
                ->where('active', true)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'question_text' => 'string',
                'question_type' => 'in:multiple_choice,true_false,short_answer,essay',
                'points' => 'numeric|min:0',
                'position' => 'integer|min:0',
                'is_required' => 'boolean',
                'image_path' => 'nullable|string|max:512',
                'explanation' => 'nullable|string',
                'allow_multiple' => 'boolean',
                'correct_answers' => 'array',
                'correct_answer_boolean' => 'boolean',
                'correct_answer_text' => 'string',
                'case_sensitive' => 'boolean',
                'word_limit' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $data = $validator->validated();

            // Convert correct_answers array to JSON if present
            if (isset($data['correct_answers'])) {
                $data['correct_answers'] = json_encode($data['correct_answers']);
            }

            $question->update($data);

            // Update test's max score if points changed
            if (isset($data['points'])) {
                $test = $question->test;
                $test->max_score = $test->calculateTotalScore();
                $test->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question updated successfully',
                'data' => $question->load('answers'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete question (soft delete via active flag)
     * DELETE /api/v1/tests/{testId}/questions/{id}
     */
    public function deleteQuestion($testId, $id)
    {
        try {
            $question = ESubjectTestQuestion::where('_test', $testId)
                ->where('id', $id)
                ->where('active', true)
                ->firstOrFail();

            DB::beginTransaction();

            $question->active = false;
            $question->save();

            // Update test's question count and max score
            $test = $question->test;
            $test->updateQuestionCount();
            $test->max_score = $test->calculateTotalScore();
            $test->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder questions
     * POST /api/v1/tests/{testId}/questions/reorder
     */
    public function reorderQuestions(Request $request, $testId)
    {
        try {
            $test = ESubjectTest::findOrFail($testId);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'order' => 'required|array',
                'order.*' => 'required|exists:e_subject_test_question,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            foreach ($request->order as $position => $questionId) {
                ESubjectTestQuestion::where('id', $questionId)
                    ->where('_test', $testId)
                    ->update(['position' => $position]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Questions reordered successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder questions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplicate question
     * POST /api/v1/tests/{testId}/questions/{id}/duplicate
     */
    public function duplicateQuestion($testId, $id)
    {
        try {
            $question = ESubjectTestQuestion::where('_test', $testId)
                ->where('id', $id)
                ->where('active', true)
                ->firstOrFail();

            DB::beginTransaction();

            $newQuestion = $question->duplicate();

            // Update test's question count and max score
            $test = $question->test;
            $test->updateQuestionCount();
            $test->max_score = $test->calculateTotalScore();
            $test->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Question duplicated successfully',
                'data' => $newQuestion->load('answers'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate question',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==========================================
    // ANSWER OPTIONS (Multiple Choice)
    // ==========================================

    /**
     * Add answer option to question
     * POST /api/v1/tests/{testId}/questions/{questionId}/answers
     */
    public function addAnswer(Request $request, $testId, $questionId)
    {
        try {
            $question = ESubjectTestQuestion::where('_test', $testId)
                ->where('id', $questionId)
                ->where('active', true)
                ->firstOrFail();

            if ($question->question_type !== ESubjectTestQuestion::TYPE_MULTIPLE_CHOICE) {
                return response()->json([
                    'success' => false,
                    'message' => 'Answer options can only be added to multiple choice questions',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'answer_text' => 'required|string',
                'image_path' => 'nullable|string|max:512',
                'position' => 'integer|min:0',
                'is_correct' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $data = $validator->validated();
            $data['_question'] = $questionId;

            $answer = ESubjectTestAnswer::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Answer option added successfully',
                'data' => $answer,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add answer option',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update answer option
     * PUT /api/v1/tests/{testId}/questions/{questionId}/answers/{id}
     */
    public function updateAnswer(Request $request, $testId, $questionId, $id)
    {
        try {
            $answer = ESubjectTestAnswer::where('_question', $questionId)
                ->where('id', $id)
                ->where('active', true)
                ->firstOrFail();

            // Verify question belongs to test
            $question = ESubjectTestQuestion::where('_test', $testId)
                ->where('id', $questionId)
                ->where('active', true)
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'answer_text' => 'string',
                'image_path' => 'nullable|string|max:512',
                'position' => 'integer|min:0',
                'is_correct' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $answer->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Answer option updated successfully',
                'data' => $answer,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update answer option',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete answer option (soft delete via active flag)
     * DELETE /api/v1/tests/{testId}/questions/{questionId}/answers/{id}
     */
    public function deleteAnswer($testId, $questionId, $id)
    {
        try {
            $answer = ESubjectTestAnswer::where('_question', $questionId)
                ->where('id', $id)
                ->where('active', true)
                ->firstOrFail();

            // Verify question belongs to test
            $question = ESubjectTestQuestion::where('_test', $testId)
                ->where('id', $questionId)
                ->where('active', true)
                ->firstOrFail();

            $answer->active = false;
            $answer->save();

            return response()->json([
                'success' => true,
                'message' => 'Answer option deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete answer option',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // ==========================================
    // RESULTS & GRADING
    // ==========================================

    /**
     * Get test results
     * GET /api/v1/tests/{testId}/results
     */
    public function getResults(Request $request, $testId)
    {
        try {
            $test = ESubjectTest::findOrFail($testId);

            if (!$test->active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Test not found',
                ], 404);
            }

            $query = EStudentTestAttempt::with(['student', 'test'])
                ->where('_test', $testId)
                ->where('active', true)
                ->whereNotNull('submitted_at');

            // Apply filters
            if ($request->has('_student')) {
                $query->where('_student', $request->_student);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('passed')) {
                $query->where('passed', $request->boolean('passed'));
            }

            $attempts = $query->orderBy('submitted_at', 'desc')->get();

            // Transform attempts data
            $attempts = $attempts->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    '_test' => $attempt->_test,
                    '_student' => $attempt->_student,
                    'attempt_number' => $attempt->attempt_number,
                    'status' => $attempt->status,
                    'started_at' => $attempt->started_at,
                    'submitted_at' => $attempt->submitted_at,
                    'graded_at' => $attempt->graded_at,
                    'duration_seconds' => $attempt->duration_seconds,
                    'duration_formatted' => $attempt->duration_formatted,
                    'total_score' => $attempt->total_score,
                    'max_score' => $attempt->max_score,
                    'percentage' => $attempt->percentage,
                    'letter_grade' => $attempt->letter_grade,
                    'numeric_grade' => $attempt->numeric_grade,
                    'passed' => $attempt->passed,
                    'auto_graded_score' => $attempt->auto_graded_score,
                    'manual_graded_score' => $attempt->manual_graded_score,
                    'requires_manual_grading' => $attempt->requiresManualGrading(),
                    'student' => $attempt->student,
                    'test' => [
                        'id' => $attempt->test->id,
                        'title' => $attempt->test->title,
                        'passing_score' => $attempt->test->passing_score,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $attempts,
                'summary' => [
                    'total_attempts' => $attempts->count(),
                    'graded' => $attempts->where('status', 'graded')->count(),
                    'pending_grading' => $attempts->where('status', 'submitted')->count(),
                    'average_score' => round($attempts->avg('percentage'), 2),
                    'pass_rate' => $test->passing_score
                        ? round(($attempts->where('passed', true)->count() / max($attempts->count(), 1)) * 100, 2)
                        : null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch results',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get specific attempt details
     * GET /api/v1/tests/{testId}/attempts/{attemptId}
     */
    public function getAttempt($testId, $attemptId)
    {
        try {
            $attempt = EStudentTestAttempt::with(['student', 'test', 'answers.question', 'answers.answer'])
                ->where('_test', $testId)
                ->where('id', $attemptId)
                ->where('active', true)
                ->firstOrFail();

            // Transform answers data
            $answers = $attempt->answers->map(function ($answer) {
                return [
                    'id' => $answer->id,
                    '_question' => $answer->_question,
                    'question' => [
                        'id' => $answer->question->id,
                        'question_text' => $answer->question->question_text,
                        'question_type' => $answer->question->question_type,
                        'points' => $answer->question->points,
                        'explanation' => $answer->question->explanation,
                    ],
                    'answer_text' => $answer->answer_text,
                    'answer_boolean' => $answer->answer_boolean,
                    'selected_answers_array' => $answer->selected_answers_array,
                    'display_value' => $answer->getDisplayValue(),
                    'points_earned' => $answer->points_earned,
                    'points_possible' => $answer->points_possible,
                    'percentage' => $answer->percentage,
                    'is_correct' => $answer->is_correct,
                    'manually_graded' => $answer->manually_graded,
                    'graded_by' => $answer->graded_by,
                    'graded_at' => $answer->graded_at,
                    'feedback' => $answer->feedback,
                    'answered_at' => $answer->answered_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $attempt->id,
                    '_test' => $attempt->_test,
                    '_student' => $attempt->_student,
                    'attempt_number' => $attempt->attempt_number,
                    'status' => $attempt->status,
                    'started_at' => $attempt->started_at,
                    'submitted_at' => $attempt->submitted_at,
                    'graded_at' => $attempt->graded_at,
                    'duration_seconds' => $attempt->duration_seconds,
                    'duration_formatted' => $attempt->duration_formatted,
                    'total_score' => $attempt->total_score,
                    'max_score' => $attempt->max_score,
                    'percentage' => $attempt->percentage,
                    'letter_grade' => $attempt->letter_grade,
                    'numeric_grade' => $attempt->numeric_grade,
                    'passed' => $attempt->passed,
                    'auto_graded_score' => $attempt->auto_graded_score,
                    'manual_graded_score' => $attempt->manual_graded_score,
                    'feedback' => $attempt->feedback,
                    'requires_manual_grading' => $attempt->requiresManualGrading(),
                    'student' => $attempt->student,
                    'test' => $attempt->test,
                    'answers' => $answers,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attempt not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Manual grading for attempt
     * POST /api/v1/tests/{testId}/attempts/{attemptId}/grade
     */
    public function gradeAttempt(Request $request, $testId, $attemptId)
    {
        try {
            $attempt = EStudentTestAttempt::with('answers')
                ->where('_test', $testId)
                ->where('id', $attemptId)
                ->where('active', true)
                ->firstOrFail();

            if ($attempt->status !== EStudentTestAttempt::STATUS_SUBMITTED &&
                $attempt->status !== EStudentTestAttempt::STATUS_GRADED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only submitted attempts can be graded',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'graded_by' => 'required|exists:e_employee,id',
                'answers' => 'required|array',
                'answers.*.answer_id' => 'required|exists:e_student_test_answer,id',
                'answers.*.points_earned' => 'required|numeric|min:0',
                'answers.*.feedback' => 'nullable|string',
                'overall_feedback' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Grade individual answers
            foreach ($request->answers as $answerData) {
                $answer = StudentAnswer::findOrFail($answerData['answer_id']);

                // Verify answer belongs to this attempt
                if ($answer->_attempt !== $attempt->id) {
                    continue;
                }

                $answer->manualGrade(
                    $answerData['points_earned'],
                    $request->graded_by,
                    $answerData['feedback'] ?? null
                );
            }

            // Recalculate total score
            $attempt->calculateScore();

            // Update attempt status
            $attempt->status = EStudentTestAttempt::STATUS_GRADED;
            $attempt->graded_at = now();

            if ($request->has('overall_feedback')) {
                $attempt->feedback = $request->overall_feedback;
            }

            $attempt->save();

            // Send notification to student about graded test
            $this->notificationService->notifyTestGraded($attempt);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attempt graded successfully',
                'data' => $attempt->load(['answers', 'student', 'test']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to grade attempt',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
