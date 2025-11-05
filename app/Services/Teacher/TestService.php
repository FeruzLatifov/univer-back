<?php

namespace App\Services\Teacher;

use App\Models\ETest;
use App\Models\ETestQuestion;
use App\Models\ETestResult;
use App\Models\EStudent;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Teacher Test Service
 *
 * Handles all test/quiz related business logic for teachers
 */
class TestService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get teacher's tests with filters
     */
    public function getTests(int $teacherId, array $filters = []): array
    {
        $query = ETest::where('_employee', $teacherId)
            ->where('active', true)
            ->with(['subject', 'group']);

        if (!empty($filters['subject_id'])) {
            $query->where('_subject', $filters['subject_id']);
        }

        if (!empty($filters['group_id'])) {
            $query->where('_group', $filters['group_id']);
        }

        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'published':
                    $query->whereNotNull('published_at');
                    break;
                case 'draft':
                    $query->whereNull('published_at');
                    break;
                case 'active':
                    $query->whereNotNull('published_at')
                        ->where('deadline', '>', now());
                    break;
                case 'ended':
                    $query->where('deadline', '<', now());
                    break;
            }
        }

        $tests = $query->orderBy('created_at', 'desc')->get();

        return $tests->map(function ($test) {
            return [
                'id' => $test->id,
                'title' => $test->title,
                'subject' => [
                    'id' => $test->subject->id,
                    'name' => $test->subject->name,
                ],
                'group' => [
                    'id' => $test->group->id,
                    'name' => $test->group->name,
                ],
                'type' => $test->type,
                'duration' => $test->duration,
                'deadline' => $test->deadline,
                'max_score' => $test->max_score,
                'question_count' => $test->questions_count ?? 0,
                'is_published' => $test->published_at !== null,
                'published_at' => $test->published_at,
                'created_at' => $test->created_at,
            ];
        })->toArray();
    }

    /**
     * Get single test details
     */
    public function getTest(int $id, int $teacherId): array
    {
        $test = ETest::where('id', $id)
            ->where('_employee', $teacherId)
            ->with(['subject', 'group'])
            ->firstOrFail();

        return [
            'id' => $test->id,
            'title' => $test->title,
            'description' => $test->description,
            'subject' => [
                'id' => $test->subject->id,
                'name' => $test->subject->name,
            ],
            'group' => [
                'id' => $test->group->id,
                'name' => $test->group->name,
            ],
            'type' => $test->type,
            'duration' => $test->duration,
            'deadline' => $test->deadline,
            'max_score' => $test->max_score,
            'passing_score' => $test->passing_score,
            'shuffle_questions' => $test->shuffle_questions,
            'show_results_immediately' => $test->show_results_immediately,
            'allow_review' => $test->allow_review,
            'max_attempts' => $test->max_attempts,
            'question_count' => $test->questions_count ?? 0,
            'is_published' => $test->published_at !== null,
            'published_at' => $test->published_at,
            'created_at' => $test->created_at,
            'updated_at' => $test->updated_at,
        ];
    }

    /**
     * Create new test
     */
    public function createTest(int $teacherId, array $data): ETest
    {
        return DB::transaction(function () use ($teacherId, $data) {
            $test = ETest::create([
                '_employee' => $teacherId,
                '_subject' => $data['subject_id'],
                '_group' => $data['group_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'type' => $data['type'] ?? 'quiz',
                'duration' => $data['duration'] ?? 60,
                'deadline' => $data['deadline'],
                'max_score' => $data['max_score'] ?? 100,
                'passing_score' => $data['passing_score'] ?? 60,
                'shuffle_questions' => $data['shuffle_questions'] ?? false,
                'show_results_immediately' => $data['show_results_immediately'] ?? true,
                'allow_review' => $data['allow_review'] ?? true,
                'max_attempts' => $data['max_attempts'] ?? 1,
                'published_at' => $data['publish_immediately'] ? now() : null,
                'active' => true,
            ]);

            if ($test->published_at) {
                $this->notifyStudents($test);
            }

            return $test;
        });
    }

    /**
     * Update test
     */
    public function updateTest(int $id, int $teacherId, array $data): ETest
    {
        $test = ETest::where('id', $id)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $test->update([
            '_subject' => $data['subject_id'] ?? $test->_subject,
            '_group' => $data['group_id'] ?? $test->_group,
            'title' => $data['title'] ?? $test->title,
            'description' => $data['description'] ?? $test->description,
            'type' => $data['type'] ?? $test->type,
            'duration' => $data['duration'] ?? $test->duration,
            'deadline' => $data['deadline'] ?? $test->deadline,
            'max_score' => $data['max_score'] ?? $test->max_score,
            'passing_score' => $data['passing_score'] ?? $test->passing_score,
            'shuffle_questions' => $data['shuffle_questions'] ?? $test->shuffle_questions,
            'show_results_immediately' => $data['show_results_immediately'] ?? $test->show_results_immediately,
            'allow_review' => $data['allow_review'] ?? $test->allow_review,
            'max_attempts' => $data['max_attempts'] ?? $test->max_attempts,
        ]);

        return $test->fresh();
    }

    /**
     * Delete test
     */
    public function deleteTest(int $id, int $teacherId): bool
    {
        $test = ETest::where('id', $id)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        return $test->delete();
    }

    /**
     * Publish test
     */
    public function publishTest(int $id, int $teacherId): ETest
    {
        return DB::transaction(function () use ($id, $teacherId) {
            $test = ETest::where('id', $id)
                ->where('_employee', $teacherId)
                ->whereNull('published_at')
                ->firstOrFail();

            $test->update(['published_at' => now()]);

            $this->notifyStudents($test);

            return $test;
        });
    }

    /**
     * Unpublish test
     */
    public function unpublishTest(int $id, int $teacherId): ETest
    {
        $test = ETest::where('id', $id)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $test->update(['published_at' => null]);

        return $test;
    }

    /**
     * Get test questions
     */
    public function getQuestions(int $testId, int $teacherId): array
    {
        $test = ETest::where('id', $testId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $questions = ETestQuestion::where('_test', $testId)
            ->orderBy('order_number')
            ->get();

        return $questions->map(function ($question) {
            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_type' => $question->question_type,
                'options' => $question->options,
                'correct_answer' => $question->correct_answer,
                'points' => $question->points,
                'order_number' => $question->order_number,
                'explanation' => $question->explanation,
            ];
        })->toArray();
    }

    /**
     * Store new question
     */
    public function storeQuestion(int $testId, int $teacherId, array $data): ETestQuestion
    {
        // Verify test ownership
        $test = ETest::where('id', $testId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        // Get next order number
        $maxOrder = ETestQuestion::where('_test', $testId)->max('order_number') ?? 0;

        return ETestQuestion::create([
            '_test' => $testId,
            'question_text' => $data['question_text'],
            'question_type' => $data['question_type'],
            'options' => $data['options'] ?? null,
            'correct_answer' => $data['correct_answer'],
            'points' => $data['points'] ?? 1,
            'order_number' => $maxOrder + 1,
            'explanation' => $data['explanation'] ?? null,
        ]);
    }

    /**
     * Update question
     */
    public function updateQuestion(int $testId, int $questionId, int $teacherId, array $data): ETestQuestion
    {
        // Verify test ownership
        $test = ETest::where('id', $testId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $question = ETestQuestion::where('id', $questionId)
            ->where('_test', $testId)
            ->firstOrFail();

        $question->update([
            'question_text' => $data['question_text'] ?? $question->question_text,
            'question_type' => $data['question_type'] ?? $question->question_type,
            'options' => $data['options'] ?? $question->options,
            'correct_answer' => $data['correct_answer'] ?? $question->correct_answer,
            'points' => $data['points'] ?? $question->points,
            'explanation' => $data['explanation'] ?? $question->explanation,
        ]);

        return $question;
    }

    /**
     * Delete question
     */
    public function deleteQuestion(int $testId, int $questionId, int $teacherId): bool
    {
        // Verify test ownership
        $test = ETest::where('id', $testId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $question = ETestQuestion::where('id', $questionId)
            ->where('_test', $testId)
            ->firstOrFail();

        return $question->delete();
    }

    /**
     * Reorder questions
     */
    public function reorderQuestions(int $testId, int $teacherId, array $questionIds): bool
    {
        // Verify test ownership
        $test = ETest::where('id', $testId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        DB::transaction(function () use ($testId, $questionIds) {
            foreach ($questionIds as $index => $questionId) {
                ETestQuestion::where('id', $questionId)
                    ->where('_test', $testId)
                    ->update(['order_number' => $index + 1]);
            }
        });

        return true;
    }

    /**
     * Get test results
     */
    public function getResults(int $testId, int $teacherId): array
    {
        $test = ETest::where('id', $testId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $results = ETestResult::where('_test', $testId)
            ->with('student')
            ->get();

        return $results->map(function ($result) use ($test) {
            return [
                'id' => $result->id,
                'student' => [
                    'id' => $result->student->id,
                    'name' => $result->student->second_name . ' ' . $result->student->first_name,
                    'student_id' => $result->student->student_id_number,
                ],
                'score' => $result->score,
                'max_score' => $test->max_score,
                'percentage' => round(($result->score / $test->max_score) * 100, 2),
                'passed' => $result->score >= $test->passing_score,
                'attempt_number' => $result->attempt_number,
                'started_at' => $result->started_at,
                'completed_at' => $result->completed_at,
                'duration' => $result->duration_minutes,
            ];
        })->toArray();
    }

    /**
     * Get test statistics
     */
    public function getStatistics(int $testId, int $teacherId): array
    {
        $test = ETest::where('id', $testId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $results = ETestResult::where('_test', $testId)->get();

        $totalStudents = EStudent::whereHas('meta', function ($q) use ($test) {
            $q->where('_group', $test->_group);
        })->count();

        $completed = $results->count();
        $notStarted = $totalStudents - $completed;

        $scores = $results->pluck('score');
        $avgScore = $scores->avg();
        $maxScore = $scores->max();
        $minScore = $scores->min();

        $passed = $results->filter(fn($r) => $r->score >= $test->passing_score)->count();
        $failed = $completed - $passed;

        return [
            'total_students' => $totalStudents,
            'completed' => $completed,
            'not_started' => $notStarted,
            'completion_rate' => $totalStudents > 0 ? round(($completed / $totalStudents) * 100, 2) : 0,
            'passed' => $passed,
            'failed' => $failed,
            'pass_rate' => $completed > 0 ? round(($passed / $completed) * 100, 2) : 0,
            'scores' => [
                'average' => $avgScore ? round($avgScore, 2) : 0,
                'max' => $maxScore ?? 0,
                'min' => $minScore ?? 0,
                'max_possible' => $test->max_score,
                'passing_score' => $test->passing_score,
            ],
        ];
    }

    /**
     * Import questions from file
     */
    public function importQuestions(int $testId, int $teacherId, $file): array
    {
        // Verify test ownership
        $test = ETest::where('id', $testId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        // Parse CSV/Excel file
        $questions = $this->parseQuestionsFile($file);

        $imported = 0;
        $errors = [];

        DB::transaction(function () use ($testId, $questions, &$imported, &$errors) {
            $maxOrder = ETestQuestion::where('_test', $testId)->max('order_number') ?? 0;

            foreach ($questions as $index => $questionData) {
                try {
                    ETestQuestion::create([
                        '_test' => $testId,
                        'question_text' => $questionData['question'],
                        'question_type' => $questionData['type'] ?? 'multiple_choice',
                        'options' => $questionData['options'] ?? null,
                        'correct_answer' => $questionData['correct_answer'],
                        'points' => $questionData['points'] ?? 1,
                        'order_number' => $maxOrder + $index + 1,
                        'explanation' => $questionData['explanation'] ?? null,
                    ]);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row " . ($index + 1) . ": " . $e->getMessage();
                }
            }
        });

        return [
            'imported' => $imported,
            'errors' => $errors,
        ];
    }

    /**
     * Export questions to file
     */
    public function exportQuestions(int $testId, int $teacherId): string
    {
        $test = ETest::where('id', $testId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $questions = ETestQuestion::where('_test', $testId)
            ->orderBy('order_number')
            ->get();

        // Generate CSV content
        $csv = "Question,Type,Options,Correct Answer,Points,Explanation\n";

        foreach ($questions as $question) {
            $options = is_array($question->options) ? implode('|', $question->options) : '';

            $csv .= sprintf(
                "\"%s\",\"%s\",\"%s\",\"%s\",%d,\"%s\"\n",
                str_replace('"', '""', $question->question_text),
                $question->question_type,
                str_replace('"', '""', $options),
                str_replace('"', '""', $question->correct_answer),
                $question->points,
                str_replace('"', '""', $question->explanation ?? '')
            );
        }

        // Save to temporary file
        $filename = 'test_' . $testId . '_questions_' . now()->timestamp . '.csv';
        $path = 'exports/' . $filename;
        Storage::put($path, $csv);

        return $path;
    }

    /**
     * Get import template
     */
    public function getImportTemplate(): string
    {
        $csv = "Question,Type,Options,Correct Answer,Points,Explanation\n";
        $csv .= "\"What is 2+2?\",\"multiple_choice\",\"2|3|4|5\",\"4\",1,\"Basic arithmetic\"\n";
        $csv .= "\"Laravel is a PHP framework\",\"true_false\",\"true|false\",\"true\",1,\"Laravel is indeed a PHP framework\"\n";

        $filename = 'test_import_template.csv';
        $path = 'templates/' . $filename;
        Storage::put($path, $csv);

        return $path;
    }

    /**
     * Parse questions file
     */
    protected function parseQuestionsFile($file): array
    {
        $content = file_get_contents($file->path());
        $lines = explode("\n", $content);
        array_shift($lines); // Remove header

        $questions = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $data = str_getcsv($line);

            if (count($data) >= 4) {
                $questions[] = [
                    'question' => $data[0],
                    'type' => $data[1] ?? 'multiple_choice',
                    'options' => !empty($data[2]) ? explode('|', $data[2]) : null,
                    'correct_answer' => $data[3],
                    'points' => isset($data[4]) ? (int)$data[4] : 1,
                    'explanation' => $data[5] ?? null,
                ];
            }
        }

        return $questions;
    }

    /**
     * Notify students about new test
     */
    protected function notifyStudents(ETest $test): void
    {
        $students = EStudent::whereHas('meta', function ($q) use ($test) {
            $q->where('_group', $test->_group);
        })->get();

        foreach ($students as $student) {
            $this->notificationService->send([
                'user_id' => $student->id,
                'type' => 'new_test',
                'title' => 'New Test Available',
                'message' => "New test: {$test->title}",
                'data' => [
                    'test_id' => $test->id,
                    'deadline' => $test->deadline,
                ],
            ]);
        }
    }
}
