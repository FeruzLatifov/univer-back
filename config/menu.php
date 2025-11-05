<?php

/**
 * Backend Menu Configuration
 *
 * Migrated from Yii2 (backend/config/params.php)
 * Labels auto-synced from Yii2
 *
 * @updated 2025-11-03 10:57:22
 */

return array (
  'backend' => 
  array (
    'structure' => 
    array (
      'icon' => 'school',
      'label' => 'Structure of HEI',
      'url' => '/structure/university',
      'permission' => 'structure.view',
      'order' => 1,
      'items' => 
      array (
        'structure/about-university' => 
        array (
          'label' => 'About HEI',
          'url' => '/structure/university',
          'icon' => 'info',
          'permission' => 'structure.university.view',
        ),
        'structure/faculty' => 
        array (
          'label' => 'Faculty',
          'url' => '/structure/faculty',
          'icon' => 'building',
          'permission' => 'structure.faculty.view',
        ),
        'structure/department' => 
        array (
          'label' => 'Department',
          'url' => '/structure/department',
          'icon' => 'briefcase-business',
          'permission' => 'structure.department.view',
        ),
        'structure/section' => 
        array (
          'label' => 'Section',
          'url' => '/structure/section',
          'icon' => 'folder-tree',
          'permission' => 'structure.section.view',
        ),
      ),
    ),
    'document' =>
    array (
      'icon' => 'folder-open',
      'label' => 'E-Documents',
      'url' => '/document/sign-documents',
      'permission' => 'document/sign-documents',
      'order' => 2,
      'items' =>
      array (
        'document/sign-documents' =>
        array (
          'label' => 'Document Sign Documents',
          'url' => '/document/sign-documents',
          'icon' => 'file-signature',
          'permission' => 'document/sign-documents',
        ),
      ),
    ),
    'employee' => 
    array (
      'icon' => 'briefcase',
      'label' => 'Employee Information',
      'url' => '/employee/employee',
      'permission' => 'employee.view',
      'order' => 3,
      'items' => 
      array (
        'employee/employee' => 
        array (
          'label' => 'Employee',
          'url' => '/employee/employee',
          'icon' => 'user-round',
          'permission' => 'employee.view',
        ),
        'employee/direction' => 
        array (
          'label' => 'Direction',
          'url' => '/employee/direction',
          'icon' => 'compass',
          'permission' => 'employee.direction.view',
        ),
        'employee/teacher' => 
        array (
          'label' => 'Teacher',
          'url' => '/employee/teacher',
          'icon' => 'school',
          'permission' => 'employee.teacher.view',
        ),
        'employee/professional-development' => 
        array (
          'label' => 'Professional Development',
          'url' => '/employee/professional-development',
          'icon' => 'rocket',
          'permission' => 'employee.development.view',
        ),
        'employee/competition' => 
        array (
          'label' => 'Competition',
          'url' => '/employee/competition',
          'icon' => 'trophy',
          'permission' => 'employee.competition.view',
        ),
        'employee/academic-degree' => 
        array (
          'label' => 'Employee Academic Degree',
          'url' => '/employee/academic-degree',
          'icon' => 'medal',
          'permission' => 'employee.degree.view',
        ),
        'employee/foreign-training' => 
        array (
          'label' => 'Employee Foreign Training',
          'url' => '/employee/foreign-training',
          'icon' => 'plane',
          'permission' => 'employee.training.view',
        ),
        'employee/foreign-employee' => 
        array (
          'label' => 'Employee Foreign Employee',
          'url' => '/employee/foreign-employee',
          'icon' => 'globe-2',
          'permission' => 'employee.foreign.view',
        ),
        'employee/tutor-group' => 
        array (
          'label' => 'Employee Tutor Group',
          'url' => '/employee/tutor-group',
          'icon' => 'users-round',
          'permission' => 'employee.tutor.view',
        ),
        'employee/department-load' => 
        array (
          'label' => 'Department Load',
          'url' => '/employee/department-load',
          'icon' => 'package',
          'permission' => 'employee/department-load',
        ),
        'employee/teacher-load' => 
        array (
          'label' => 'Teacher Load',
          'url' => '/employee/teacher-load',
          'icon' => 'briefcase',
          'permission' => 'employee/teacher-load',
        ),
        'employee/teacher-load-formation' =>
        array (
          'label' => 'Employee Teacher Load Formation',
          'url' => '/employee/teacher-load-formation',
          'icon' => 'clipboard-list',
          'permission' => 'employee/teacher-load-formation',
        ),
        'employee/teacher-load-type' => 
        array (
          'label' => 'Teacher Load Type',
          'url' => '/employee/teacher-load-type',
          'icon' => 'list-tree',
          'permission' => 'employee/teacher-load-type',
        ),
        'employee/load-monitoring' => 
        array (
          'label' => 'Load Monitoring',
          'url' => '/employee/load-monitoring',
          'icon' => 'eye',
          'permission' => 'employee/load-monitoring',
        ),
        'employee/foreign-certificate' => 
        array (
          'label' => 'Employee Foreign Certificate',
          'url' => '/employee/foreign-certificate',
          'icon' => 'award',
          'permission' => 'employee.certificate.view',
        ),
      ),
    ),
    'student' => 
    array (
      'icon' => 'users',
      'label' => 'Student Information',
      'url' => '/student/student',
      'permission' => 'student.view',
      'order' => 4,
      'items' => 
      array (
        'student/special' => 
        array (
          'label' => 'Special',
          'url' => '/student/special',
          'icon' => 'star',
          'permission' => 'student.special.view',
        ),
        'student/student' => 
        array (
          'label' => 'Student',
          'url' => '/student/student',
          'icon' => 'user',
          'permission' => 'student.view',
        ),
        'student/group' => 
        array (
          'label' => 'Group',
          'url' => '/student/group',
          'icon' => 'users',
          'permission' => 'group.view',
        ),
        'student/student-fixed' => 
        array (
          'label' => 'Student Fixed',
          'url' => '/student/student-fixed',
          'icon' => 'lock',
          'permission' => 'student.fixed.view',
        ),
        'student/student-contingent' => 
        array (
          'label' => 'Contingent',
          'url' => '/student/student-contingent',
          'icon' => 'list-checks',
          'permission' => 'student.contingent.view',
        ),
        'student/contingent-list' => 
        array (
          'label' => 'Contingent List',
          'url' => '/student/contingent-list',
          'icon' => 'clipboard-list',
          'permission' => 'student.contingent.view',
        ),
        'student/admission-quota' => 
        array (
          'label' => 'Admission Quota',
          'url' => '/student/admission-quota',
          'icon' => 'user-plus',
          'permission' => 'student.quota.view',
        ),
        'student/student-award' => 
        array (
          'label' => 'Student Award',
          'url' => '/student/student-award',
          'icon' => 'award',
          'permission' => 'student.award.view',
        ),
        'student/qualification' => 
        array (
          'label' => 'Qualification',
          'url' => '/student/qualification',
          'icon' => 'shield-check',
          'permission' => 'student.qualification.view',
        ),
        'student/exchange' => 
        array (
          'label' => 'Student Exchange',
          'url' => '/student/exchange',
          'icon' => 'shuffle',
          'permission' => 'student.exchange.view',
        ),
        'student/olympiad' => 
        array (
          'label' => 'Student Olympiad',
          'url' => '/student/olympiad',
          'icon' => 'trophy',
          'permission' => 'student.olympiad.view',
        ),
        'student/sport' => 
        array (
          'label' => 'Student Sport',
          'url' => '/student/sport',
          'icon' => 'dumbbell',
          'permission' => 'student.sport.view',
        ),
        'student/foreign-certificate' => 
        array (
          'label' => 'Foreign Certificate',
          'url' => '/student/foreign-certificate',
          'icon' => 'file-badge',
          'permission' => 'student.certificate.view',
        ),
      ),
    ),
    'transfer' => 
    array (
      'icon' => 'user-cog',
      'label' => 'Student Status',
      'url' => '/decree/index',
      'permission' => 'transfer.view',
      'order' => 15,
      'items' => 
      array (
        'decree/index' => 
        array (
          'label' => 'Decrees',
          'url' => '/decree/index',
          'icon' => 'file-text',
          'permission' => 'decree.view',
        ),
        'decree/edu-decree' => 
        array (
          'label' => 'Edu Decree',
          'url' => '/decree/edu-decree',
          'icon' => 'scroll-text',
          'permission' => 'decree.view',
        ),
        'decree/template' => 
        array (
          'label' => 'Templates',
          'url' => '/decree/template',
          'icon' => 'file-type',
          'permission' => 'decree.template.view',
        ),
        'decree/disciplinary' => 
        array (
          'label' => 'Disciplinary',
          'url' => '/decree/disciplinary',
          'icon' => 'gavel',
          'permission' => 'decree.disciplinary.view',
        ),
        'transfer/student-group' => 
        array (
          'label' => 'Transfer Student Group',
          'url' => '/transfer/student-group',
          'icon' => 'arrow-right-left',
          'permission' => 'transfer.group.view',
        ),
        'transfer/student-course-transfer' => 
        array (
          'label' => 'Transfer (Course)',
          'url' => '/transfer/student-course-transfer',
          'icon' => 'arrow-up',
          'permission' => 'transfer.course.view',
        ),
        'transfer/student-course-expel' => 
        array (
          'label' => 'Expel (Course / Semester)',
          'url' => '/transfer/student-course-expel',
          'icon' => 'user-x',
          'permission' => 'transfer.expel.view',
        ),
        'transfer/student-expel' => 
        array (
          'label' => 'Expel',
          'url' => '/transfer/student-expel',
          'icon' => 'user-x',
          'permission' => 'transfer.expel.view',
        ),
        'transfer/student-remove' => 
        array (
          'label' => 'Student Remove',
          'url' => '/transfer/student-remove',
          'icon' => 'user-minus',
          'permission' => 'transfer.remove.view',
        ),
        'transfer/academic-mobile' => 
        array (
          'label' => 'Transfer Academic Mobile',
          'url' => '/transfer/academic-mobile',
          'icon' => 'plane',
          'permission' => 'transfer.mobile.view',
        ),
        'transfer/academic-leave' => 
        array (
          'label' => 'Academic leave',
          'url' => '/transfer/academic-leave',
          'icon' => 'pause',
          'permission' => 'transfer.leave.view',
        ),
        'transfer/restore' => 
        array (
          'label' => 'Transfer Restore',
          'url' => '/transfer/restore',
          'icon' => 'refresh-cw',
          'permission' => 'transfer.restore.view',
        ),
        'transfer/return' => 
        array (
          'label' => 'Transfer Return',
          'url' => '/transfer/return',
          'icon' => 'corner-down-left',
          'permission' => 'transfer.return.view',
        ),
        'transfer/graduate' => 
        array (
          'label' => 'Graduate',
          'url' => '/transfer/graduate',
          'icon' => 'graduation-cap',
          'permission' => 'transfer.graduate.view',
        ),
        'transfer/graduate-simple' => 
        array (
          'label' => 'Graduate Simple',
          'url' => '/transfer/graduate-simple',
          'icon' => 'check-circle',
          'permission' => 'transfer.graduate.view',
        ),
        'transfer/graduate-status' => 
        array (
          'label' => 'Graduate Status',
          'url' => '/transfer/graduate-status',
          'icon' => 'badge-check',
          'permission' => 'transfer.graduate.view',
        ),
        'transfer/status' => 
        array (
          'label' => 'Transfer Status',
          'url' => '/transfer/status',
          'icon' => 'list',
          'permission' => 'transfer.status.view',
        ),
      ),
    ),
    'subjects' => 
    array (
      'icon' => 'database',
      'label' => 'Subjects',
      'url' => '/curriculum/subject',
      'permission' => 'subject.view',
      'order' => 6,
      'items' => 
      array (
        'curriculum/subject-group' => 
        array (
          'label' => 'Subject Groups',
          'url' => '/curriculum/subject-group',
          'icon' => 'folder',
          'permission' => 'subject.group.view',
        ),
        'curriculum/subject' => 
        array (
          'label' => 'Subject',
          'url' => '/curriculum/subject',
          'icon' => 'book',
          'permission' => 'subject.view',
        ),
        'teacher/subject-topics' => 
        array (
          'label' => 'Subject Topics',
          'url' => '/teacher/subject-topics',
          'icon' => 'list-tree',
          'permission' => 'subject.topics.view',
        ),
        'file-resource/index' => 
        array (
          'label' => 'File Resource Index',
          'url' => '/file-resource/index',
          'icon' => 'folder-open',
          'permission' => 'file-resource.view',
        ),
        'teacher/subject-tasks' => 
        array (
          'label' => 'Subject Tasks',
          'url' => '/teacher/subject-tasks',
          'icon' => 'clipboard-check',
          'permission' => 'subject.tasks.view',
        ),
        'teacher/subject-task-other' => 
        array (
          'label' => 'Subject Tasks Other',
          'url' => '/teacher/subject-task-other',
          'icon' => 'clipboard-list',
          'permission' => 'subject.tasks.view',
        ),
        'teacher/calendar-plan' => 
        array (
          'label' => 'Calendar Plan',
          'url' => '/teacher/calendar-plan',
          'icon' => 'calendar',
          'permission' => 'teacher.calendar.view',
        ),
        'credit/subject-teacher' => 
        array (
          'label' => 'Subject Teacher',
          'url' => '/credit/subject-teacher',
          'icon' => 'user-check',
          'permission' => 'subject.teacher.view',
        ),
        'credit/subject-choose' => 
        array (
          'label' => 'Subject Choose',
          'url' => '/credit/subject-choose',
          'icon' => 'mouse-pointer-click',
          'permission' => 'subject.choose.view',
        ),
        'teacher/subject-info' => 
        array (
          'label' => 'Subject Info',
          'url' => '/teacher/subject-info',
          'icon' => 'info',
          'permission' => 'subject.info.view',
        ),
      ),
    ),
    'curriculum' => 
    array (
      'icon' => 'book-open',
      'label' => 'Curriculum Process',
      'url' => '/curriculum/education-year',
      'permission' => 'curriculum.view',
      'order' => 7,
      'items' => 
      array (
        'curriculum/education-year' => 
        array (
          'label' => 'Education Year',
          'url' => '/curriculum/education-year',
          'icon' => 'calendar-range',
          'permission' => 'curriculum.year.view',
        ),
        'curriculum/curriculum' => 
        array (
          'label' => 'Curriculum',
          'url' => '/curriculum/curriculum',
          'icon' => 'book-open',
          'permission' => 'curriculum.view',
        ),
        'curriculum/curriculum-list' => 
        array (
          'label' => 'Curriculum List',
          'url' => '/curriculum/curriculum-list',
          'icon' => 'list',
          'permission' => 'curriculum.list.view',
        ),
        'curriculum/semester' => 
        array (
          'label' => 'Education Semester',
          'url' => '/curriculum/semester',
          'icon' => 'calendar-fold',
          'permission' => 'curriculum.semester.view',
        ),
        'curriculum/curriculum-block' => 
        array (
          'label' => 'Subject Block',
          'url' => '/curriculum/curriculum-block',
          'icon' => 'boxes',
          'permission' => 'curriculum.block.view',
        ),
        'curriculum/student-register' => 
        array (
          'label' => 'Student Register',
          'url' => '/curriculum/student-register',
          'icon' => 'user-plus',
          'permission' => 'curriculum.register.view',
        ),
        'transfer/subject-register' => 
        array (
          'label' => 'Transfer Subject Register',
          'url' => '/transfer/subject-register',
          'icon' => 'book-plus',
          'permission' => 'curriculum.register.view',
        ),
        'curriculum/student-subjects-register' => 
        array (
          'label' => 'Student Subjects Register',
          'url' => '/curriculum/student-subjects-register',
          'icon' => 'clipboard-pen',
          'permission' => 'curriculum.register.view',
        ),
        'curriculum/schedule' => 
        array (
          'label' => 'Schedule',
          'url' => '/curriculum/schedule-info',
          'icon' => 'calendar-clock',
          'permission' => 'schedule.view',
        ),
        'curriculum/schedule-view' => 
        array (
          'label' => 'Schedule View',
          'url' => '/curriculum/schedule-info-view',
          'icon' => 'eye',
          'permission' => 'schedule.view',
        ),
        'curriculum/exam-schedule' => 
        array (
          'label' => 'Exam Schedule',
          'url' => '/curriculum/exam-schedule-info',
          'icon' => 'calendar-check',
          'permission' => 'exam.schedule.view',
        ),
        'curriculum/exam-schedule-view' => 
        array (
          'label' => 'Exam Schedule View',
          'url' => '/curriculum/exam-schedule-info-view',
          'icon' => 'search',
          'permission' => 'exam.schedule.view',
        ),
        'curriculum/marking-system' => 
        array (
          'label' => 'Marking System',
          'url' => '/curriculum/marking-system',
          'icon' => 'pencil-ruler',
          'permission' => 'curriculum.marking.view',
        ),
        'curriculum/grade-type' => 
        array (
          'label' => 'Grade Type',
          'url' => '/curriculum/grade-type',
          'icon' => 'badge',
          'permission' => 'curriculum.grade.view',
        ),
        'curriculum/rating-grade' => 
        array (
          'label' => 'Rating Grade',
          'url' => '/curriculum/rating-grade',
          'icon' => 'star',
          'permission' => 'curriculum.rating.view',
        ),
        'curriculum/lesson-pair' => 
        array (
          'label' => 'Lesson Pair',
          'url' => '/curriculum/lesson-pair',
          'icon' => 'clock',
          'permission' => 'curriculum.lesson.view',
        ),
        'exam/index' => 
        array (
          'label' => 'Exams',
          'url' => '/exam/index',
          'icon' => 'file-text',
          'permission' => 'exam.view',
        ),
      ),
    ),
    'individual-training' => 
    array (
      'icon' => 'book-marked',
      'label' => 'Individual Trainings',
      'url' => '/individual-training/subject-teacher',
      'permission' => 'individual-training.view',
      'order' => 8,
      'items' => 
      array (
        'individual-training/subject-teacher' => 
        array (
          'label' => 'Subject Teachers',
          'url' => '/individual-training/subject-teacher',
          'icon' => 'user-check',
          'permission' => 'individual-training.teacher.view',
        ),
        'individual-training/subject-student' => 
        array (
          'label' => 'Subject Students',
          'url' => '/individual-training/subject-student',
          'icon' => 'users',
          'permission' => 'individual-training.student.view',
        ),
        'individual-training/subject-schedule' => 
        array (
          'label' => 'Subject Schedule',
          'url' => '/individual-training/subject-schedule',
          'icon' => 'calendar',
          'permission' => 'individual-training.schedule.view',
        ),
        'individual-training/subject-attendance' => 
        array (
          'label' => 'Subject Attendance',
          'url' => '/individual-training/subject-attendance',
          'icon' => 'clipboard-check',
          'permission' => 'individual-training.attendance.view',
        ),
      ),
    ),
    'retraining' => 
    array (
      'icon' => 'graduation-cap',
      'label' => 'Credit education',
      'url' => '/retraining/retraining',
      'permission' => 'retraining.view',
      'order' => 9,
      'items' => 
      array (
        'retraining/retraining' => 
        array (
          'label' => 'Retraining Register',
          'url' => '/retraining/retraining',
          'icon' => 'book-marked',
          'permission' => 'retraining.view',
        ),
        'retraining/student' => 
        array (
          'label' => 'Student List',
          'url' => '/retraining/student',
          'icon' => 'users',
          'permission' => 'retraining.student.view',
        ),
        'retraining/subject-group' => 
        array (
          'label' => 'Groups of Subjects',
          'url' => '/retraining/subject-group',
          'icon' => 'folder',
          'permission' => 'retraining.subject.view',
        ),
        'retraining/student-register' => 
        array (
          'label' => 'Student Register',
          'url' => '/retraining/student-register',
          'icon' => 'user-plus',
          'permission' => 'retraining.register.view',
        ),
        'retraining/schedule' => 
        array (
          'label' => 'Schedule',
          'url' => '/retraining/schedule-info',
          'icon' => 'calendar-clock',
          'permission' => 'retraining.schedule.view',
        ),
        'retraining/exam-schedule-info' => 
        array (
          'label' => 'Exam Schedule',
          'url' => '/retraining/exam-schedule-info',
          'icon' => 'calendar-check',
          'permission' => 'retraining.exam.view',
        ),
        'retraining/time-table' => 
        array (
          'label' => 'My Timetable',
          'url' => '/retraining/time-table',
          'icon' => 'calendar-days',
          'permission' => 'retraining.timetable.view',
        ),
        'retraining/midterm-exam-table' => 
        array (
          'label' => 'Midterm Examtable',
          'url' => '/retraining/midterm-exam-table',
          'icon' => 'clipboard',
          'permission' => 'retraining.exam.view',
        ),
        'retraining/final-exam-table' => 
        array (
          'label' => 'Final Examtable',
          'url' => '/retraining/final-exam-table',
          'icon' => 'clipboard-check',
          'permission' => 'retraining.exam.view',
        ),
        'retraining/other-exam-table' => 
        array (
          'label' => 'Other Examtable',
          'url' => '/retraining/other-exam-table',
          'icon' => 'clipboard-list',
          'permission' => 'retraining.exam.view',
        ),
        'retraining/performance' => 
        array (
          'label' => 'Performance',
          'url' => '/retraining/performance',
          'icon' => 'trending-up',
          'permission' => 'retraining.performance.view',
        ),
        'retraining/academic-record' => 
        array (
          'label' => 'Academic record',
          'url' => '/retraining/academic-record',
          'icon' => 'file-text',
          'permission' => 'retraining.record.view',
        ),
        'retraining/teacher-subject' => 
        array (
          'label' => 'Teacher Subject',
          'url' => '/retraining/teacher-subject',
          'icon' => 'book-open',
          'permission' => 'retraining.teacher.view',
        ),
      ),
    ),
    'attendance' => 
    array (
      'icon' => 'clipboard-check',
      'label' => 'Attendance',
      'url' => '/attendance/attendance-journal',
      'permission' => 'attendance.view',
      'order' => 10,
      'items' => 
      array (
        'attendance/attendance-journal' => 
        array (
          'label' => 'Journal',
          'url' => '/attendance/attendance-journal',
          'icon' => 'notebook',
          'permission' => 'attendance.view',
        ),
        'attendance/activity' => 
        array (
          'label' => 'Attendance Activity ',
          'url' => '/attendance/activity',
          'icon' => 'activity',
          'permission' => 'attendance.view',
        ),
        'attendance/report' => 
        array (
          'label' => 'Attendance Report ',
          'url' => '/attendance/report',
          'icon' => 'file-bar-chart',
          'permission' => 'attendance.view',
        ),
        'attendance/overall' => 
        array (
          'label' => 'Overall',
          'url' => '/attendance/overall',
          'icon' => 'pie-chart',
          'permission' => 'attendance.view',
        ),
        'attendance/by-subjects' => 
        array (
          'label' => 'Subjects',
          'url' => '/attendance/by-subjects',
          'icon' => 'book-open',
          'permission' => 'attendance.view',
        ),
        'attendance/attendance-setting' => 
        array (
          'label' => 'Setting',
          'url' => '/attendance/attendance-setting',
          'icon' => 'settings',
          'permission' => 'attendance.setting.view',
        ),
        'attendance/lessons' => 
        array (
          'label' => 'Attendance Lessons',
          'url' => '/attendance/lessons',
          'icon' => 'list',
          'permission' => 'attendance.view',
        ),
        'attendance/by-daily' => 
        array (
          'label' => 'Daily',
          'url' => '/attendance/by-daily',
          'icon' => 'calendar-days',
          'permission' => 'attendance.view',
        ),
        'attendance/stat' => 
        array (
          'label' => 'Attendance Stat',
          'url' => '/attendance/stat',
          'icon' => 'bar-chart',
          'permission' => 'attendance.view',
        ),
      ),
    ),
    'performance' => 
    array (
      'icon' => 'chart-line',
      'label' => 'Performance',
      'url' => '/performance/performance',
      'permission' => 'performance.view',
      'order' => 11,
      'items' => 
      array (
        'performance/performance' => 
        array (
          'label' => 'Performance',
          'url' => '/performance/performance',
          'icon' => 'trending-up',
          'permission' => 'performance.view',
        ),
        'performance/summary' => 
        array (
          'label' => 'Summary',
          'url' => '/performance/summary',
          'icon' => 'file-text',
          'permission' => 'performance.view',
        ),
        'performance/debtors' => 
        array (
          'label' => 'Debtors',
          'url' => '/performance/debtors',
          'icon' => 'alert-triangle',
          'permission' => 'performance.view',
        ),
        'performance/debtors-all' => 
        array (
          'label' => 'Debtors All',
          'url' => '/performance/debtors-all',
          'icon' => 'alert-octagon',
          'permission' => 'performance.view',
        ),
        'performance/gpa' => 
        array (
          'label' => 'Performance Gpa',
          'url' => '/performance/gpa',
          'icon' => 'award',
          'permission' => 'gpa.view',
        ),
        'performance/ptt' => 
        array (
          'label' => 'Performance Ptt',
          'url' => '/performance/ptt',
          'icon' => 'clipboard',
          'permission' => 'performance.ptt.view',
        ),
        'performance/ptt-check' => 
        array (
          'label' => 'Performance Ptt Check',
          'url' => '/performance/ptt-check',
          'icon' => 'clipboard-check',
          'permission' => 'performance.ptt.view',
        ),
        'performance/ptt-fill' => 
        array (
          'label' => 'Performance Ptt Fill',
          'url' => '/performance/ptt-fill',
          'icon' => 'pen-tool',
          'permission' => 'performance.ptt.view',
        ),
        'performance/appeal' => 
        array (
          'label' => 'Performance Appeal',
          'url' => '/performance/appeal',
          'icon' => 'message-square',
          'permission' => 'performance.appeal.view',
        ),
      ),
    ),
    'infrastructure' => 
    array (
      'icon' => 'building-2',
      'label' => 'Infrastructure',
      'url' => '/infrastructure/building',
      'permission' => 'infrastructure.view',
      'order' => 12,
      'items' => 
      array (
        'infrastructure/building' => 
        array (
          'label' => 'Building',
          'url' => '/infrastructure/building',
          'icon' => 'building',
          'permission' => 'infrastructure.building.view',
        ),
        'infrastructure/auditorium' => 
        array (
          'label' => 'Auditorium',
          'url' => '/infrastructure/auditorium',
          'icon' => 'door-open',
          'permission' => 'infrastructure.auditorium.view',
        ),
        'infrastructure/inventory' => 
        array (
          'label' => 'Infrastructure Inventory',
          'url' => '/infrastructure/inventory',
          'icon' => 'package',
          'permission' => 'infrastructure.inventory.view',
        ),
        'report/literatures' => 
        array (
          'label' => 'Report Literatures',
          'url' => '/report/literatures',
          'icon' => 'book',
          'permission' => 'report.literatures.view',
        ),
        'report/laboratories' => 
        array (
          'label' => 'Report Laboratories',
          'url' => '/report/laboratories',
          'icon' => 'flask',
          'permission' => 'report.laboratories.view',
        ),
        'report/projectors' => 
        array (
          'label' => 'Report Projectors',
          'url' => '/report/projectors',
          'icon' => 'monitor',
          'permission' => 'report.projectors.view',
        ),
      ),
    ),
    'teacher-attendance' => 
    array (
      'icon' => 'calendar-days',
      'label' => 'Trainings',
      'url' => '/teacher/time-table',
      'permission' => 'teacher.trainings.view',
      'order' => 13,
      'items' => 
      array (
        'teacher/time-table' => 
        array (
          'label' => 'My Timetable',
          'url' => '/teacher/time-table',
          'icon' => 'calendar-clock',
          'permission' => 'teacher.timetable.view',
        ),
        'teacher/training-list' => 
        array (
          'label' => 'Training List',
          'url' => '/teacher/training-list',
          'icon' => 'list',
          'permission' => 'teacher.trainings.view',
        ),
        'teacher/attendance-journal' => 
        array (
          'label' => 'Attendance Journal',
          'url' => '/teacher/attendance-journal',
          'icon' => 'notebook',
          'permission' => 'teacher.attendance.view',
        ),
        'teacher/rating-journal' => 
        array (
          'label' => 'Rating Journal',
          'url' => '/teacher/rating-journal',
          'icon' => 'star',
          'permission' => 'teacher.rating.view',
        ),
      ),
    ),
    'teacher-examtable' => 
    array (
      'icon' => 'award',
      'label' => 'Rating Grades',
      'url' => '/teacher/midterm-exam-table',
      'permission' => 'teacher.examtable.view',
      'order' => 14,
      'items' => 
      array (
        'teacher/midterm-exam-table' => 
        array (
          'label' => 'Midterm Examtable',
          'url' => '/teacher/midterm-exam-table',
          'icon' => 'clipboard',
          'permission' => 'teacher.exam.view',
        ),
        'teacher/final-exam-table' => 
        array (
          'label' => 'Final Examtable',
          'url' => '/teacher/final-exam-table',
          'icon' => 'clipboard-check',
          'permission' => 'teacher.exam.view',
        ),
        'teacher/other-exam-table' => 
        array (
          'label' => 'Other Examtable',
          'url' => '/teacher/other-exam-table',
          'icon' => 'clipboard-list',
          'permission' => 'teacher.exam.view',
        ),
        'teacher/certificate-committee-result' => 
        array (
          'label' => 'Certificate Committee Result',
          'url' => '/teacher/certificate-committee-result',
          'icon' => 'award',
          'permission' => 'teacher.certificate.view',
        ),
      ),
    ),
    'archive' => 
    array (
      'icon' => 'archive',
      'label' => 'Archive',
      'url' => '/archive/academic-record',
      'permission' => 'archive.view',
      'order' => 15,
      'items' => 
      array (
        'archive/academic-record' => 
        array (
          'label' => 'Academic record',
          'url' => '/archive/academic-record',
          'icon' => 'file-text',
          'permission' => 'archive.record.view',
        ),
        'archive/diploma' => 
        array (
          'label' => 'Diploma Registration',
          'url' => '/archive/diploma',
          'icon' => 'scroll',
          'permission' => 'archive.diploma.view',
        ),
        'archive/diploma-simple' => 
        array (
          'label' => 'Simple Diploma Registration',
          'url' => '/archive/diploma-simple',
          'icon' => 'file-check',
          'permission' => 'archive.diploma.view',
        ),
        'archive/diploma-blank' => 
        array (
          'label' => 'Diploma Blank',
          'url' => '/archive/diploma-blank',
          'icon' => 'file',
          'permission' => 'archive.diploma.view',
        ),
        'archive/diploma-list' => 
        array (
          'label' => 'Diploma List',
          'url' => '/archive/diploma-list',
          'icon' => 'list',
          'permission' => 'archive.diploma.view',
        ),
        'archive/transcript' => 
        array (
          'label' => 'Academic Information',
          'url' => '/archive/transcript',
          'icon' => 'info',
          'permission' => 'archive.transcript.view',
        ),
        'archive/academic-information-data' => 
        array (
          'label' => 'Academic Information Data',
          'url' => '/archive/academic-information-data',
          'icon' => 'database',
          'permission' => 'archive.info.view',
        ),
        'archive/accreditation' => 
        array (
          'label' => 'Accreditation',
          'url' => '/archive/accreditation',
          'icon' => 'shield-check',
          'permission' => 'archive.accreditation.view',
        ),
        'archive/batch-rate' => 
        array (
          'label' => 'Batch Rate',
          'url' => '/archive/batch-rate',
          'icon' => 'layers',
          'permission' => 'archive.batch.view',
        ),
        'archive/employment' => 
        array (
          'label' => 'Graduate Employment',
          'url' => '/archive/employment',
          'icon' => 'briefcase',
          'permission' => 'archive.employment.view',
        ),
        'archive/certificate-committee' => 
        array (
          'label' => 'Certificate Committee',
          'url' => '/archive/certificate-committee',
          'icon' => 'users',
          'permission' => 'archive.certificate.view',
        ),
        'archive/graduate-work' => 
        array (
          'label' => 'Graduate Work',
          'url' => '/archive/graduate-work',
          'icon' => 'book-open',
          'permission' => 'archive.graduate.view',
        ),
        'archive/reference' => 
        array (
          'label' => 'Archive Reference',
          'url' => '/archive/reference',
          'icon' => 'file-text',
          'permission' => 'archive.reference.view',
        ),
        'archive/certificate' => 
        array (
          'label' => 'Certificate',
          'url' => '/archive/certificate',
          'icon' => 'award',
          'permission' => 'archive.certificate.view',
        ),
      ),
    ),
    'science' => 
    array (
      'icon' => 'flask',
      'label' => 'Science',
      'url' => '/science/project',
      'permission' => 'science.view',
      'order' => 16,
      'items' => 
      array (
        'science/project' => 
        array (
          'label' => 'Science Project',
          'url' => '/science/project',
          'icon' => 'lightbulb',
          'permission' => 'science.project.view',
        ),
        'science/publication-methodical' => 
        array (
          'label' => 'Science Methodical Publication',
          'url' => '/science/publication-methodical',
          'icon' => 'book',
          'permission' => 'science.publication.view',
        ),
        'science/publication-scientifical' => 
        array (
          'label' => 'Science Publication Scientifical',
          'url' => '/science/publication-scientifical',
          'icon' => 'newspaper',
          'permission' => 'science.publication.view',
        ),
        'science/publication-property' => 
        array (
          'label' => 'Science Publication Property',
          'url' => '/science/publication-property',
          'icon' => 'copyright',
          'permission' => 'science.property.view',
        ),
        'science/scientific-activity' => 
        array (
          'label' => 'Science Scientific Activity',
          'url' => '/science/scientific-activity',
          'icon' => 'microscope',
          'permission' => 'science.activity.view',
        ),
        'science/publication-methodical-check' => 
        array (
          'label' => 'Science Methodical Check',
          'url' => '/science/publication-methodical-check',
          'icon' => 'book-check',
          'permission' => 'science.check.view',
        ),
        'science/publication-scientifical-check' => 
        array (
          'label' => 'Science Scientifical Check',
          'url' => '/science/publication-scientifical-check',
          'icon' => 'search-check',
          'permission' => 'science.check.view',
        ),
        'science/publication-property-check' => 
        array (
          'label' => 'Science Property Check',
          'url' => '/science/publication-property-check',
          'icon' => 'shield-check',
          'permission' => 'science.check.view',
        ),
        'science/scientific-activity-check' => 
        array (
          'label' => 'Science Activity Check',
          'url' => '/science/scientific-activity-check',
          'icon' => 'check-square',
          'permission' => 'science.check.view',
        ),
      ),
    ),
    'rating' => 
    array (
      'icon' => 'trending-up',
      'label' => 'Rating',
      'url' => '/science/criteria-template',
      'permission' => 'rating.view',
      'order' => 17,
      'items' => 
      array (
        'science/criteria-template' => 
        array (
          'label' => 'Rating Criteria Template',
          'url' => '/science/criteria-template',
          'icon' => 'file-type',
          'permission' => 'rating.criteria.view',
        ),
        'science/publication-criteria' => 
        array (
          'label' => 'Science Publication Criteria',
          'url' => '/science/publication-criteria',
          'icon' => 'book-marked',
          'permission' => 'rating.criteria.view',
        ),
        'science/scientific-activity-criteria' => 
        array (
          'label' => 'Science Scientific Activity',
          'url' => '/science/scientific-activity-criteria',
          'icon' => 'microscope',
          'permission' => 'rating.criteria.view',
        ),
        'science/teacher-rating' => 
        array (
          'label' => 'Rating Teacher',
          'url' => '/science/teacher-rating',
          'icon' => 'user-check',
          'permission' => 'rating.teacher.view',
        ),
        'science/department-rating' => 
        array (
          'label' => 'Rating Department',
          'url' => '/science/department-rating',
          'icon' => 'briefcase',
          'permission' => 'rating.department.view',
        ),
        'science/faculty-rating' => 
        array (
          'label' => 'Rating Faculty',
          'url' => '/science/faculty-rating',
          'icon' => 'building',
          'permission' => 'rating.faculty.view',
        ),
      ),
    ),
    'doctorate' => 
    array (
      'icon' => 'graduation-cap',
      'label' => 'Doctorate',
      'url' => '/science/specialty',
      'permission' => 'doctorate.view',
      'order' => 18,
      'items' => 
      array (
        'science/doctorate-specialty' => 
        array (
          'label' => 'Doctorate Specialty',
          'url' => '/science/specialty',
          'icon' => 'award',
          'permission' => 'doctorate.specialty.view',
        ),
        'science/doctorate-student' => 
        array (
          'label' => 'Doctorate Student',
          'url' => '/science/doctorate-student',
          'icon' => 'user-round',
          'permission' => 'doctorate.student.view',
        ),
      ),
    ),
    'finance' => 
    array (
      'icon' => 'banknote',
      'label' => 'Finance',
      'url' => '/finance/minimum-wage',
      'permission' => 'finance.view',
      'order' => 19,
      'items' => 
      array (
        'finance/minimum-wage' => 
        array (
          'label' => 'Minimum Wage',
          'url' => '/finance/minimum-wage',
          'icon' => 'dollar-sign',
          'permission' => 'finance.wage.view',
        ),
        'finance/scholarship-amount' => 
        array (
          'label' => 'Amount of Scholarship',
          'url' => '/finance/scholarship-amount',
          'icon' => 'coins',
          'permission' => 'finance.scholarship.view',
        ),
        'finance/bank-details' => 
        array (
          'label' => 'Bank Details',
          'url' => '/finance/bank-details',
          'icon' => 'landmark',
          'permission' => 'finance.bank.view',
        ),
        'finance/contract-template' => 
        array (
          'label' => 'Contract Template',
          'url' => '/finance/contract-template',
          'icon' => 'file-text',
          'permission' => 'finance.contract.view',
        ),
        'finance/contract-type' => 
        array (
          'label' => 'Type of Contract',
          'url' => '/finance/contract-type',
          'icon' => 'file-type',
          'permission' => 'finance.contract.view',
        ),
        'finance/contract-price' => 
        array (
          'label' => 'Contract Price',
          'url' => '/finance/contract-price',
          'icon' => 'tag',
          'permission' => 'finance.contract.view',
        ),
        'finance/contract-price-foreign' => 
        array (
          'label' => ' Foreign Contract Price',
          'url' => '/finance/contract-price-foreign',
          'icon' => 'tags',
          'permission' => 'finance.contract.view',
        ),
        'finance/increased-contract-coef' => 
        array (
          'label' => 'Coefficient of Increased Contract',
          'url' => '/finance/increased-contract-coef',
          'icon' => 'percent',
          'permission' => 'finance.contract.view',
        ),
        'finance/uzasbo-data' => 
        array (
          'label' => 'Student Uzasbo',
          'url' => '/finance/uzasbo-data',
          'icon' => 'database',
          'permission' => 'finance.uzasbo.view',
        ),
        'finance/student-contract' => 
        array (
          'label' => 'Student Contract',
          'url' => '/finance/student-contract',
          'icon' => 'file-signature',
          'permission' => 'finance.contract.view',
        ),
        'finance/contract-other' => 
        array (
          'label' => 'Contract Other',
          'url' => '/finance/contract-other',
          'icon' => 'file-text',
          'permission' => 'finance.contract.view',
        ),
        'finance/payment-monitoring' => 
        array (
          'label' => 'Payment monitoring',
          'url' => '/finance/payment-monitoring',
          'icon' => 'eye',
          'permission' => 'finance.payment.view',
        ),
        'finance/control-contract' => 
        array (
          'label' => 'Control Contract',
          'url' => '/finance/control-contract',
          'icon' => 'shield-check',
          'permission' => 'finance.contract.view',
        ),
        'finance/student-contract-manual' => 
        array (
          'label' => 'Student Contract Manual',
          'url' => '/finance/student-contract-manual',
          'icon' => 'hand',
          'permission' => 'finance.contract.view',
        ),
        'finance/contract-invoice' => 
        array (
          'label' => 'Contract Invoice',
          'url' => '/finance/contract-invoice',
          'icon' => 'receipt',
          'permission' => 'finance.invoice.view',
        ),
        'finance/payment-monitoring-department' => 
        array (
          'label' => 'Payment Monitoring Department',
          'url' => '/finance/payment-monitoring-department',
          'icon' => 'building-2',
          'permission' => 'finance.payment.view',
        ),
      ),
    ),
    'scholarship' => 
    array (
      'icon' => 'coins',
      'label' => 'Scholarship',
      'url' => '/finance/scholarship',
      'permission' => 'scholarship.view',
      'order' => 20,
      'items' => 
      array (
        'finance/scholarship' => 
        array (
          'label' => 'Scholarship',
          'url' => '/finance/scholarship',
          'icon' => 'coins',
          'permission' => 'scholarship.view',
        ),
        'finance/scholarship-cancel' => 
        array (
          'label' => 'Finance Scholarship Cancel',
          'url' => '/finance/scholarship-cancel',
          'icon' => 'x-circle',
          'permission' => 'scholarship.cancel.view',
        ),
        'finance/scholarship-protocol' => 
        array (
          'label' => 'Scholarship Protocol',
          'url' => '/finance/scholarship-protocol',
          'icon' => 'file-text',
          'permission' => 'scholarship.protocol.view',
        ),
        'finance/scholarship-protocol-check' => 
        array (
          'label' => 'Scholarship Protocol Check',
          'url' => '/finance/scholarship-protocol-check',
          'icon' => 'file-check',
          'permission' => 'scholarship.protocol.view',
        ),
        'decree/decree-info' => 
        array (
          'label' => 'Decree Info',
          'url' => '/decree/decree-info',
          'icon' => 'info',
          'permission' => 'decree.view',
        ),
        'decree/decree-info-agreement' => 
        array (
          'label' => 'Decree Info Agreement',
          'url' => '/decree/decree-info-agreement',
          'icon' => 'file-signature',
          'permission' => 'decree.view',
        ),
        'finance/scholarship-protocol-send' => 
        array (
          'label' => 'Scholarship Protocol Send',
          'url' => '/finance/scholarship-protocol-send',
          'icon' => 'send',
          'permission' => 'scholarship.protocol.view',
        ),
      ),
    ),
    'statistical' => 
    array (
      'icon' => 'bar-chart-3',
      'label' => 'Statistical',
      'url' => '/dashboard/open-data',
      'permission' => 'statistical.view',
      'order' => 21,
      'items' => 
      array (
        'dashboard/stat' => 
        array (
          'label' => 'Dashboard Open Data',
          'url' => '/dashboard/open-data',
          'icon' => 'bar-chart',
          'permission' => 'statistical.view',
        ),
        'statistical/by-student' => 
        array (
          'label' => 'By Student',
          'url' => '/statistical/by-student',
          'icon' => 'user',
          'permission' => 'statistical.view',
        ),
        'statistical/by-student-general' => 
        array (
          'label' => 'By Student General',
          'url' => '/statistical/by-student-general',
          'icon' => 'users',
          'permission' => 'statistical.view',
        ),
        'statistical/by-student-social' => 
        array (
          'label' => 'By Student Social',
          'url' => '/statistical/by-student-social',
          'icon' => 'user-round',
          'permission' => 'statistical.view',
        ),
        'statistical/by-teacher' => 
        array (
          'label' => 'By Teacher',
          'url' => '/statistical/by-teacher',
          'icon' => 'user-check',
          'permission' => 'statistical.view',
        ),
        'file-resource/report' => 
        array (
          'label' => 'File Resource Report',
          'url' => '/file-resource/report',
          'icon' => 'folder',
          'permission' => 'file-resource.view',
        ),
        'statistical/by-contract' => 
        array (
          'label' => 'By Contract',
          'url' => '/statistical/by-contract',
          'icon' => 'file-text',
          'permission' => 'statistical.view',
        ),
        'statistical/by-employment' => 
        array (
          'label' => 'By Employment',
          'url' => '/statistical/by-employment',
          'icon' => 'briefcase',
          'permission' => 'statistical.view',
        ),
        'statistical/by-performance' => 
        array (
          'label' => 'By Performance',
          'url' => '/statistical/by-performance',
          'icon' => 'trending-up',
          'permission' => 'statistical.view',
        ),
        'statistical/load-stat' => 
        array (
          'label' => 'By Load Stat',
          'url' => '/statistical/load-stat',
          'icon' => 'package',
          'permission' => 'statistical.view',
        ),
        'statistical/by-science' => 
        array (
          'label' => 'By Science',
          'url' => '/statistical/by-science',
          'icon' => 'flask',
          'permission' => 'statistical.view',
        ),
        'statistical/by-subject-performance' => 
        array (
          'label' => 'By Subject Performance',
          'url' => '/statistical/by-subject-performance',
          'icon' => 'book-open',
          'permission' => 'statistical.view',
        ),
      ),
    ),
    'report' => 
    array (
      'icon' => 'file-bar-chart',
      'label' => 'Report',
      'url' => '/report/by-teachers',
      'permission' => 'report.view',
      'order' => 22,
      'items' => 
      array (
        'report/by-teachers' => 
        array (
          'label' => 'By Teachers',
          'url' => '/report/by-teachers',
          'icon' => 'users',
          'permission' => 'report.view',
        ),
        'report/by-students' => 
        array (
          'label' => 'By Students',
          'url' => '/report/by-students',
          'icon' => 'users-round',
          'permission' => 'report.view',
        ),
        'report/by-resources' => 
        array (
          'label' => 'By Theme Resources',
          'url' => '/report/by-resources',
          'icon' => 'folder',
          'permission' => 'report.view',
        ),
        'report/by-rooms' => 
        array (
          'label' => 'Report By Rooms',
          'url' => '/report/by-rooms',
          'icon' => 'door-open',
          'permission' => 'report.view',
        ),
        'report/teacher-map' => 
        array (
          'label' => 'Report Teacher Map',
          'url' => '/report/teacher-map',
          'icon' => 'map',
          'permission' => 'report.view',
        ),
        'report/by-exam' => 
        array (
          'label' => 'Report By Exam',
          'url' => '/report/by-exam',
          'icon' => 'clipboard-list',
          'permission' => 'report.view',
        ),
        'poll/index' => 
        array (
          'label' => 'Poll Index',
          'url' => '/poll/index',
          'icon' => 'message-circle',
          'permission' => 'poll.view',
        ),
        'poll/mine' => 
        array (
          'label' => 'Poll Mine',
          'url' => '/poll/mine',
          'icon' => 'user-round',
          'permission' => 'poll.view',
        ),
      ),
    ),
    'student-data' => 
    array (
      'icon' => 'cloud-upload',
      'label' => 'External Services',
      'url' => '/student-data/sync',
      'permission' => 'student-data.view',
      'order' => 23,
      'items' => 
      array (
        'student-data/sync' => 
        array (
          'label' => 'Student Data Sync',
          'url' => '/student-data/sync',
          'icon' => 'refresh-cw',
          'permission' => 'student-data.sync.view',
        ),
        'student-data/grant-type' => 
        array (
          'label' => 'Student Data Grant Type',
          'url' => '/student-data/grant-type',
          'icon' => 'award',
          'permission' => 'student-data.grant.view',
        ),
        'student-data/welfare' => 
        array (
          'label' => 'Student Data Welfare',
          'url' => '/student-data/welfare',
          'icon' => 'heart',
          'permission' => 'student-data.welfare.view',
        ),
        'student-data/poverty-level' => 
        array (
          'label' => 'Student Data Poverty Level',
          'url' => '/student-data/poverty-level',
          'icon' => 'alert-circle',
          'permission' => 'student-data.poverty.view',
        ),
        'student-data/women-registry' => 
        array (
          'label' => 'Student Data Woman Registry',
          'url' => '/student-data/woman-registry',
          'icon' => 'user-round',
          'permission' => 'student-data.women.view',
        ),
        'student-data/contract' => 
        array (
          'label' => 'Student Data Contract',
          'url' => '/student-data/contract',
          'icon' => 'file-text',
          'permission' => 'student-data.contract.view',
        ),
        'student-data/stipend' => 
        array (
          'label' => 'Student Data Stipend',
          'url' => '/student-data/stipend',
          'icon' => 'coins',
          'permission' => 'student-data.stipend.view',
        ),
        'student-data/plagiarism' => 
        array (
          'label' => 'Student Data Plagiarism',
          'url' => '/student-data/plagiarism',
          'icon' => 'copy',
          'permission' => 'student-data.plagiarism.view',
        ),
        'student/removal-request' => 
        array (
          'label' => 'Student Removal Request',
          'url' => '/student/removal-request',
          'icon' => 'trash-2',
          'permission' => 'student.removal.view',
        ),
      ),
    ),
    'message' => 
    array (
      'icon' => 'mail',
      'label' => 'Messages',
      'url' => '/message/my-messages',
      'permission' => 'message.view',
      'order' => 24,
      'items' => 
      array (
        'message/index' => 
        array (
          'label' => 'All Messages',
          'url' => '/message/all-messages',
          'icon' => 'inbox',
          'permission' => 'message.view',
        ),
        'message/my-messages' => 
        array (
          'label' => 'My Messages',
          'url' => '/message/my-messages',
          'icon' => 'mail',
          'permission' => 'message.view',
        ),
        'message/compose' => 
        array (
          'label' => 'Compose',
          'url' => '/message/compose',
          'icon' => 'pen-square',
          'permission' => 'message.create',
        ),
      ),
    ),
    'notification' => 
    array (
      'icon' => 'bell',
      'label' => 'Notifications',
      'url' => '/notification/index',
      'permission' => 'notification.view',
      'order' => 25,
      'items' => 
      array (
        'notification/index' => 
        array (
          'label' => 'Notifications',
          'url' => '/notification/index',
          'icon' => 'bell',
          'permission' => 'notification.view',
        ),
      ),
    ),
    'system' => 
    array (
      'icon' => 'settings',
      'label' => 'System',
      'url' => '/system/admin',
      'permission' => 'system.view',
      'order' => 26,
      'items' => 
      array (
        'file-resource/manager' => 
        array (
          'label' => 'File Resource Manager',
          'url' => '/file-resource/manager',
          'icon' => 'folder-cog',
          'permission' => 'file-resource.view',
        ),
        'system/admin' => 
        array (
          'label' => 'Administrators',
          'url' => '/system/admin',
          'icon' => 'shield-plus',
          'permission' => 'admin.view',
        ),
        'system/role' => 
        array (
          'label' => 'Administrator Roles',
          'url' => '/system/role',
          'icon' => 'users-cog',
          'permission' => 'role.view',
        ),
        'system/oauth-client' => 
        array (
          'label' => 'OAuth Clients',
          'url' => '/system/oauth-client',
          'icon' => 'key-round',
          'permission' => 'oauth.view',
        ),
        'system/login' => 
        array (
          'label' => 'Login History',
          'url' => '/system/login',
          'icon' => 'history',
          'permission' => 'system.login.view',
        ),
        'system/system-log' => 
        array (
          'label' => 'System Logs',
          'url' => '/system/system-log',
          'icon' => 'file-text',
          'permission' => 'system.log.view',
        ),
        'system/sync-log' => 
        array (
          'label' => 'Sync Logs',
          'url' => '/system/sync-log',
          'icon' => 'refresh-cw',
          'permission' => 'system.sync.view',
        ),
        'system/sync-status' => 
        array (
          'label' => 'Sync Status',
          'url' => '/system/sync-status',
          'icon' => 'activity',
          'permission' => 'system.sync.view',
        ),
        'system/translation' => 
        array (
          'label' => 'UI Translation',
          'url' => '/system/translation',
          'icon' => 'languages',
          'permission' => 'system.translation.view',
        ),
        'system/configuration' => 
        array (
          'label' => 'Configuration',
          'url' => '/system/configuration',
          'icon' => 'settings-2',
          'permission' => 'system.config.view',
        ),
        'system/classifier' => 
        array (
          'label' => 'Classifiers',
          'url' => '/system/classifier',
          'icon' => 'tags',
          'permission' => 'system.classifier.view',
        ),
        'system/backup' => 
        array (
          'label' => 'Backups',
          'url' => '/system/backup',
          'icon' => 'hard-drive',
          'permission' => 'system.backup.view',
        ),
      ),
    ),
  ),
  'student' => 
  array (
  ),
);
