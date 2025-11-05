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
     * @OA\Get(
     *     path="/api/v1/employee/teacher-load",
     *     summary="Get teacher workload list",
     *     description="Retrieve list of teacher workload records for the authenticated employee",
     *     operationId="employeeTeacherLoadList",
     *     tags={"Employee - Teacher Load"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Teacher loads retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="teacher_loads",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="_employee", type="integer", example=123),
     *                         @OA\Property(property="_education_year", type="integer", example=5),
     *                         @OA\Property(property="total_load", type="number", format="float", example=900.5),
     *                         @OA\Property(property="created_at", type="string", format="date-time", example="2024-11-05T12:00:00Z"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-11-05T12:00:00Z"),
     *                         @OA\Property(property="education_year_name", type="string", example="2024-2025"),
     *                         @OA\Property(property="education_year_code", type="string", example="2024")
     *                     )
     *                 ),
     *                 @OA\Property(property="total", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Autentifikatsiya talab qilinadi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Xatolik yuz berdi"),
     *             @OA\Property(property="error", type="string", example="Database error")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/employee/teacher-load/{id}",
     *     summary="Get teacher workload details",
     *     description="Retrieve detailed information about a specific teacher workload including subjects, scientific and methodical work",
     *     operationId="employeeTeacherLoadDetails",
     *     tags={"Employee - Teacher Load"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Teacher load ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Teacher load details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="teacher_load",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="total_load", type="number", format="float", example=900.5),
     *                     @OA\Property(property="education_year_name", type="string", example="2024-2025")
     *                 ),
     *                 @OA\Property(
     *                     property="autumn_subjects",
     *                     type="array",
     *                     description="Subjects taught in autumn semester",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="subject_name", type="string", example="Mathematics"),
     *                         @OA\Property(property="training_type_name", type="string", example="Lecture")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="spring_subjects",
     *                     type="array",
     *                     description="Subjects taught in spring semester",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="subject_name", type="string", example="Physics"),
     *                         @OA\Property(property="training_type_name", type="string", example="Practice")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="scientific_work",
     *                     type="array",
     *                     description="Scientific work activities",
     *                     @OA\Items(type="object")
     *                 ),
     *                 @OA\Property(
     *                     property="methodical_work",
     *                     type="array",
     *                     description="Methodical work activities",
     *                     @OA\Items(type="object")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Autentifikatsiya talab qilinadi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Teacher load not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Yuklama topilmadi")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Xatolik yuz berdi"),
     *             @OA\Property(property="error", type="string", example="Database error")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     path="/api/v1/employee/teacher-load/{id}/download",
     *     summary="Download teacher workload as PDF",
     *     description="Generate and download a PDF document of the teacher's workload (not yet implemented)",
     *     operationId="employeeTeacherLoadDownload",
     *     tags={"Employee - Teacher Load"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Teacher load ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF file download",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(
     *                 type="string",
     *                 format="binary"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=501,
     *         description="Not implemented",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="PDF generatsiya hali implementatsiya qilinmagan")
     *         )
     *     )
     * )
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


