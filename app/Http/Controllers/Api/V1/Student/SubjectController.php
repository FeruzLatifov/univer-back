<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ECurriculumSubject;

class SubjectController extends Controller
{
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
