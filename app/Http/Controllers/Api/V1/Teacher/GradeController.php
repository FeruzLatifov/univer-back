<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\Controller;
use App\Models\EGrade;
use App\Models\ESubjectSchedule;
use App\Models\EStudent;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Teacher Grade Controller
 *
 * Manages student grading and exam results
 */
class GradeController extends Controller
{
    use ApiResponse;

    /**
     * Get grades for a subject
     *
     * GET /api/v1/teacher/subject/{id}/grades
     *
     * @param Request $request
     * @param int $id Subject ID
     * @return JsonResponse
     */
    public function index(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();
        $gradeType = $request->input('type'); // current, midterm, final

        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with(['group', 'subject'])
            ->first();

        if (!$schedule) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        // Get students
        $students = EStudent::where('_group', $schedule->_group)
            ->where('active', true)
            ->get();

        // Get grades
        $gradesQuery = EGrade::where('_subject', $id)
            ->whereIn('_student', $students->pluck('id'));

        if ($gradeType) {
            $gradesQuery->where('_grade_type', $this->mapGradeType($gradeType));
        }

        $grades = $gradesQuery->get()->keyBy(function ($grade) {
            return $grade->_student . '_' . $grade->_grade_type;
        });

        // Build student list with grades
        $studentList = $students->map(function ($student) use ($grades) {
            return [
                'id' => $student->id,
                'student_id' => $student->student_id_number,
                'full_name' => $student->full_name,
                'photo' => $student->image,
                'grades' => [
                    'current' => $this->getGradeData($grades, $student->id, EGrade::TYPE_CURRENT),
                    'midterm' => $this->getGradeData($grades, $student->id, EGrade::TYPE_MIDTERM),
                    'final' => $this->getGradeData($grades, $student->id, EGrade::TYPE_FINAL),
                    'overall' => $this->getGradeData($grades, $student->id, EGrade::TYPE_OVERALL),
                ],
            ];
        });

        return $this->successResponse([
            'subject' => [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
            ],
            'group' => [
                'id' => $schedule->group->id,
                'name' => $schedule->group->name,
            ],
            'students' => $studentList,
        ], 'Baholar ro\'yxati');
    }

    /**
     * Enter/update grade for a student
     *
     * POST /api/v1/teacher/grade
     *
     * Body:
     * {
     *   "subject_id": 1,
     *   "student_id": 123,
     *   "grade_type": "midterm",
     *   "grade": 85,
     *   "max_grade": 100,
     *   "comment": "Yaxshi ishladi"
     * }
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'student_id' => 'required|exists:e_student,id',
            'grade_type' => 'required|in:current,midterm,final,overall',
            'grade' => 'required|numeric|min:0',
            'max_grade' => 'required|integer|min:1',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $request->subject_id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->first();

        if (!$schedule) {
            return $this->forbiddenResponse('Sizda bu fanga baho qo\'yish huquqi yo\'q');
        }

        // Verify student is in the group
        $student = EStudent::where('id', $request->student_id)
            ->where('_group', $schedule->_group)
            ->where('active', true)
            ->first();

        if (!$student) {
            return $this->forbiddenResponse('Talaba bu guruhda emas');
        }

        try {
            $gradeType = $this->mapGradeType($request->grade_type);

            $grade = EGrade::updateOrCreate(
                [
                    '_student' => $request->student_id,
                    '_subject' => $request->subject_id,
                    '_grade_type' => $gradeType,
                    '_semester' => $schedule->_semester,
                    '_education_year' => $schedule->_education_year,
                ],
                [
                    'grade' => $request->grade,
                    'max_grade' => $request->max_grade,
                    'comment' => $request->comment,
                    '_employee' => $teacher->employee->id,
                    'active' => true,
                ]
            );

            return $this->successResponse([
                'id' => $grade->id,
                'student_id' => $student->student_id_number,
                'student_name' => $student->full_name,
                'grade_type' => $request->grade_type,
                'grade' => $grade->grade,
                'max_grade' => $grade->max_grade,
                'percentage' => $grade->percentage,
                'letter_grade' => $grade->letter_grade,
            ], 'Baho muvaffaqiyatli saqlandi');

        } catch (\Exception $e) {
            return $this->serverErrorResponse('Baho qo\'yishda xatolik: ' . $e->getMessage());
        }
    }

    /**
     * Update existing grade
     *
     * PUT /api/v1/teacher/grade/{id}
     *
     * @param Request $request
     * @param int $id Grade ID
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $teacher = $request->user();

        $validator = Validator::make($request->all(), [
            'grade' => 'required|numeric|min:0',
            'max_grade' => 'required|integer|min:1',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $grade = EGrade::findOrFail($id);

        // Verify teacher has access
        $schedule = ESubjectSchedule::where('_subject', $grade->_subject)
            ->where('_employee', $teacher->employee->id)
            ->first();

        if (!$schedule) {
            return $this->forbiddenResponse('Sizda bu bahoni o\'zgartirish huquqi yo\'q');
        }

        $grade->update([
            'grade' => $request->grade,
            'max_grade' => $request->max_grade,
            'comment' => $request->comment,
        ]);

        return $this->successResponse([
            'id' => $grade->id,
            'grade' => $grade->grade,
            'max_grade' => $grade->max_grade,
            'percentage' => $grade->percentage,
            'letter_grade' => $grade->letter_grade,
        ], 'Baho yangilandi');
    }

    /**
     * Get grade statistics and report
     *
     * GET /api/v1/teacher/grade/report
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function report(Request $request): JsonResponse
    {
        $teacher = $request->user();

        $validator = Validator::make($request->all(), [
            'subject_id' => 'required|exists:e_subject,id',
            'grade_type' => 'nullable|in:current,midterm,final,overall',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $request->subject_id)
            ->where('_employee', $teacher->employee->id)
            ->where('active', true)
            ->with(['subject', 'group'])
            ->first();

        if (!$schedule) {
            return $this->forbiddenResponse('Sizda bu fanga kirish huquqi yo\'q');
        }

        $gradeType = $request->grade_type ? $this->mapGradeType($request->grade_type) : null;

        // Get grades
        $gradesQuery = EGrade::where('_subject', $request->subject_id);

        if ($gradeType) {
            $gradesQuery->where('_grade_type', $gradeType);
        }

        $grades = $gradesQuery->get();

        // Calculate statistics
        $gradeValues = $grades->pluck('percentage');

        $statistics = [
            'total_students' => $grades->count(),
            'average_percentage' => $gradeValues->avg(),
            'highest_percentage' => $gradeValues->max(),
            'lowest_percentage' => $gradeValues->min(),
            'distribution' => [
                'A' => $grades->where('letter_grade', 'A')->count(),
                'B' => $grades->where('letter_grade', 'B')->count(),
                'C' => $grades->where('letter_grade', 'C')->count(),
                'D' => $grades->where('letter_grade', 'D')->count(),
                'E' => $grades->where('letter_grade', 'E')->count(),
                'F' => $grades->where('letter_grade', 'F')->count(),
            ],
        ];

        return $this->successResponse([
            'subject' => $schedule->subject->name,
            'group' => $schedule->group->name,
            'grade_type' => $request->grade_type ?? 'all',
            'statistics' => $statistics,
        ], 'Baholar hisoboti');
    }

    /**
     * Helper: Map grade type string to constant
     */
    private function mapGradeType(string $type): string
    {
        $map = [
            'current' => EGrade::TYPE_CURRENT,
            'midterm' => EGrade::TYPE_MIDTERM,
            'final' => EGrade::TYPE_FINAL,
            'overall' => EGrade::TYPE_OVERALL,
        ];

        return $map[$type] ?? EGrade::TYPE_CURRENT;
    }

    /**
     * Helper: Get grade data for student
     */
    private function getGradeData($grades, $studentId, $gradeType): ?array
    {
        $key = $studentId . '_' . $gradeType;
        $grade = $grades->get($key);

        if (!$grade) {
            return null;
        }

        return [
            'id' => $grade->id,
            'grade' => $grade->grade,
            'max_grade' => $grade->max_grade,
            'percentage' => $grade->percentage,
            'letter_grade' => $grade->letter_grade,
            'comment' => $grade->comment,
        ];
    }
}
