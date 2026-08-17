<?php

/**
 * School role permissions (tenant) + platform role permissions (operator console).
 * Route middleware must enforce these; navigation hiding is not authorization.
 * Role meaning and data scope: docs/ROLES.md
 */
return [
    'roles' => [
        'school_admin' => [
            'school.manage', 'school.view', 'accounts.manage', 'curriculum.manage',
            'learners.view', 'learners.manage', 'staff.manage',
            'finance.view', 'finance.manage', 'assessment.view', 'assessment.manage', 'assessment.enter',
            'attendance.view', 'attendance.mark', 'attendance.manage', 'promotions.approve', 'timetable.manage',
            'emis.manage', 'sms.send', 'sms.manage', 'announcements.manage',
            'admissions.manage', 'library.manage', 'inventory.manage', 'transport.manage',
            'hostel.manage', 'hr.view', 'hr.manage', 'clinic.manage', 'cbt.manage', 'lms.manage',
            'reports.view',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
        ],
        'director' => [
            // Governance: full visibility, staff appointments, no grade/finance/attendance writes.
            'school.view', 'staff.manage', 'learners.view',
            'finance.view', 'assessment.view', 'reports.view',
            'attendance.view', 'hr.view', 'announcements.manage',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
        ],
        'head_teacher' => [
            // Operational lead: staff/learners/ops; cannot write grades or fees.
            'school.view', 'staff.manage', 'learners.view', 'learners.manage',
            'finance.view', 'assessment.view', 'reports.view',
            'attendance.view', 'attendance.manage', 'promotions.approve',
            'timetable.manage', 'sms.send', 'announcements.manage', 'hr.view', 'hr.manage',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
        ],
        'deputy_head_teacher' => [
            'school.view', 'staff.manage', 'learners.view', 'learners.manage',
            'finance.view', 'assessment.view', 'reports.view',
            'attendance.view', 'attendance.manage',
            'timetable.manage', 'sms.send', 'announcements.manage',
            'hr.view', 'hr.manage',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
        ],
        'director_of_studies' => [
            // Academic lead: curriculum + assessment school-wide; no finance or HR.
            'school.view', 'curriculum.manage', 'learners.view', 'learners.manage',
            'assessment.view', 'assessment.manage', 'assessment.enter', 'reports.view',
            'attendance.view', 'attendance.manage', 'timetable.manage',
            'announcements.manage', 'lms.manage', 'cbt.manage', 'sms.send',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'bursar' => [
            'finance.view', 'finance.manage', 'fees.record', 'fees.report', 'sms.send',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'class_teacher' => [
            // Homeroom: view class reports — not unrestricted mark entry.
            'attendance.view', 'attendance.mark', 'assessment.view', 'class.view',
            'learners.view', 'sms.send', 'self.timetable.view',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'subject_teacher' => [
            // Enter is further scoped to teaching_assignments (class + subject).
            'assessment.enter', 'assessment.view', 'marksheet.submit', 'lms.manage',
            'attendance.view', 'attendance.mark', 'learners.view', 'self.timetable.view',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'parent' => [
            'child.results.view', 'child.fees.view', 'fees.pay', 'self.timetable.view',
            'announcements.view',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'student' => [
            'self.results.view', 'self.fees.view', 'self.timetable.view', 'lms.view', 'cbt.take',
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
