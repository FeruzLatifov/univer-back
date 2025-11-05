<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESubjectSchedule;

class ScheduleController extends Controller
{
    /**
     * Get student schedule
     *
     * @OA\Get(
     *     path="/api/v1/student/schedule",
     *     tags={"Student - Schedule"},
     *     summary="Get student class schedule",
     *     description="Returns the weekly class schedule for the authenticated student grouped by day of the week. Includes subject, teacher, room, time, and type information.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Schedule grouped by day of week (1=Monday, 7=Sunday)",
     *                 @OA\Property(
     *                     property="1",
     *                     type="array",
     *                     description="Monday classes",
     *                     @OA\Items(
     *                         @OA\Property(property="subject", type="string", example="Mathematics"),
     *                         @OA\Property(property="teacher", type="string", example="Dr. John Smith"),
     *                         @OA\Property(property="room", type="string", example="Room 201"),
     *                         @OA\Property(property="time", type="string", example="09:00"),
     *                         @OA\Property(property="type", type="integer", example=1, description="Training type (1=Lecture, 2=Practice, etc.)")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="2",
     *                     type="array",
     *                     description="Tuesday classes",
     *                     @OA\Items(
     *                         @OA\Property(property="subject", type="string", example="Physics"),
     *                         @OA\Property(property="teacher", type="string", example="Prof. Jane Doe"),
     *                         @OA\Property(property="room", type="string", example="Lab 105"),
     *                         @OA\Property(property="time", type="string", example="10:30"),
     *                         @OA\Property(property="type", type="integer", example=2)
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Internal server error")
     *         )
     *     )
     * )
     */
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
