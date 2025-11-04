<?php

/**
 * Menu URL Mapping
 *
 * Maps legacy Yii2-style menu paths to current React frontend routes.
 * Only add mappings that are confirmed in frontend routes.
 */

return [
    'exact' => [
        // Structure
        'structure/faculty' => '/structure/faculties',
        'structure/department' => '/structure/departments',
        'structure/university' => '/structure/university',

        // Employees
        'employee/employee' => '/employees',
        'employee/teacher' => '/teachers',
        'employee/teacher-load' => '/employees/workload',
        'employee/teacher-load-formation' => '/employee/teacher-load-formation',
        'employee/academic-degree' => '/employees/academic-degrees',

        // Students
        'student/student' => '/students',
        'student/group' => '/students/groups',

        // Attendance
        'attendance/attendance-journal' => '/attendance',

        // Performance
        'performance/performance' => '/performance',
        'performance/gpa' => '/performance/gpa',

        // Curriculum
        'curriculum/subject' => '/curriculum/subjects',
        'curriculum/schedule-info' => '/curriculum/schedule',

        // Documents
        'document/sign-documents' => '/document/sign-documents',

        // Archive
        'archive/academic-record' => '/archive',
        'archive/diploma' => '/archive/diplomas',

        // Reports
        'report/by-teachers' => '/reports',

        // System
        'system/admin' => '/system/users',
        'system/role' => '/system/roles',

        // Teacher area
        'teacher/time-table' => '/teacher/schedule',
        'teacher/attendance-journal' => '/teacher/attendance',
        'teacher/midterm-exam-table' => '/teacher/tests',
        'teacher/final-exam-table' => '/teacher/tests',
        'teacher/other-exam-table' => '/teacher/tests',
        'teacher/subject-topics' => '/teacher/topics',
        'teacher/subject-tasks' => '/teacher/assignments',
        'teacher/subject-task-other' => '/teacher/assignments',
        'teacher/calendar-plan' => '/teacher/schedule',
    ],
];


