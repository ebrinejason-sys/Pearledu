<?php

/**
 * School role permissions (tenant) + platform role permissions (operator console).
 * Route middleware must enforce these; navigation hiding is not authorization.
 */
return [
    'roles' => [
        'school_admin' => [
            'school.manage', 'accounts.manage', 'learners.manage', 'staff.manage',
            'finance.view', 'finance.manage', 'assessment.view', 'assessment.manage', 'assessment.enter',
            'attendance.view', 'attendance.mark', 'promotions.approve', 'timetable.manage',
            'emis.manage', 'sms.send', 'sms.manage', 'announcements.manage',
            'admissions.manage', 'library.manage', 'inventory.manage', 'transport.manage',
            'hostel.manage', 'hr.manage', 'clinic.manage', 'cbt.manage', 'lms.manage',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
        ],
        'director' => [
            'school.view', 'finance.view', 'finance.manage', 'assessment.view', 'reports.view', 'sms.send',
            'staff.manage', 'learners.manage', 'attendance.view', 'attendance.mark', 'promotions.approve',
            'announcements.manage',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
        ],
        'head_teacher' => [
            'school.view', 'staff.manage', 'assessment.manage', 'assessment.enter', 'promotions.approve',
            'attendance.view', 'attendance.mark', 'timetable.manage', 'sms.send',
            'learners.manage', 'announcements.manage',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
        ],
        'deputy_head_teacher' => [
            'school.view', 'staff.manage', 'assessment.manage', 'assessment.enter', 'attendance.view',
            'attendance.mark', 'timetable.manage', 'sms.send', 'learners.manage',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
        ],
        'bursar' => [
            'finance.manage', 'fees.record', 'fees.report', 'sms.send',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'class_teacher' => [
            // View class broadsheet / reports for assigned class — not unrestricted mark entry.
            'attendance.mark', 'assessment.view', 'class.view', 'sms.send', 'learners.manage',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'subject_teacher' => [
            // Enter is further scoped to teaching_assignments (class + subject).
            'assessment.enter', 'assessment.view', 'marksheet.submit', 'lms.manage',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'parent' => [
            'child.results.view', 'child.fees.view', 'fees.pay', 'self.timetable.view',
            'announcements.view',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'student' => [
            'self.results.view', 'self.timetable.view', 'lms.view', 'cbt.take',
            'announcements.view',
            'helpdesk.create', 'helpdesk.view_own',
        ],
    ],

    /**
     * Platform operator permissions. Keys are enforced via middleware `platform.permission:…`.
     */
    'platform_permissions' => [
        'platform.dashboard.view',
        'platform.schools.view',
        'platform.schools.create',
        'platform.schools.update',
        'platform.schools.suspend',
        'platform.schools.delete',
        'platform.schools.enter',
        'platform.schools.enter_suspended',
        'platform.users.impersonate',
        'platform.users.impersonate_write',
        'platform.staff.view',
        'platform.staff.manage',
        'platform.support.view',
        'platform.support.manage',
        'platform.invitations.manage',
        'platform.sms.view',
        'platform.sms.topup',
        'platform.sms.configure',
        'platform.pricing.view',
        'platform.pricing.manage',
        'platform.audit.view',
        'platform.system.view',
        'platform.system.manage',
    ],

    /** @var array<string, list<string>> */
    'platform_roles' => [
        'platform_admin' => ['*'],
        'platform_ops' => [
            'platform.dashboard.view',
            'platform.schools.view',
            'platform.schools.create',
            'platform.schools.update',
            'platform.schools.suspend',
            'platform.schools.enter',
            'platform.staff.view',
            'platform.support.view',
            'platform.support.manage',
            'platform.invitations.manage',
            'platform.sms.view',
            'platform.sms.topup',
            'platform.pricing.view',
        ],
        'emis_data_entrant' => [
            'platform.dashboard.view',
            'platform.schools.view',
            'platform.schools.enter',
        ],
        'support_agent' => [
            'platform.dashboard.view',
            'platform.schools.view',
            'platform.users.impersonate',
            'platform.support.view',
            'platform.support.manage',
            'platform.invitations.manage',
        ],
    ],

    /** Minutes before destructive platform actions require password re-entry. */
    'platform_recent_auth_minutes' => 15,
];
