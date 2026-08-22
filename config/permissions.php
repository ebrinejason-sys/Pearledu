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
            'learners.view', 'learners.manage', 'learner.academic.manage', 'enrollment.manage',
            'staff.manage', 'staff.invite.teacher', 'users.invite.parent',
            'finance.view', 'finance.manage', 'finance.report.view', 'finance.reconcile',
            'fees.structure.manage', 'fees.invoice.create', 'fees.invoice.void',
            'fees.payment.record', 'fees.payment.confirm', 'fees.payment.reject', 'fees.payment.reverse',
            'fees.discount.apply', 'fees.record', 'fees.report',
            'assessment.view', 'assessment.manage', 'assessment.enter', 'marksheet.submit', 'marksheet.verify', 'results.publish',
            'attendance.view', 'attendance.mark', 'attendance.manage', 'promotions.approve', 'timetable.manage',
            'emis.manage', 'sms.send', 'sms.manage', 'announcements.manage',
            'admissions.manage', 'library.manage', 'inventory.manage', 'transport.manage',
            'hostel.manage', 'hr.view', 'hr.manage', 'clinic.manage', 'cbt.manage', 'lms.manage',
            'reports.view',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
            'staff.view', 'staff.messages', 'staff.id.print', 'staff.attendance.view', 'staff.attendance.mark',
            'hr.payroll.view', 'hr.payroll.manage',
        ],
        'director' => [
            // Governance: full visibility, staff appointments, no grade/finance/attendance writes.
            'school.view', 'staff.manage', 'staff.invite.teacher', 'learners.view',
            'finance.view', 'finance.report.view', 'assessment.view', 'reports.view',
            'attendance.view', 'hr.view', 'announcements.manage', 'users.invite.parent',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
            'staff.view', 'staff.messages', 'staff.attendance.view', 'hr.payroll.view',
        ],
        'head_teacher' => [
            // Operational lead: staff/learners/ops; cannot write grades or fees.
            'school.view', 'staff.manage', 'staff.invite.teacher', 'users.invite.parent',
            'learners.view', 'learners.manage', 'enrollment.manage',
            'finance.view', 'finance.report.view', 'assessment.view', 'reports.view',
            'attendance.view', 'attendance.manage', 'promotions.approve',
            'timetable.manage', 'sms.send', 'announcements.manage', 'hr.view', 'hr.manage',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
            'staff.view', 'staff.messages', 'staff.id.print', 'staff.attendance.view', 'staff.attendance.mark',
            'hr.payroll.view',
        ],
        'deputy_head_teacher' => [
            'school.view', 'staff.manage', 'staff.invite.teacher', 'users.invite.parent',
            'learners.view', 'learners.manage', 'enrollment.manage',
            'finance.view', 'finance.report.view', 'assessment.view', 'reports.view',
            'attendance.view', 'attendance.manage',
            'timetable.manage', 'sms.send', 'announcements.manage',
            'hr.view', 'hr.manage',
            'helpdesk.create', 'helpdesk.view_own', 'helpdesk.manage',
            'staff.view', 'staff.messages', 'staff.id.print', 'staff.attendance.view', 'staff.attendance.mark',
            'hr.payroll.view',
        ],
        'director_of_studies' => [
            // Academic lead: curriculum + assessment; no finance, HR, or identity admin.
            'school.view', 'curriculum.manage', 'learners.view', 'learner.academic.manage', 'enrollment.manage',
            'staff.invite.teacher', 'users.invite.parent',
            'assessment.view', 'assessment.manage', 'assessment.enter', 'marksheet.submit', 'marksheet.verify', 'results.publish', 'reports.view',
            'attendance.view', 'attendance.manage', 'timetable.manage',
            'announcements.manage', 'lms.manage', 'cbt.manage', 'sms.send',
            'helpdesk.create', 'helpdesk.view_own',
            'staff.messages',
        ],
        'bursar' => [
            'finance.view', 'finance.manage', 'finance.report.view', 'finance.reconcile',
            'fees.structure.manage', 'fees.invoice.create', 'fees.invoice.void',
            'fees.payment.record', 'fees.payment.confirm', 'fees.payment.reject', 'fees.payment.reverse',
            'fees.discount.apply', 'fees.record', 'fees.report',
            'sms.send',
            'helpdesk.create', 'helpdesk.view_own',
            'staff.messages', 'hr.payroll.view', 'hr.payroll.manage',
        ],
        'secretary' => [
            // Front office: printable staff IDs and barcode clock. No finance or grade writes.
            'school.view', 'staff.view', 'staff.id.print', 'staff.attendance.view', 'staff.attendance.mark',
            'staff.messages',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'class_teacher' => [
            // Homeroom: view class reports — not unrestricted mark entry or school-wide SMS.
            'attendance.view', 'attendance.mark', 'assessment.view', 'assessment.lock', 'class.view',
            'learners.view', 'learners.profile.update', 'users.invite.parent', 'self.timetable.view',
            'helpdesk.create', 'helpdesk.view_own',
            'staff.messages',
        ],
        'subject_teacher' => [
            // Enter is further scoped to teaching_assignments (class + subject).
            'assessment.enter', 'assessment.view', 'marksheet.submit', 'lms.manage', 'cbt.manage',
            'attendance.view', 'attendance.mark', 'learners.view', 'self.timetable.view',
            'helpdesk.create', 'helpdesk.view_own',
            'staff.messages',
        ],
        'parent' => [
            'child.results.view', 'child.fees.view', 'child.attendance.view', 'fees.pay', 'self.timetable.view',
            'announcements.view',
            'helpdesk.create', 'helpdesk.view_own',
        ],
        'student' => [
            'self.results.view', 'self.fees.view', 'self.attendance.view', 'self.timetable.view', 'lms.view', 'cbt.take',
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
