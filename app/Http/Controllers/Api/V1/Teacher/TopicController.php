<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ESubjectSchedule;
use App\Models\ESubjectTopic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Topic/Syllabus Controller
 *
 * Manages course syllabus and topics
 */
class TopicController extends Controller
{
    use ApiResponse;

    /**
     * Get all topics for a subject
     *
     * GET /api/v1/teacher/subject/{id}/topics
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        $topics = ESubjectTopic::where('_subject', $id)
            ->where('active', true)
            ->orderBy('order_number')
            ->get();

        $topicList = $topics->map(function ($topic, $index) {
            return [
                'id' => $topic->id,
                'name' => $topic->name,
                'content' => $topic->content,
                'order_number' => $topic->order_number ?? ($index + 1),
                'hours' => $topic->hours,
            ];
        });

        return $this->successResponse([
            'total_topics' => $topics->count(),
            'total_hours' => $topics->sum('hours'),
            'topics' => $topicList,
        ], 'Mavzular ro\'yxati');
    }

    /**
     * Create new topic
     *
     * POST /api/v1/teacher/subject/{id}/topic
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function store(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu fanga mavzu qo\'shish huquqi yo\'q');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:512',
            'content' => 'nullable|string',
            'hours' => 'nullable|integer|min:0',
            'order_number' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // If no order_number provided, add to end
        if (!$request->has('order_number')) {
            $maxOrder = ESubjectTopic::where('_subject', $id)
                ->where('active', true)
                ->max('order_number');
            $orderNumber = ($maxOrder ?? 0) + 1;
        } else {
            $orderNumber = $request->order_number;
        }

        $topic = ESubjectTopic::create([
            '_subject' => $id,
            'name' => $request->name,
            'content' => $request->content,
            'hours' => $request->hours,
            'order_number' => $orderNumber,
            'active' => true,
        ]);

        return $this->createdResponse([
            'id' => $topic->id,
            'name' => $topic->name,
            'content' => $topic->content,
            'hours' => $topic->hours,
            'order_number' => $topic->order_number,
        ], 'Mavzu qo\'shildi');
    }

    /**
     * Update topic
     *
     * PUT /api/v1/teacher/subject/{subjectId}/topic/{topicId}
     *
     * @param Request $request
     * @param int $subjectId Subject ID
     * @param int $topicId Topic ID
     * @return JsonResponse
     */
    public function update(Request $request, int $subjectId, int $topicId): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $subjectId)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu mavzuni o\'zgartirish huquqi yo\'q');
        }

        $topic = ESubjectTopic::where('id', $topicId)
            ->where('_subject', $subjectId)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:512',
            'content' => 'nullable|string',
            'hours' => 'nullable|integer|min:0',
            'order_number' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $topic->update($request->only(['name', 'content', 'hours', 'order_number']));

        return $this->successResponse([
            'id' => $topic->id,
            'name' => $topic->name,
            'content' => $topic->content,
            'hours' => $topic->hours,
            'order_number' => $topic->order_number,
        ], 'Mavzu yangilandi');
    }

    /**
     * Delete topic
     *
     * DELETE /api/v1/teacher/subject/{subjectId}/topic/{topicId}
     *
     * @param Request $request
     * @param int $subjectId Subject ID
     * @param int $topicId Topic ID
     * @return JsonResponse
     */
    public function destroy(Request $request, int $subjectId, int $topicId): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $subjectId)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu mavzuni o\'chirish huquqi yo\'q');
        }

        $topic = ESubjectTopic::where('id', $topicId)
            ->where('_subject', $subjectId)
            ->firstOrFail();

        $topic->update(['active' => false]);

        return $this->successResponse(null, 'Mavzu o\'chirildi');
    }

    /**
     * Reorder topics
     *
     * POST /api/v1/teacher/subject/{id}/topics/reorder
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function reorder(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->exists();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        $validator = Validator::make($request->all(), [
            'topics' => 'required|array',
            'topics.*.id' => 'required|exists:e_subject_topic,id',
            'topics.*.order_number' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            DB::beginTransaction();

            foreach ($request->topics as $topicData) {
                ESubjectTopic::where('id', $topicData['id'])
                    ->where('_subject', $id)
                    ->update(['order_number' => $topicData['order_number']]);
            }

            DB::commit();

            return $this->successResponse(null, 'Mavzular tartibi o\'zgartirildi');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Get syllabus summary
     *
     * GET /api/v1/teacher/subject/{id}/syllabus
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function syllabus(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        // Verify teacher teaches this subject
        $teachesSubject = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->with('subject')
            ->where('active', true)
            ->first();

        if (!$teachesSubject) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        $topics = ESubjectTopic::where('_subject', $id)
            ->where('active', true)
            ->orderBy('order_number')
            ->get();

        $subject = $teachesSubject->subject;

        return $this->successResponse([
            'subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'credit' => $subject->credit,
            ],
            'summary' => [
                'total_topics' => $topics->count(),
                'total_hours' => $topics->sum('hours'),
                'lecture_hours' => null, // Can be calculated from curriculum_subject
                'practice_hours' => null,
            ],
            'topics' => $topics->map(fn($t) => [
                'order' => $t->order_number,
                'name' => $t->name,
                'hours' => $t->hours,
            ]),
        ], 'Fan dasturi');
    }
}
