# PearlEdu role workspaces + EMIS-style UX

Date: 2026-08-17

## Problem

School users share one dashboard and a “More” bucket of admin links. LMS/CBT are not teaching-assignment scoped. Class-teacher homeroom class cannot be edited. Parents/students cannot see attendance. DOS can invite teachers in policy but cannot open Staff. Assessment has period statuses but no teacher submit → DOS verify step.

## Non-goals

- New RBAC tables or replacing PermissionResolver
- Separate apps per role
- Pixel-copy of MoES EMIS
- Payroll, fee-defaulter portal lock, director break-glass

## Expected behavior

- Permissions = what; assignments = where. Multi-role = union.
- Teacher: My Teaching, assigned class+subject only (marks, LMS, CBT, attendance).
- Class teacher: My Class homeroom; no automatic mark entry; `role_assignments.class_id` editable and preserved.
- Bursar: finance workspace; granular fee mutation keys; no academics/HR.
- DOS: academics + teacher invites; no finance/HR/identity; no `learners.manage`.
- Head/Deputy: operational oversight; no grade/fee writes.
- Director: executive view; no transactional writes.
- Parent/student: linked learners only, including attendance.

## Tests

Allowed / forbidden / out-of-scope for teacher LMS/CBT, class-teacher class_id sync, parent/student attendance IDOR, bursar vs marks, DOS vs fees, marksheet submit/verify.
