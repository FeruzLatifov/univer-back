<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESubjectTest;
use App\Models\EStudentTestAttempt;

class TestController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();

        $tests = ESubjectTest::whereHas('subject.curriculumSubjects', function ($query) use ($student) {
                $query->where('curriculum_id', $student->_curriculum);
            })
            ->where('status', 'published')
            ->where('end_time', '>=', now())
            ->with(['subject'])
            ->get()
            ->map(function ($test) use ($student) {
                $attempts = $test->attempts()->where('_student', $student->id)->count();
                $bestScore = $test->attempts()->where('_student', $student->id)->max('score');

                return [
                    'id' => $test->id,
                    'title' => $test->title,
                    'subject' => $test->subject->name ?? 'N/A',
                    'duration' => $test->duration,
                    'start_time' => $test->start_time,
                    'end_time' => $test->end_time,
                    'max_attempts' => $test->max_attempts,
                    'attempts_count' => $attempts,
                    'best_score' => $bestScore,
                    'can_attempt' => $attempts < $test->max_attempts && $test->start_time <= now(),
                ];
            });

        return response()->json(['success' => true, 'data' => $tests]);
    }

    public function results(Request $request)
    {
        $student = $request->user();

        $results = EStudentTestAttempt::where('_student', $student->id)
            ->with(['test.subject'])
            ->orderBy('finished_at', 'desc')
            ->paginate(15);

        return response()->json(['success' => true, 'data' => $results]);
    }
}
