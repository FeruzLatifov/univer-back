<?php

namespace App\Services\Teacher;

use App\Models\EGrade;
use App\Models\ESubjectSchedule;
use App\Models\EStudent;

/**
 * Teacher Grade Service
 *
 * BUSINESS LOGIC LAYER
 * ======================
 *
 * Modular Monolith Architecture - Teacher Module
 * Contains all business logic for teacher grading management
 *
 * Controller → Service → Repository → Model
 *
 * @package App\Services\Teacher
 */
class GradeService
{
    /**
     * Get grades for a subject
     *
     * @param int $teacherId Teacher employee ID
     * @param int $subjectId Subject ID
     * @param string|null $gradeType Grade type filter
     * @return array
     * @throws \Exception
     */
    public function getGrades(int $teacherId, int $subjectId, ?string $gradeType = null): array
    {
        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $subjectId)
            ->where('_employee', $teacherId)
            ->where('active', true)
            ->with(['group', 'subject'])
            ->first();

        if (!$schedule) {
            throw new \Exception('Sizda bu fanga kirish huquqi yo\'q');
        }

        // Get students (using meta relationship to avoid _group column issue)
        $students = EStudent::whereHas('meta', function($q) use ($schedule) {
            $q->where('_group', $schedule->_group)->where('active', true);
        })->where('active', true)->get();

        // Get grades
        $gradesQuery = EGrade::where('_subject', $subjectId)
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

        return [
            'subject' => [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
            ],
            'group' => [
                'id' => $schedule->group->id,
                'name' => $schedule->group->name,
            ],
            'students' => $studentList->toArray(),
        ];
    }

    /**
     * Enter or update grade for a student
     *
     * @param int $teacherId Teacher employee ID
     * @param int $subjectId Subject ID
     * @param int $studentId Student ID
     * @param string $gradeType Grade type
     * @param float $grade Grade value
     * @param int $maxGrade Maximum grade
     * @param string|null $comment Comment
     * @return array
     * @throws \Exception
     */
    public function storeGrade(
        int $teacherId,
        int $subjectId,
        int $studentId,
        string $gradeType,
        float $grade,
        int $maxGrade,
        ?string $comment = null
    ): array {
        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $subjectId)
            ->where('_employee', $teacherId)
            ->where('active', true)
            ->first();

        if (!$schedule) {
            throw new \Exception('Sizda bu fanga baho qo\'yish huquqi yo\'q');
        }

        // Verify student is in the group (using meta relationship)
        $student = EStudent::where('id', $studentId)
            ->whereHas('meta', function($q) use ($schedule) {
                $q->where('_group', $schedule->_group)->where('active', true);
            })
            ->where('active', true)
            ->first();

        if (!$student) {
            throw new \Exception('Talaba bu guruhda emas');
        }

        $gradeTypeCode = $this->mapGradeType($gradeType);

        $gradeModel = EGrade::updateOrCreate(
            [
                '_student' => $studentId,
                '_subject' => $subjectId,
                '_grade_type' => $gradeTypeCode,
                '_semester' => $schedule->_semester,
                '_education_year' => $schedule->_education_year,
            ],
            [
                'grade' => $grade,
                'max_grade' => $maxGrade,
                'comment' => $comment,
                '_employee' => $teacherId,
                'active' => true,
            ]
        );

        return [
            'id' => $gradeModel->id,
            'student_id' => $student->student_id_number,
            'student_name' => $student->full_name,
            'grade_type' => $gradeType,
            'grade' => $gradeModel->grade,
            'max_grade' => $gradeModel->max_grade,
            'percentage' => $gradeModel->percentage,
            'letter_grade' => $gradeModel->letter_grade,
        ];
    }

    /**
     * Update existing grade
     *
     * @param int $teacherId Teacher employee ID
     * @param int $gradeId Grade ID
     * @param float $grade Grade value
     * @param int $maxGrade Maximum grade
     * @param string|null $comment Comment
     * @return array
     * @throws \Exception
     */
    public function updateGrade(
        int $teacherId,
        int $gradeId,
        float $grade,
        int $maxGrade,
        ?string $comment = null
    ): array {
        $gradeModel = EGrade::findOrFail($gradeId);

        // Verify teacher has access
        $schedule = ESubjectSchedule::where('_subject', $gradeModel->_subject)
            ->where('_employee', $teacherId)
            ->first();

        if (!$schedule) {
            throw new \Exception('Sizda bu bahoni o\'zgartirish huquqi yo\'q');
        }

        $gradeModel->update([
            'grade' => $grade,
            'max_grade' => $maxGrade,
            'comment' => $comment,
        ]);

        return [
            'id' => $gradeModel->id,
            'grade' => $gradeModel->grade,
            'max_grade' => $gradeModel->max_grade,
            'percentage' => $gradeModel->percentage,
            'letter_grade' => $gradeModel->letter_grade,
        ];
    }

    /**
     * Get grade statistics and report
     *
     * @param int $teacherId Teacher employee ID
     * @param int $subjectId Subject ID
     * @param string|null $gradeType Grade type filter
     * @return array
     * @throws \Exception
     */
    public function getGradeReport(int $teacherId, int $subjectId, ?string $gradeType = null): array
    {
        // Verify teacher teaches this subject
        $schedule = ESubjectSchedule::where('_subject', $subjectId)
            ->where('_employee', $teacherId)
            ->where('active', true)
            ->with(['subject', 'group'])
            ->first();

        if (!$schedule) {
            throw new \Exception('Sizda bu fanga kirish huquqi yo\'q');
        }

        $gradeTypeCode = $gradeType ? $this->mapGradeType($gradeType) : null;

        // Get grades
        $gradesQuery = EGrade::where('_subject', $subjectId);

        if ($gradeTypeCode) {
            $gradesQuery->where('_grade_type', $gradeTypeCode);
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

        return [
            'subject' => $schedule->subject->name,
            'group' => $schedule->group->name,
            'grade_type' => $gradeType ?? 'all',
            'statistics' => $statistics,
        ];
    }

    /**
     * Map grade type string to constant
     *
     * @param string $type
     * @return string
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
     * Get grade data for student
     *
     * @param \Illuminate\Support\Collection $grades
     * @param int $studentId
     * @param string $gradeType
     * @return array|null
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
