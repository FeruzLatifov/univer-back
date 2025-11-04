<?php

/**
 * Permission Aliases Configuration
 *
 * Comprehensive mapping between Yii2 path-based permissions and Laravel dotted notation
 *
 * SECTIONS:
 * 1. map - Bidirectional permission mapping for flexibility
 * 2. path_to_dot - Canonical Yii2 → Laravel permission mapping (EXPANDED)
 * 3. wildcards - Wildcard permission expansion rules
 *
 * @version 2.0 - Expanded for full Yii2 compatibility
 * @date 2025-11-03
 */

return [
    // Map strict required permission => acceptable alternative permissions
    // Helps to bridge Yii2 route permissions and Laravel dot permissions
    'map' => [
        'document.sign.view' => [
            'document.view',
            'document/sign-documents',
        ],
        'employee.load.view' => [
            'employee.view',
            'employee/teacher-load-formation',
        ],
        'student.view' => [
            'student.view',
            'student/student',
        ],
        'teacher.timetable.view' => [
            'teacher.view',
            'teacher/time-table',
        ],
    ],

    // Canonical mapping from legacy route path to dot-notation permission
    // Prevents over-broad permissions like `employee.view` being granted for load-only resources
    //
    // ✅ EXPANDED with 100+ most common Yii2 paths
    'path_to_dot' => [
        // Documents
        'document/sign-documents' => 'document.sign.view',

        // Structure
        'structure/university' => 'structure.university.view',
        'structure/faculty' => 'structure.faculty.view',
        'structure/department' => 'structure.department.view',

        // Employee (Base)
        'employee/employee' => 'employee.view',
        'employee/create' => 'employee.create',
        'employee/update' => 'employee.edit',
        'employee/delete' => 'employee.delete',
        'employee/teacher' => 'employee.teacher.view',
        'employee/direction' => 'employee.direction.view',

        // Employee (Load - Specific permissions to avoid over-broad grant)
        'employee/teacher-load-formation' => 'employee.load.view',
        'employee/department-load' => 'employee.load.view',
        'employee/teacher-load' => 'employee.load.view',
        'employee/teacher-load-type' => 'employee.load.view',
        'employee/load-monitoring' => 'employee.load.view',

        // Employee (Professional Development)
        'employee/professional-development' => 'employee.development.view',
        'employee/academic-degree' => 'employee.degree.view',
        'employee/foreign-training' => 'employee.training.view',

        // Student (Base)
        'student/student' => 'student.view',
        'student/create' => 'student.create',
        'student/update' => 'student.edit',
        'student/delete' => 'student.delete',
        'student/group' => 'group.view',
        'student/special' => 'student.special.view',
        'student/contingent-list' => 'student.contingent.view',

        // Transfer
        'transfer/student-group' => 'transfer.group.view',
        'transfer/student-expel' => 'transfer.expel.view',
        'transfer/academic-leave' => 'transfer.leave.view',
        'transfer/restore' => 'transfer.restore.view',
        'transfer/graduate' => 'transfer.graduate.view',

        // Decree
        'decree/index' => 'decree.view',
        'decree/edu-decree' => 'decree.view',
        'decree/template' => 'decree.template.view',

        // Curriculum & Subjects
        'curriculum/subject' => 'subject.view',
        'curriculum/subject-create' => 'subject.create',
        'curriculum/subject-group' => 'subject.group.view',
        'curriculum/curriculum' => 'curriculum.view',
        'curriculum/semester' => 'curriculum.semester.view',
        'curriculum/schedule' => 'schedule.view',
        'curriculum/schedule-info' => 'schedule.view',
        'curriculum/exam-schedule' => 'exam.schedule.view',

        // Teacher
        'teacher/time-table' => 'teacher.timetable.view',
        'teacher/attendance-journal' => 'teacher.attendance.view',
        'teacher/rating-journal' => 'teacher.rating.view',
        'teacher/midterm-exam-table' => 'teacher.exam.view',
        'teacher/final-exam-table' => 'teacher.exam.view',
        'teacher/subject-topics' => 'subject.topics.view',
        'teacher/subject-tasks' => 'subject.tasks.view',

        // Attendance
        'attendance/attendance-journal' => 'attendance.view',
        'attendance/activity' => 'attendance.view',
        'attendance/report' => 'attendance.view',
        'attendance/lessons' => 'attendance.view',

        // Performance
        'performance/performance' => 'performance.view',
        'performance/debtors' => 'performance.view',
        'performance/gpa' => 'gpa.view',
        'performance/appeal' => 'performance.appeal.view',

        // Archive
        'archive/academic-record' => 'archive.record.view',
        'archive/diploma' => 'archive.diploma.view',
        'archive/diploma-list' => 'archive.diploma.view',
        'archive/transcript' => 'archive.transcript.view',

        // Finance
        'finance/student-contract' => 'finance.contract.view',
        'finance/payment-monitoring' => 'finance.payment.view',
        'finance/scholarship' => 'scholarship.view',
        'finance/minimum-wage' => 'finance.wage.view',

        // Statistics & Reports
        'statistical/by-student' => 'statistical.view',
        'statistical/by-teacher' => 'statistical.view',
        'report/by-teachers' => 'report.view',
        'report/by-students' => 'report.view',

        // System
        'system/admin' => 'admin.view',
        'system/role' => 'role.view',
        'system/configuration' => 'system.config.view',
        'system/system-log' => 'system.log.view',

        // Science
        'science/project' => 'science.project.view',
        'science/publication-methodical' => 'science.publication.view',
        'science/teacher-rating' => 'rating.teacher.view',
    ],

    /**
     * Wildcard Permission Expansions
     *
     * Defines which specific permissions a wildcard includes
     * Used for permission checking with patterns like 'student.*'
     */
    'wildcards' => [
        'student.*' => [
            'student.view',
            'student.create',
            'student.edit',
            'student.delete',
            'student.special.view',
            'student.contingent.view',
        ],
        'employee.*' => [
            'employee.view',
            'employee.create',
            'employee.edit',
            'employee.delete',
            'employee.teacher.view',
            'employee.direction.view',
            'employee.load.view',
            'employee.development.view',
        ],
        'teacher.*' => [
            'teacher.timetable.view',
            'teacher.attendance.view',
            'teacher.rating.view',
            'teacher.exam.view',
        ],
        'system.*' => [
            'admin.view',
            'role.view',
            'system.config.view',
            'system.log.view',
        ],
        'performance.*' => [
            'performance.view',
            'performance.appeal.view',
            'gpa.view',
        ],
    ],

    /**
     * Permission Aliases
     *
     * Map deprecated or alternative permission names to canonical ones
     */
    'aliases' => [
        'student/index' => 'student/student',
        'employee/index' => 'employee/employee',
    ],
];


