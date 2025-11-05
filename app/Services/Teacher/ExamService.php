<?php

namespace App\Services\Teacher;

use App\Models\EExam;
use App\Models\EExamResult;
use App\Models\EStudent;
use Illuminate\Support\Facades\DB;

/**
 * Teacher Exam Service
 *
 * Handles exam management and result recording
 */
class ExamService
{
    /**
     * Get teacher's exams with filters
     */
    public function getExams(int $teacherId, array $filters = []): array
    {
        $query = EExam::where('_employee', $teacherId)
            ->where('active', true)
            ->with(['subject', 'group']);

        if (!empty($filters['subject_id'])) {
            $query->where('_subject', $filters['subject_id']);
        }

        if (!empty($filters['group_id'])) {
            $query->where('_group', $filters['group_id']);
        }

        if (!empty($filters['type'])) {
            $query->where('exam_type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            switch ($filters['status']) {
                case 'upcoming':
                    $query->where('exam_date', '>', now());
                    break;
                case 'completed':
                    $query->where('exam_date', '<', now());
                    break;
            }
        }

        $exams = $query->orderBy('exam_date', 'desc')->get();

        return $exams->map(function ($exam) {
            $resultsCount = EExamResult::where('_exam', $exam->id)->count();
            $totalStudents = EStudent::whereHas('meta', function ($q) use ($exam) {
                $q->where('_group', $exam->_group);
            })->count();

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'subject' => [
                    'id' => $exam->subject->id,
                    'name' => $exam->subject->name,
                ],
                'group' => [
                    'id' => $exam->group->id,
                    'name' => $exam->group->name,
                ],
                'exam_type' => $exam->exam_type,
                'exam_date' => $exam->exam_date,
                'max_score' => $exam->max_score,
                'duration' => $exam->duration,
                'location' => $exam->location,
                'results_entered' => $resultsCount,
                'total_students' => $totalStudents,
                'is_completed' => $exam->exam_date < now(),
                'created_at' => $exam->created_at,
            ];
        })->toArray();
    }

    /**
     * Create new exam
     */
    public function createExam(int $teacherId, array $data): EExam
    {
        return EExam::create([
            '_employee' => $teacherId,
            '_subject' => $data['subject_id'],
            '_group' => $data['group_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'exam_type' => $data['exam_type'] ?? 'midterm',
            'exam_date' => $data['exam_date'],
            'duration' => $data['duration'] ?? 120,
            'max_score' => $data['max_score'] ?? 100,
            'passing_score' => $data['passing_score'] ?? 60,
            'location' => $data['location'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'active' => true,
        ]);
    }

    /**
     * Get single exam details
     */
    public function getExam(int $id, int $teacherId): array
    {
        $exam = EExam::where('id', $id)
            ->where('_employee', $teacherId)
            ->with(['subject', 'group'])
            ->firstOrFail();

        $results = EExamResult::where('_exam', $id)
            ->with('student')
            ->get();

        $totalStudents = EStudent::whereHas('meta', function ($q) use ($exam) {
            $q->where('_group', $exam->_group);
        })->count();

        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'description' => $exam->description,
            'subject' => [
                'id' => $exam->subject->id,
                'name' => $exam->subject->name,
            ],
            'group' => [
                'id' => $exam->group->id,
                'name' => $exam->group->name,
            ],
            'exam_type' => $exam->exam_type,
            'exam_date' => $exam->exam_date,
            'duration' => $exam->duration,
            'max_score' => $exam->max_score,
            'passing_score' => $exam->passing_score,
            'location' => $exam->location,
            'instructions' => $exam->instructions,
            'results_count' => $results->count(),
            'total_students' => $totalStudents,
            'is_completed' => $exam->exam_date < now(),
            'results' => $results->map(function ($result) {
                return [
                    'student_id' => $result->_student,
                    'student_name' => $result->student->second_name . ' ' . $result->student->first_name,
                    'score' => $result->score,
                    'grade' => $result->grade,
                    'attended' => $result->attended,
                ];
            })->toArray(),
            'created_at' => $exam->created_at,
            'updated_at' => $exam->updated_at,
        ];
    }

    /**
     * Enter exam results
     */
    public function enterResults(int $examId, int $teacherId, array $results): array
    {
        $exam = EExam::where('id', $examId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        return DB::transaction(function () use ($examId, $results) {
            $created = 0;
            $updated = 0;
            $errors = [];

            foreach ($results as $result) {
                try {
                    $existingResult = EExamResult::where('_exam', $examId)
                        ->where('_student', $result['student_id'])
                        ->first();

                    $data = [
                        '_exam' => $examId,
                        '_student' => $result['student_id'],
                        'score' => $result['score'],
                        'grade' => $result['grade'] ?? $this->calculateGrade($result['score'], $examId),
                        'attended' => $result['attended'] ?? true,
                        'notes' => $result['notes'] ?? null,
                    ];

                    if ($existingResult) {
                        $existingResult->update($data);
                        $updated++;
                    } else {
                        EExamResult::create($data);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Student ID {$result['student_id']}: " . $e->getMessage();
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
            ];
        });
    }

    /**
     * Get exam statistics
     */
    public function getStatistics(int $examId, int $teacherId): array
    {
        $exam = EExam::where('id', $examId)
            ->where('_employee', $teacherId)
            ->firstOrFail();

        $results = EExamResult::where('_exam', $examId)->get();

        $totalStudents = EStudent::whereHas('meta', function ($q) use ($exam) {
            $q->where('_group', $exam->_group);
        })->count();

        $attended = $results->where('attended', true)->count();
        $absent = $totalStudents - $attended;

        $scores = $results->where('attended', true)->pluck('score');
        $avgScore = $scores->avg();
        $maxScore = $scores->max();
        $minScore = $scores->min();

        $passed = $results->where('score', '>=', $exam->passing_score)->count();
        $failed = $attended - $passed;

        return [
            'total_students' => $totalStudents,
            'attended' => $attended,
            'absent' => $absent,
            'attendance_rate' => $totalStudents > 0 ? round(($attended / $totalStudents) * 100, 2) : 0,
            'passed' => $passed,
            'failed' => $failed,
            'pass_rate' => $attended > 0 ? round(($passed / $attended) * 100, 2) : 0,
            'scores' => [
                'average' => $avgScore ? round($avgScore, 2) : 0,
                'max' => $maxScore ?? 0,
                'min' => $minScore ?? 0,
                'max_possible' => $exam->max_score,
                'passing_score' => $exam->passing_score,
            ],
            'grade_distribution' => $this->getGradeDistribution($results),
        ];
    }

    /**
     * Calculate grade from score
     */
    protected function calculateGrade(float $score, int $examId): string
    {
        $exam = EExam::find($examId);
        $percentage = ($score / $exam->max_score) * 100;

        if ($percentage >= 90) return 'A';
        if ($percentage >= 80) return 'B';
        if ($percentage >= 70) return 'C';
        if ($percentage >= 60) return 'D';
        return 'F';
    }

    /**
     * Get grade distribution
     */
    protected function getGradeDistribution($results): array
    {
        $distribution = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
            'D' => 0,
            'F' => 0,
        ];

        foreach ($results as $result) {
            if ($result->attended && isset($distribution[$result->grade])) {
                $distribution[$result->grade]++;
            }
        }

        return $distribution;
    }
}
