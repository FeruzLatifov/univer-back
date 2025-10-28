<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESubjectSchedule;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $student = $request->user();

        $schedule = ESubjectSchedule::where('_group', $student->_group)
            ->with(['subject', 'employee', 'lessonPair', 'auditorium'])
            ->orderBy('_week_day')
            ->orderBy('_lesson_pair')
            ->get()
            ->groupBy('_week_day')
            ->map(function ($daySchedule) {
                return $daySchedule->map(function ($item) {
                    return [
                        'subject' => $item->subject->name ?? 'N/A',
                        'teacher' => $item->employee->full_name ?? 'N/A',
                        'room' => $item->auditorium->name ?? 'N/A',
                        'time' => $item->lessonPair->start_time ?? 'N/A',
                        'type' => $item->_training_type,
                    ];
                });
            });

        return response()->json(['success' => true, 'data' => $schedule]);
    }
}
