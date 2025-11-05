<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\EAssignment;
use App\Models\EAssignmentSubmission;
use App\Models\EStudent;
use App\Models\EStudentTaskActivity;
use App\Models\ESubjectSchedule;
use App\Traits\ApiResponse;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Teacher Assignment Controller
 *
 * Manages assignments/tasks for teachers
 */
class AssignmentController extends Controller
{
    use ApiResponse;

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all assignments for teacher
     *
     * GET /api/v1/teacher/assignments
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $subjectId = $request->input('subject_id');
        $groupId = $request->input('group_id');
        $status = $request->input('status'); // upcoming, active, overdue, past

        $query = EAssignment::where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with(['subject', 'group', 'topic']);

        if ($subjectId) {
            $query->where('_subject', $subjectId);
        }

        if ($groupId) {
            $query->where('_group', $groupId);
        }

        // Filter by status
        switch ($status) {
            case 'upcoming':
                $query->where('deadline', '>', now()->addDay())
                    ->where('published_at', '<=', now());
                break;
            case 'active':
                $query->where('deadline', '>', now())
                    ->where('deadline', '<=', now()->addWeek())
                    ->where('published_at', '<=', now());
                break;
            case 'overdue':
                $query->where('deadline', '<', now());
                break;
            case 'past':
                $query->where('deadline', '<', now()->subMonth());
                break;
        }

        $assignments = $query->orderBy('deadline', 'desc')->get();

        $assignmentList = $assignments->map(function ($assignment) {
            $stats = $assignment->submission_stats;

            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'subject' => [
                    'id' => $assignment->subject->id,
                    'name' => $assignment->subject->name,
                ],
                'group' => [
                    'id' => $assignment->group->id,
                    'name' => $assignment->group->name,
                ],
                'topic' => $assignment->topic ? [
                    'id' => $assignment->topic->id,
                    'name' => $assignment->topic->name,
                ] : null,
                'deadline' => $assignment->deadline->format('Y-m-d H:i'),
                'max_score' => $assignment->max_score,
                'marking_category' => $assignment->marking_category_name,
                'is_overdue' => $assignment->is_overdue,
                'is_published' => $assignment->is_published,
                'days_until_deadline' => $assignment->days_until_deadline,
                'file_count' => $assignment->file_count,
                'submission_stats' => $stats,
                'published_at' => $assignment->published_at?->format('Y-m-d H:i'),
            ];
        });

        return $this->successResponse($assignmentList, 'Topshiriqlar ro\'yxati');
    }

    /**
     * Get assignment details
     *
     * GET /api/v1/teacher/assignment/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->with(['subject', 'group', 'topic', 'submissions.student'])
            ->firstOrFail();

        return $this->successResponse([
            'id' => $assignment->id,
            'title' => $assignment->title,
            'description' => $assignment->description,
            'instructions' => $assignment->instructions,
            'subject' => [
                'id' => $assignment->subject->id,
                'name' => $assignment->subject->name,
            ],
            'group' => [
                'id' => $assignment->group->id,
                'name' => $assignment->group->name,
            ],
            'topic' => $assignment->topic ? [
                'id' => $assignment->topic->id,
                'name' => $assignment->topic->name,
            ] : null,
            'max_score' => $assignment->max_score,
            'deadline' => $assignment->deadline->format('Y-m-d H:i'),
            'allow_late' => $assignment->allow_late,
            'attempt_count' => $assignment->attempt_count,
            'marking_category' => $assignment->_marking_category,
            'marking_category_name' => $assignment->marking_category_name,
            'files' => $assignment->files,
            'is_overdue' => $assignment->is_overdue,
            'is_published' => $assignment->is_published,
            'published_at' => $assignment->published_at?->format('Y-m-d H:i'),
            'submission_stats' => $assignment->submission_stats,
            'created_at' => $assignment->created_at->format('Y-m-d H:i'),
        ], 'Topshiriq ma\'lumotlari');
    }

    /**
     * Create new assignment
     *
     * POST /api/v1/teacher/assignment
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'group_id' => 'required|exists:e_group,id',
            'subject_topic_id' => 'nullable|exists:e_subject_topic,id',
            'title' => 'required|string|max:512',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'max_score' => 'required|integer|min:1',
            'deadline' => 'required|date|after:now',
            'marking_category' => 'nullable|in:11,12,13,14,15',
            'allow_late' => 'nullable|boolean',
            'attempt_count' => 'nullable|integer|min:1',
            'publish_now' => 'nullable|boolean',
            'files.*' => 'nullable|file|max:51200', // Max 50MB per file
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Verify teacher teaches this subject to this group
        $teachesSubject = ESubjectSchedule::where('_subject', $request->subject_id)
            ->where('_group', $request->group_id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu guruhga topshiriq berish huquqi yo\'q');
        }

        try {
            DB::beginTransaction();

            // Handle file uploads
            $uploadedFiles = [];
            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs(
                        'assignments/' . $request->subject_id . '/' . $request->group_id,
                        $filename,
                        'public'
                    );

                    $uploadedFiles[] = [
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }

            // Get current education year and semester (you may need to adjust this logic)
            $currentYear = date('Y');
            $currentSemester = date('n') <= 6 ? '2' : '1'; // Simple logic, adjust as needed

            $assignment = EAssignment::create([
                '_subject' => $request->subject_id,
                '_group' => $request->group_id,
                '_subject_topic' => $request->subject_topic_id,
                '_employee' => $teacher->employee->id,
                '_education_year' => $currentYear,
                '_semester' => $currentSemester,
                'title' => $request->title,
                'description' => $request->description,
                'instructions' => $request->instructions,
                'max_score' => $request->max_score,
                'deadline' => $request->deadline,
                '_marking_category' => $request->marking_category,
                'allow_late' => $request->allow_late ?? true,
                'attempt_count' => $request->attempt_count,
                'files' => !empty($uploadedFiles) ? $uploadedFiles : null,
                'position' => 0,
                'active' => true,
                'published_at' => $request->publish_now ? now() : null,
            ]);

            // Send notification to students if published immediately
            if ($request->publish_now) {
                $this->notificationService->notifyNewAssignment($assignment);
            }

            DB::commit();

            return $this->createdResponse([
                'id' => $assignment->id,
                'title' => $assignment->title,
                'deadline' => $assignment->deadline->format('Y-m-d H:i'),
                'is_published' => $assignment->is_published,
                'file_count' => $assignment->file_count,
            ], 'Topshiriq yaratildi');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Update assignment
     *
     * PUT /api/v1/teacher/assignment/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:512',
            'description' => 'nullable|string',
            'instructions' => 'nullable|string',
            'max_score' => 'sometimes|required|integer|min:1',
            'deadline' => 'sometimes|required|date',
            'marking_category' => 'nullable|in:11,12,13,14,15',
            'allow_late' => 'nullable|boolean',
            'attempt_count' => 'nullable|integer|min:1',
            'publish_now' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $updateData = $request->only([
            'title',
            'description',
            'instructions',
            'max_score',
            'deadline',
            '_marking_category',
            'allow_late',
            'attempt_count',
        ]);

        if ($request->has('publish_now') && $request->publish_now && !$assignment->published_at) {
            $updateData['published_at'] = now();
        }

        $assignment->update($updateData);

        return $this->successResponse([
            'id' => $assignment->id,
            'title' => $assignment->title,
            'deadline' => $assignment->deadline->format('Y-m-d H:i'),
            'is_published' => $assignment->is_published,
        ], 'Topshiriq yangilandi');
    }

    /**
     * Delete assignment (soft delete)
     *
     * DELETE /api/v1/teacher/assignment/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->firstOrFail();

        // Check if there are submissions
        $hasSubmissions = $assignment->submissions()
            ->whereNotNull('submitted_at')
            ->exists();

        if ($hasSubmissions) {
            return $this->errorResponse(
                'Bu topshiriqda yuborilgan javoblar bor. O\'chirib bo\'lmaydi.',
                400
            );
        }

        $assignment->update(['active' => false]);

        return $this->successResponse(null, 'Topshiriq o\'chirildi');
    }

    /**
     * Publish assignment
     *
     * POST /api/v1/teacher/assignment/{id}/publish
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function publish(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->firstOrFail();

        if ($assignment->is_published) {
            return $this->errorResponse('Topshiriq allaqachon nashr qilingan', 400);
        }

        $assignment->update(['published_at' => now()]);

        return $this->successResponse([
            'id' => $assignment->id,
            'published_at' => $assignment->published_at->format('Y-m-d H:i'),
        ], 'Topshiriq nashr qilindi');
    }

    /**
     * Unpublish assignment
     *
     * POST /api/v1/teacher/assignment/{id}/unpublish
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function unpublish(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->firstOrFail();

        // Check if there are submissions
        $hasSubmissions = $assignment->submissions()
            ->whereNotNull('submitted_at')
            ->exists();

        if ($hasSubmissions) {
            return $this->errorResponse(
                'Bu topshiriqda yuborilgan javoblar bor. Nashrdan olib bo\'lmaydi.',
                400
            );
        }

        $assignment->update(['published_at' => null]);

        return $this->successResponse(null, 'Topshiriq nashrdan olindi');
    }

    /**
     * Get assignment submissions
     *
     * GET /api/v1/teacher/assignment/{id}/submissions
     *
     * @param Request $request
     * @param int $id Assignment ID
     * @return JsonResponse
     */
    public function submissions(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->with(['submissions.student'])
            ->firstOrFail();

        $status = $request->input('status'); // all, submitted, pending, graded, late

        $query = $assignment->submissions()
            ->with('student')
            ->where('active', true);

        // Filter by status
        switch ($status) {
            case 'submitted':
                $query->whereNotNull('submitted_at')->whereNull('graded_at');
                break;
            case 'pending':
                $query->whereNull('submitted_at');
                break;
            case 'graded':
                $query->whereNotNull('graded_at');
                break;
            case 'late':
                $query->where('is_late', true);
                break;
        }

        $submissions = $query->orderBy('submitted_at', 'desc')->get();

        // Get students who haven't submitted
        $submittedStudentIds = $submissions->pluck('_student')->toArray();
        $allStudents = EStudent::where('_group', $assignment->_group)
            ->where('active', true)
            ->get();

        $notSubmittedStudents = $allStudents->filter(function ($student) use ($submittedStudentIds) {
            return !in_array($student->id, $submittedStudentIds);
        });

        $submissionList = $submissions->map(function ($submission) {
            return [
                'id' => $submission->id,
                'student' => [
                    'id' => $submission->student->id,
                    'student_id' => $submission->student->student_id_number,
                    'full_name' => $submission->student->full_name,
                    'photo' => $submission->student->image,
                ],
                'attempt_number' => $submission->attempt_number,
                'submitted_at' => $submission->submitted_at?->format('Y-m-d H:i'),
                'is_late' => $submission->is_late,
                'days_late' => $submission->days_late,
                'score' => $submission->score,
                'max_score' => $submission->max_score,
                'percentage' => $submission->percentage,
                'letter_grade' => $submission->letter_grade,
                'numeric_grade' => $submission->numeric_grade,
                'passed' => $submission->passed,
                'graded_at' => $submission->graded_at?->format('Y-m-d H:i'),
                'status' => $submission->status,
                'status_name' => $submission->status_name,
                'file_count' => $submission->file_count,
                'has_feedback' => !empty($submission->feedback),
            ];
        });

        $notSubmittedList = $notSubmittedStudents->map(function ($student) {
            return [
                'id' => null,
                'student' => [
                    'id' => $student->id,
                    'student_id' => $student->student_id_number,
                    'full_name' => $student->full_name,
                    'photo' => $student->image,
                ],
                'submitted_at' => null,
                'status' => 'not_submitted',
                'status_name' => 'Topshirilmagan',
            ];
        });

        return $this->successResponse([
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'deadline' => $assignment->deadline->format('Y-m-d H:i'),
                'max_score' => $assignment->max_score,
            ],
            'submissions' => $submissionList,
            'not_submitted' => $notSubmittedList,
            'stats' => $assignment->submission_stats,
        ], 'Topshiriq javoblari');
    }

    /**
     * Get single submission details
     *
     * GET /api/v1/teacher/submission/{id}
     *
     * @param Request $request
     * @param int $id Submission ID
     * @return JsonResponse
     */
    public function submissionDetail(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $submission = EAssignmentSubmission::where('id', $id)
            ->with(['assignment', 'student'])
            ->firstOrFail();

        // Verify teacher owns the assignment
        if ($submission->assignment->_employee !== $teacher->employee->id) {
            return $this->forbiddenResponse('Sizda bu javobni ko\'rish huquqi yo\'q');
        }

        // Mark as viewed
        if (!$submission->viewed_at) {
            $submission->update([
                'viewed_at' => now(),
                'status' => EAssignmentSubmission::STATUS_VIEWED,
            ]);

            EStudentTaskActivity::logActivity(
                $submission->_assignment,
                $submission->_student,
                EStudentTaskActivity::ACTIVITY_VIEWED_BY_TEACHER,
                ['teacher_id' => $teacher->employee->id]
            );
        }

        return $this->successResponse([
            'id' => $submission->id,
            'assignment' => [
                'id' => $submission->assignment->id,
                'title' => $submission->assignment->title,
                'max_score' => $submission->assignment->max_score,
            ],
            'student' => [
                'id' => $submission->student->id,
                'student_id' => $submission->student->student_id_number,
                'full_name' => $submission->student->full_name,
                'photo' => $submission->student->image,
            ],
            'text_content' => $submission->text_content,
            'files' => $submission->all_files,
            'attempt_number' => $submission->attempt_number,
            'submitted_at' => $submission->submitted_at?->format('Y-m-d H:i'),
            'is_late' => $submission->is_late,
            'days_late' => $submission->days_late,
            'score' => $submission->score,
            'max_score' => $submission->max_score,
            'percentage' => $submission->percentage,
            'feedback' => $submission->feedback,
            'graded_at' => $submission->graded_at?->format('Y-m-d H:i'),
            'viewed_at' => $submission->viewed_at?->format('Y-m-d H:i'),
            'status' => $submission->status,
            'status_name' => $submission->status_name,
        ], 'Javob tafsilotlari');
    }

    /**
     * Grade submission
     *
     * POST /api/v1/teacher/submission/{id}/grade
     *
     * @param Request $request
     * @param int $id Submission ID
     * @return JsonResponse
     */
    public function gradeSubmission(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $submission = EAssignmentSubmission::where('id', $id)
            ->with('assignment')
            ->firstOrFail();

        // Verify teacher owns the assignment
        if ($submission->assignment->_employee !== $teacher->employee->id) {
            return $this->forbiddenResponse('Sizda bu javobni baholash huquqi yo\'q');
        }

        $validator = Validator::make($request->all(), [
            'score' => 'required|numeric|min:0|max:' . $submission->max_score,
            'feedback' => 'nullable|string|max:2000',
            'return_for_revision' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $updateData = [
            'score' => $request->score,
            'feedback' => $request->feedback,
            '_employee' => $teacher->employee->id,
            'graded_at' => now(),
        ];

        if ($request->return_for_revision) {
            $updateData['status'] = EAssignmentSubmission::STATUS_RETURNED;
            $updateData['returned_at'] = now();
            $activityType = EStudentTaskActivity::ACTIVITY_RETURNED;
        } else {
            $updateData['status'] = EAssignmentSubmission::STATUS_GRADED;
            $activityType = EStudentTaskActivity::ACTIVITY_GRADED;
        }

        $submission->update($updateData);

        // Log activity
        EStudentTaskActivity::logActivity(
            $submission->_assignment,
            $submission->_student,
            $activityType,
            [
                'score' => $request->score,
                'max_score' => $submission->max_score,
                'percentage' => $submission->percentage,
            ]
        );

        return $this->successResponse([
            'id' => $submission->id,
            'score' => $submission->score,
            'max_score' => $submission->max_score,
            'percentage' => $submission->percentage,
            'letter_grade' => $submission->letter_grade,
            'numeric_grade' => $submission->numeric_grade,
            'passed' => $submission->passed,
            'graded_at' => $submission->graded_at->format('Y-m-d H:i'),
            'status' => $submission->status,
        ], 'Javob baholandi');
    }

    /**
     * Download submission file
     *
     * GET /api/v1/teacher/submission/{id}/download/{fileIndex}
     *
     * @param Request $request
     * @param int $id Submission ID
     * @param int $fileIndex File index (0-based)
     * @return mixed
     */
    public function downloadSubmissionFile(Request $request, int $id, int $fileIndex = 0)
    {
        $teacher = $request->user();

        $submission = EAssignmentSubmission::where('id', $id)
            ->with('assignment')
            ->firstOrFail();

        // Verify teacher owns the assignment
        if ($submission->assignment->_employee !== $teacher->employee->id) {
            return $this->forbiddenResponse('Sizda bu faylni yuklab olish huquqi yo\'q');
        }

        $allFiles = $submission->all_files;

        if (!isset($allFiles[$fileIndex])) {
            return $this->notFoundResponse('Fayl topilmadi');
        }

        $file = $allFiles[$fileIndex];

        if (!Storage::disk('public')->exists($file['path'])) {
            return $this->notFoundResponse('Fayl tizimda topilmadi');
        }

        // Log activity
        EStudentTaskActivity::logActivity(
            $submission->_assignment,
            $submission->_student,
            EStudentTaskActivity::ACTIVITY_FILE_DOWNLOADED,
            ['file_name' => $file['name'], 'teacher_id' => $teacher->employee->id]
        );

        return Storage::disk('public')->download($file['path'], $file['name']);
    }

    /**
     * Get assignment statistics
     *
     * GET /api/v1/teacher/assignment/{id}/statistics
     *
     * @param Request $request
     * @param int $id Assignment ID
     * @return JsonResponse
     */
    public function statistics(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->with('submissions')
            ->firstOrFail();

        $submissions = $assignment->submissions()
            ->whereNotNull('submitted_at')
            ->whereNotNull('graded_at')
            ->get();

        if ($submissions->isEmpty()) {
            return $this->successResponse([
                'message' => 'Hali baholangan javoblar yo\'q',
            ]);
        }

        $scores = $submissions->pluck('percentage');

        $statistics = [
            'total_students' => EStudent::where('_group', $assignment->_group)
                ->where('active', true)
                ->count(),
            'submitted' => $assignment->submissions()->whereNotNull('submitted_at')->count(),
            'not_submitted' => $assignment->submission_stats['not_submitted'],
            'graded' => $submissions->count(),
            'pending_grading' => $assignment->submission_stats['pending_grading'],
            'late_submissions' => $assignment->submissions()->where('is_late', true)->count(),
            'average_score' => round($scores->avg(), 2),
            'highest_score' => $scores->max(),
            'lowest_score' => $scores->min(),
            'median_score' => $scores->median(),
            'passed' => $submissions->where('passed', true)->count(),
            'failed' => $submissions->where('passed', false)->count(),
            'grade_distribution' => [
                '5' => $submissions->where('numeric_grade', '5')->count(),
                '4' => $submissions->where('numeric_grade', '4')->count(),
                '3' => $submissions->where('numeric_grade', '3')->count(),
                '2' => $submissions->where('numeric_grade', '2')->count(),
            ],
            'letter_distribution' => [
                'A' => $submissions->where('letter_grade', 'A')->count(),
                'B' => $submissions->where('letter_grade', 'B')->count(),
                'C' => $submissions->where('letter_grade', 'C')->count(),
                'D' => $submissions->where('letter_grade', 'D')->count(),
                'E' => $submissions->where('letter_grade', 'E')->count(),
                'F' => $submissions->where('letter_grade', 'F')->count(),
            ],
        ];

        return $this->successResponse($statistics, 'Topshiriq statistikasi');
    }

    /**
     * Get recent activities
     *
     * GET /api/v1/teacher/assignment/{id}/activities
     *
     * @param Request $request
     * @param int $id Assignment ID
     * @return JsonResponse
     */
    public function activities(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $assignment = EAssignment::where('id', $id)
            ->where('_employee', $teacher->employee->id)
            ->firstOrFail();

        $days = $request->input('days', 7);

        $activities = EStudentTaskActivity::where('_assignment', $id)
            ->with('student')
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $activityList = $activities->map(function ($activity) {
            return [
                'id' => $activity->id,
                'student' => [
                    'id' => $activity->student->id,
                    'full_name' => $activity->student->full_name,
                ],
                'activity_type' => $activity->activity_type,
                'activity_name' => $activity->activity_name,
                'details' => $activity->parsed_details,
                'created_at' => $activity->created_at->format('Y-m-d H:i:s'),
                'time_ago' => $activity->created_at->diffForHumans(),
            ];
        });

        return $this->successResponse($activityList, 'Topshiriq faolligi');
    }

    /**
     * Get teacher's subjects (for dropdown)
     *
     * GET /api/v1/teacher/my-subjects
     *
     * Returns list of subjects that teacher is teaching in current semester
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function mySubjects(Request $request): JsonResponse
    {
        $teacher = $request->user();

        // Get distinct subjects from schedule where teacher is assigned
        $subjects = ESubjectSchedule::where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with('subject')
            ->select('_subject')
            ->distinct()
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->subject->id,
                    'name' => $schedule->subject->name,
                    'code' => $schedule->subject->code ?? null,
                ];
            })
            ->unique('id')
            ->values();

        return $this->successResponse($subjects, 'O\'qituvchi fanlari');
    }

    /**
     * Get teacher's groups (for dropdown)
     *
     * GET /api/v1/teacher/my-groups
     *
     * Returns list of groups that teacher is teaching
     * Optionally filter by subject_id
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myGroups(Request $request): JsonResponse
    {
        $teacher = $request->user();
        $subjectId = $request->input('subject_id');

        $query = ESubjectSchedule::where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with('group');

        if ($subjectId) {
            $query->where('_subject', $subjectId);
        }

        $groups = $query->select('_group')
            ->distinct()
            ->get()
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->group->id,
                    'name' => $schedule->group->name,
                    'code' => $schedule->group->code ?? null,
                ];
            })
            ->unique('id')
            ->values();

        return $this->successResponse($groups, 'O\'qituvchi guruhlari');
    }
}
