<?php

return ['roles' => [
    'school_admin' => [
        'school.manage', 'accounts.manage', 'learners.manage', 'staff.manage',
        'finance.view', 'finance.manage', 'assessment.view', 'assessment.manage', 'assessment.enter',
        'attendance.view', 'attendance.mark', 'promotions.approve', 'timetable.manage',
        'emis.manage', 'sms.send', 'sms.manage', 'announcements.manage',
        'admissions.manage', 'library.manage', 'inventory.manage', 'transport.manage',
        'hostel.manage', 'hr.manage', 'clinic.manage', 'cbt.manage', 'lms.manage',
    ],
    'director' => [
        'school.view', 'finance.view', 'assessment.view', 'reports.view', 'sms.send',
        'staff.manage', 'learners.manage', 'attendance.view', 'promotions.approve',
        'announcements.manage',
    ],
    'head_teacher' => [
        'school.view', 'staff.manage', 'assessment.manage', 'assessment.enter', 'promotions.approve',
        'attendance.view', 'attendance.mark', 'timetable.manage', 'sms.send',
        'learners.manage', 'announcements.manage',
    ],
    'deputy_head_teacher' => [
        'school.view', 'staff.manage', 'assessment.manage', 'assessment.enter', 'attendance.view',
        'attendance.mark', 'timetable.manage', 'sms.send', 'learners.manage',
    ],
    'bursar' => [
        'finance.manage', 'fees.record', 'fees.report', 'sms.send',
    ],
    'class_teacher' => [
        'attendance.mark', 'assessment.enter', 'class.view', 'sms.send', 'learners.manage',
    ],
    'subject_teacher' => [
        'assessment.enter', 'marksheet.submit', 'lms.manage',
    ],
    'parent' => [
        'child.results.view', 'child.fees.view', 'fees.pay', 'self.timetable.view',
    ],
    'student' => [
        'self.results.view', 'self.timetable.view', 'lms.view', 'cbt.take',
    ],
]];
