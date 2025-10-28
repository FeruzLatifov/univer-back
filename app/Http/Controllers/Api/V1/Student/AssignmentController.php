<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\EAssignment;
use App\Models\EAssignmentSubmission;

class AssignmentController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();

        $assignments = EAssignment::whereHas('subject.curriculumSubjects', function ($query) use ($student) {
                $query->where('curriculum_id', $student->_curriculum);
            })
            ->where('status', 'published')
            ->with(['subject'])
            ->orderBy('deadline', 'desc')
            ->paginate(15);

        $assignments->getCollection()->transform(function ($assignment) use ($student) {
            $submission = $assignment->submissions()->where('_student', $student->id)->first();
            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'subject' => $assignment->subject->name ?? 'N/A',
                'deadline' => $assignment->deadline,
                'max_grade' => $assignment->max_grade,
                'status' => $submission ? 'submitted' : 'pending',
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'submitted_at' => $submission->submitted_at,
                    'grade' => $submission->grade,
                ] : null,
            ];
        });

        return response()->json(['success' => true, 'data' => $assignments]);
    }

    public function submit(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string',
            'files' => 'nullable|array|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $student = $request->user();
        $assignment = EAssignment::findOrFail($id);

        if ($assignment->deadline < now()) {
            return response()->json(['success' => false, 'message' => 'Deadline passed'], 400);
        }

        $submission = EAssignmentSubmission::updateOrCreate(
            ['_assignment' => $id, '_student' => $student->id],
            [
                'content' => $request->content,
                'submitted_at' => now(),
                'status' => 'submitted',
            ]
        );

        return response()->json(['success' => true, 'data' => $submission], 201);
    }
}
