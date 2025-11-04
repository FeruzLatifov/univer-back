<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teacher Load Controller
 *
 * Manages teacher load (workload) information
 * Equivalent to Yii2: EmployeeController::actionTeacherLoadFormation
 *
 * @route /api/v1/employee/teacher-load
 */
class TeacherLoadController extends Controller
{
    /**
     * Get teacher load list
     *
     * @route GET /api/v1/employee/teacher-load
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Get authenticated teacher
            $user = auth('admin-api')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Autentifikatsiya talab qilinadi',
                ], 401);
            }

            // Get teacher loads for current employee
            $teacherLoads = DB::table('e_teacher_load')
                ->join('h_education_year', 'e_teacher_load._education_year', '=', 'h_education_year.id')
                ->where('e_teacher_load._employee', $user->_employee)
                ->where('e_teacher_load.active', true)
                ->select([
                    'e_teacher_load.id',
                    'e_teacher_load._employee',
                    'e_teacher_load._education_year',
                    'e_teacher_load.total_load',
                    'e_teacher_load.created_at',
                    'e_teacher_load.updated_at',
                    'h_education_year.name as education_year_name',
                    'h_education_year.code as education_year_code',
                ])
                ->orderBy('h_education_year.code', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher_loads' => $teacherLoads,
                    'total' => $teacherLoads->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get teacher load details
     *
     * @route GET /api/v1/employee/teacher-load/{id}
     * @param int $id Teacher load ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = auth('admin-api')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Autentifikatsiya talab qilinadi',
                ], 401);
            }

            // Get teacher load with details
            $teacherLoad = DB::table('e_teacher_load')
                ->join('h_education_year', 'e_teacher_load._education_year', '=', 'h_education_year.id')
                ->where('e_teacher_load.id', $id)
                ->where('e_teacher_load._employee', $user->_employee)
                ->select([
                    'e_teacher_load.*',
                    'h_education_year.name as education_year_name',
                ])
                ->first();

            if (!$teacherLoad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Yuklama topilmadi',
                ], 404);
            }

            // Get autumn semester subjects
            $autumnSubjects = DB::table('e_teacher_load_meta')
                ->join('e_subject', 'e_teacher_load_meta._subject', '=', 'e_subject.id')
                ->leftJoin('h_training_type', 'e_teacher_load_meta._training_type', '=', 'h_training_type.id')
                ->where('e_teacher_load_meta._teacher_load', $id)
                ->where('e_teacher_load_meta._semester_type', 11)
                ->select([
                    'e_teacher_load_meta.*',
                    'e_subject.name as subject_name',
                    'h_training_type.name as training_type_name',
                ])
                ->get();

            // Get spring semester subjects
            $springSubjects = DB::table('e_teacher_load_meta')
                ->join('e_subject', 'e_teacher_load_meta._subject', '=', 'e_subject.id')
                ->leftJoin('h_training_type', 'e_teacher_load_meta._training_type', '=', 'h_training_type.id')
                ->where('e_teacher_load_meta._teacher_load', $id)
                ->where('e_teacher_load_meta._semester_type', 12)
                ->select([
                    'e_teacher_load_meta.*',
                    'e_subject.name as subject_name',
                    'h_training_type.name as training_type_name',
                ])
                ->get();

            // Get scientific work
            $scientificWork = DB::table('e_teacher_load_scientific')
                ->where('_teacher_load', $id)
                ->get();

            // Get methodical work
            $methodicalWork = DB::table('e_teacher_load_methodical')
                ->where('_teacher_load', $id)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'teacher_load' => $teacherLoad,
                    'autumn_subjects' => $autumnSubjects,
                    'spring_subjects' => $springSubjects,
                    'scientific_work' => $scientificWork,
                    'methodical_work' => $methodicalWork,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Xatolik yuz berdi',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download teacher load as PDF
     *
     * @route GET /api/v1/employee/teacher-load/{id}/download
     * @param int $id Teacher load ID
     * @return \Illuminate\Http\Response
     */
    public function download($id)
    {
        // TODO: Implement PDF generation
        return response()->json([
            'success' => false,
            'message' => 'PDF generatsiya hali implementatsiya qilinmagan',
        ], 501);
    }
}


