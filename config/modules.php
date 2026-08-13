<?php

/**
 * School feature modules. Core modules default on; optional modules default off
 * so a day school never sees Hostel until it opts in.
 */
return [
    'core' => [
        'learners' => 'Learners',
        'admissions' => 'Admissions',
        'attendance' => 'Attendance',
        'assessment' => 'Assessment',
        'fees' => 'Fees',
        'sms' => 'SMS',
        'announcements' => 'Announcements',
        'timetable' => 'Timetable',
    ],

    'optional' => [
        'library' => 'Library',
        'hostel' => 'Hostel',
        'transport' => 'Transport',
        'clinic' => 'Clinic',
        'lms' => 'LMS',
        'cbt' => 'CBT',
        'inventory' => 'Inventory',
        'hr' => 'HR',
        'emis' => 'EMIS Support',
        'schoolpay' => 'SchoolPay',
    ],
];
