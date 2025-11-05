<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Services\Teacher\TestService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Test Controller (REFACTORED)
 *
 * Thin controller - delegates to TestService
 */
class TestController extends Controller
{
    use ApiResponse;

    protected TestService $testService;

    public function __construct(TestService $testService)
    {
        $this->testService = $testService;
    }

    /**
     * Get all tests
     *
     * @OA\Get(
     *     path="/api/v1/teacher/tests",
     *     tags={"Teacher - Tests"},
     *     summary="Get all tests for authenticated teacher",
     *     description="Returns a list of all tests created by the teacher. Can be filtered by subject, group, or status.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="subject_id",
     *         in="query",
     *         description="Filter by subject ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="group_id",
     *         in="query",
     *         description="Filter by academic group ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=5)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by test status",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"draft", "published", "closed"},
     *             example="published"
     *         )
     *     ),
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
     *                     @OA\Property(property="title", type="string", example="Midterm Exam"),
     *                     @OA\Property(property="type", type="string", example="exam"),
     *                     @OA\Property(property="duration", type="integer", example=90),
     *                     @OA\Property(property="deadline", type="string", format="date-time"),
     *                     @OA\Property(property="status", type="string", example="published"),
     *                     @OA\Property(property="questions_count", type="integer", example=25),
     *                     @OA\Property(property="attempts_count", type="integer", example=15)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $filters = $request->only(['subject_id', 'group_id', 'status']);

        $tests = $this->testService->getTests($teacher->employee->id, $filters);

        return $this->successResponse($tests);
    }

    /**
     * Get single test
     *
     * @OA\Get(
     *     path="/api/v1/teacher/tests/{id}",
     *     tags={"Teacher - Tests"},
     *     summary="Get single test details",
     *     description="Returns detailed information about a specific test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
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
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="type", type="string", example="exam"),
     *                 @OA\Property(property="duration", type="integer", example=90),
     *                 @OA\Property(property="deadline", type="string", format="date-time"),
     *                 @OA\Property(property="max_score", type="integer"),
     *                 @OA\Property(property="passing_score", type="integer"),
     *                 @OA\Property(property="shuffle_questions", type="boolean"),
     *                 @OA\Property(property="show_results_immediately", type="boolean"),
     *                 @OA\Property(property="allow_review", type="boolean"),
     *                 @OA\Property(property="max_attempts", type="integer"),
     *                 @OA\Property(property="subject", type="object"),
     *                 @OA\Property(property="group", type="object"),
     *                 @OA\Property(property="questions", type="array", @OA\Items(type="object"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $test = $this->testService->getTest($id, $teacher->employee->id);

        return $this->successResponse($test);
    }

    /**
     * Create test
     *
     * @OA\Post(
     *     path="/api/v1/teacher/tests",
     *     tags={"Teacher - Tests"},
     *     summary="Create a new test",
     *     description="Creates a new test/quiz/exam",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"subject_id", "group_id", "title", "deadline"},
     *             @OA\Property(property="subject_id", type="integer", example=1),
     *             @OA\Property(property="group_id", type="integer", example=5),
     *             @OA\Property(property="title", type="string", maxLength=255, example="Midterm Exam"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="type", type="string", enum={"quiz", "exam", "practice"}, example="exam"),
     *             @OA\Property(property="duration", type="integer", minimum=1, example=90, description="Duration in minutes"),
     *             @OA\Property(property="deadline", type="string", format="date-time", example="2025-12-01 14:00:00"),
     *             @OA\Property(property="max_score", type="integer", minimum=1, example=100),
     *             @OA\Property(property="passing_score", type="integer", minimum=0, example=60),
     *             @OA\Property(property="shuffle_questions", type="boolean", example=true),
     *             @OA\Property(property="show_results_immediately", type="boolean", example=false),
     *             @OA\Property(property="allow_review", type="boolean", example=true),
     *             @OA\Property(property="max_attempts", type="integer", minimum=1, example=3),
     *             @OA\Property(property="publish_immediately", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Test created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Test created successfully"),
     *                 @OA\Property(property="test", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'group_id' => 'required|exists:h_academic_group,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:quiz,exam,practice',
            'duration' => 'nullable|integer|min:1',
            'deadline' => 'required|date|after:now',
            'max_score' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0',
            'shuffle_questions' => 'boolean',
            'show_results_immediately' => 'boolean',
            'allow_review' => 'boolean',
            'max_attempts' => 'nullable|integer|min:1',
            'publish_immediately' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $test = $this->testService->createTest($teacher->employee->id, $request->all());

        return $this->successResponse([
            'message' => 'Test created successfully',
            'test' => $test,
        ], 201);
    }

    /**
     * Update test
     *
     * @OA\Put(
     *     path="/api/v1/teacher/tests/{id}",
     *     tags={"Teacher - Tests"},
     *     summary="Update an existing test",
     *     description="Updates test details. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", nullable=true, example="Updated Test Title"),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="type", type="string", enum={"quiz", "exam", "practice"}, nullable=true),
     *             @OA\Property(property="duration", type="integer", nullable=true, example=120),
     *             @OA\Property(property="deadline", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="max_score", type="integer", nullable=true),
     *             @OA\Property(property="passing_score", type="integer", nullable=true),
     *             @OA\Property(property="shuffle_questions", type="boolean", nullable=true),
     *             @OA\Property(property="show_results_immediately", type="boolean", nullable=true),
     *             @OA\Property(property="allow_review", type="boolean", nullable=true),
     *             @OA\Property(property="max_attempts", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Test updated successfully"),
     *                 @OA\Property(property="test", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject_id' => 'nullable|exists:e_subject,id',
            'group_id' => 'nullable|exists:h_academic_group,id',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:quiz,exam,practice',
            'duration' => 'nullable|integer|min:1',
            'deadline' => 'nullable|date',
            'max_score' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0',
            'shuffle_questions' => 'boolean',
            'show_results_immediately' => 'boolean',
            'allow_review' => 'boolean',
            'max_attempts' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $test = $this->testService->updateTest($id, $teacher->employee->id, $request->all());

        return $this->successResponse([
            'message' => 'Test updated successfully',
            'test' => $test,
        ]);
    }

    /**
     * Delete test
     *
     * @OA\Delete(
     *     path="/api/v1/teacher/tests/{id}",
     *     tags={"Teacher - Tests"},
     *     summary="Delete a test",
     *     description="Permanently deletes a test. Cannot be undone.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Test deleted successfully")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $this->testService->deleteTest($id, $teacher->employee->id);

        return $this->successResponse(['message' => 'Test deleted successfully']);
    }

    /**
     * Publish test
     *
     * @OA\Post(
     *     path="/api/v1/teacher/tests/{id}/publish",
     *     tags={"Teacher - Tests"},
     *     summary="Publish a test",
     *     description="Makes the test visible and available to students",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test published successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Test published successfully"),
     *                 @OA\Property(property="test", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $test = $this->testService->publishTest($id, $teacher->employee->id);

        return $this->successResponse([
            'message' => 'Test published successfully',
            'test' => $test,
        ]);
    }

    /**
     * Unpublish test
     *
     * @OA\Post(
     *     path="/api/v1/teacher/tests/{id}/unpublish",
     *     tags={"Teacher - Tests"},
     *     summary="Unpublish a test",
     *     description="Hides the test from students (returns to draft status)",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Test unpublished successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Test unpublished successfully"),
     *                 @OA\Property(property="test", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function unpublish(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $test = $this->testService->unpublishTest($id, $teacher->employee->id);

        return $this->successResponse([
            'message' => 'Test unpublished successfully',
            'test' => $test,
        ]);
    }

    /**
     * Get test questions
     *
     * @OA\Get(
     *     path="/api/v1/teacher/tests/{id}/questions",
     *     tags={"Teacher - Tests"},
     *     summary="Get all questions for a test",
     *     description="Returns a list of all questions for the specified test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="question_text", type="string"),
     *                     @OA\Property(property="question_type", type="string", example="multiple_choice"),
     *                     @OA\Property(property="options", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="points", type="integer"),
     *                     @OA\Property(property="order", type="integer")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function questions(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $questions = $this->testService->getQuestions($id, $teacher->employee->id);

        return $this->successResponse($questions);
    }

    /**
     * Store new question
     *
     * @OA\Post(
     *     path="/api/v1/teacher/tests/{id}/questions",
     *     tags={"Teacher - Tests"},
     *     summary="Add a new question to a test",
     *     description="Creates a new question for the specified test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question_text", "question_type", "correct_answer"},
     *             @OA\Property(property="question_text", type="string", example="What is 2+2?"),
     *             @OA\Property(
     *                 property="question_type",
     *                 type="string",
     *                 enum={"multiple_choice", "true_false", "short_answer", "essay"},
     *                 example="multiple_choice"
     *             ),
     *             @OA\Property(
     *                 property="options",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"3", "4", "5", "6"}
     *             ),
     *             @OA\Property(property="correct_answer", example="4"),
     *             @OA\Property(property="points", type="integer", example=2),
     *             @OA\Property(property="explanation", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Question added successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Question added successfully"),
     *                 @OA\Property(property="question", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function storeQuestion(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question_text' => 'required|string',
            'question_type' => 'required|in:multiple_choice,true_false,short_answer,essay',
            'options' => 'nullable|array',
            'correct_answer' => 'required',
            'points' => 'nullable|integer|min:1',
            'explanation' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $question = $this->testService->storeQuestion($id, $teacher->employee->id, $request->all());

        return $this->successResponse([
            'message' => 'Question added successfully',
            'question' => $question,
        ], 201);
    }

    /**
     * Update question
     *
     * @OA\Put(
     *     path="/api/v1/teacher/tests/{testId}/questions/{questionId}",
     *     tags={"Teacher - Tests"},
     *     summary="Update an existing question",
     *     description="Updates question details. All fields are optional.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="testId",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="questionId",
     *         in="path",
     *         description="Question ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="question_text", type="string", nullable=true),
     *             @OA\Property(property="question_type", type="string", enum={"multiple_choice", "true_false", "short_answer", "essay"}, nullable=true),
     *             @OA\Property(property="options", type="array", @OA\Items(type="string"), nullable=true),
     *             @OA\Property(property="correct_answer", nullable=true),
     *             @OA\Property(property="points", type="integer", nullable=true),
     *             @OA\Property(property="explanation", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="question", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Question or test not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function updateQuestion(Request $request, int $testId, int $questionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question_text' => 'nullable|string',
            'question_type' => 'nullable|in:multiple_choice,true_false,short_answer,essay',
            'options' => 'nullable|array',
            'correct_answer' => 'nullable',
            'points' => 'nullable|integer|min:1',
            'explanation' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $question = $this->testService->updateQuestion($testId, $questionId, $teacher->employee->id, $request->all());

        return $this->successResponse([
            'message' => 'Question updated successfully',
            'question' => $question,
        ]);
    }

    /**
     * Delete question
     *
     * @OA\Delete(
     *     path="/api/v1/teacher/tests/{testId}/questions/{questionId}",
     *     tags={"Teacher - Tests"},
     *     summary="Delete a question from a test",
     *     description="Permanently deletes a question. Cannot be undone.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="testId",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="questionId",
     *         in="path",
     *         description="Question ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Question deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="message", type="string"))
     *         )
     *     ),
     *     @OA\Response(response=404, description="Question or test not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroyQuestion(Request $request, int $testId, int $questionId): JsonResponse
    {
        $teacher = $request->user();

        $this->testService->deleteQuestion($testId, $questionId, $teacher->employee->id);

        return $this->successResponse(['message' => 'Question deleted successfully']);
    }

    /**
     * Reorder questions
     *
     * @OA\Post(
     *     path="/api/v1/teacher/tests/{id}/questions/reorder",
     *     tags={"Teacher - Tests"},
     *     summary="Reorder test questions",
     *     description="Changes the order of questions in a test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question_ids"},
     *             @OA\Property(
     *                 property="question_ids",
     *                 type="array",
     *                 @OA\Items(type="integer"),
     *                 example={3, 1, 4, 2},
     *                 description="Array of question IDs in desired order"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Questions reordered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", @OA\Property(property="message", type="string"))
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function reorderQuestions(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'question_ids' => 'required|array',
            'question_ids.*' => 'integer|exists:e_test_question,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $this->testService->reorderQuestions($id, $teacher->employee->id, $request->input('question_ids'));

        return $this->successResponse(['message' => 'Questions reordered successfully']);
    }

    /**
     * Get test results
     *
     * @OA\Get(
     *     path="/api/v1/teacher/tests/{id}/results",
     *     tags={"Teacher - Tests"},
     *     summary="Get test results",
     *     description="Returns all student attempts and results for the test",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="student_name", type="string"),
     *                     @OA\Property(property="score", type="number"),
     *                     @OA\Property(property="max_score", type="integer"),
     *                     @OA\Property(property="percentage", type="number"),
     *                     @OA\Property(property="status", type="string"),
     *                     @OA\Property(property="submitted_at", type="string", format="date-time")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function results(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $results = $this->testService->getResults($id, $teacher->employee->id);

        return $this->successResponse($results);
    }

    /**
     * Get test statistics
     *
     * @OA\Get(
     *     path="/api/v1/teacher/tests/{id}/statistics",
     *     tags={"Teacher - Tests"},
     *     summary="Get test statistics",
     *     description="Returns statistical data about test performance",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
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
     *                 @OA\Property(property="total_attempts", type="integer", example=25),
     *                 @OA\Property(property="completed", type="integer", example=20),
     *                 @OA\Property(property="in_progress", type="integer", example=5),
     *                 @OA\Property(property="average_score", type="number", example=75.5),
     *                 @OA\Property(property="highest_score", type="number", example=98),
     *                 @OA\Property(property="lowest_score", type="number", example=42),
     *                 @OA\Property(property="pass_rate", type="number", example=80)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function statistics(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $stats = $this->testService->getStatistics($id, $teacher->employee->id);

        return $this->successResponse($stats);
    }

    /**
     * Import questions
     *
     * @OA\Post(
     *     path="/api/v1/teacher/tests/{id}/import",
     *     tags={"Teacher - Tests"},
     *     summary="Import questions from CSV file",
     *     description="Bulk import questions from a CSV file",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     format="binary",
     *                     description="CSV or TXT file with questions"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Questions imported successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Questions imported successfully"),
     *                 @OA\Property(property="imported", type="integer", example=15),
     *                 @OA\Property(property="errors", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function import(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $teacher = $request->user();

        $result = $this->testService->importQuestions($id, $teacher->employee->id, $request->file('file'));

        return $this->successResponse([
            'message' => 'Questions imported successfully',
            'imported' => $result['imported'],
            'errors' => $result['errors'],
        ]);
    }

    /**
     * Export questions
     *
     * @OA\Get(
     *     path="/api/v1/teacher/tests/{id}/export",
     *     tags={"Teacher - Tests"},
     *     summary="Export test questions to CSV",
     *     description="Downloads all questions as a CSV file",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Test ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="CSV file download",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Test not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function export(Request $request, int $id)
    {
        $teacher = $request->user();

        $path = $this->testService->exportQuestions($id, $teacher->employee->id);

        return response()->download(storage_path('app/' . $path));
    }

    /**
     * Get import template
     *
     * @OA\Get(
     *     path="/api/v1/teacher/tests/import-template",
     *     tags={"Teacher - Tests"},
     *     summary="Download question import template",
     *     description="Downloads a CSV template file for importing questions",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="CSV template file download",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function importTemplate()
    {
        $path = $this->testService->getImportTemplate();

        return response()->download(storage_path('app/' . $path));
    }
}
