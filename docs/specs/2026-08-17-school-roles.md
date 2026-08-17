# School roles — specification

Date: 2026-08-17

## Problem

PearlEdu already has tenant RBAC (`role_assignments` + `config/permissions.php` + route middleware + RLS). Role meaning, least-privilege boundaries, and data scope were incomplete relative to the school operating model: no Director of Studies, Head Teacher and Director could write grades and/or finance, teachers could mark any class, and students could not open their fee statement.

## Users

School members: student, parent, subject teacher, class teacher, Director of Studies (DOS), bursar, deputy head teacher, head teacher, director, school admin. Platform operator roles are unchanged.

## Expected behavior

- Permissions are the union of a user's active assignments in the current school.
- Route middleware and data-scope services enforce access. Hiding a nav item is not authorization.
- Each role matches `docs/ROLES.md`.
- Teachers mark attendance and view learners only for assigned classes (teaching assignment and/or homeroom).
- Director and Head Teacher may view finance; only bursar / school admin may mutate it.
- Director, Head Teacher, and Deputy Head Teacher may not enter or manage grades. DOS, school admin, and assigned subject teachers may.
- Class teachers view (not enter) marks unless they also hold `subject_teacher` with a teaching assignment.
- Students may view their own fee invoices; they cannot pay (parents pay).

## Non-goals

- New `roles` / `permissions` / `role_permissions` tables (the existing `roles` catalog + config map is the source of truth).
- Platform RBAC changes.
- LMS/CBT row-level scoping for teachers (still school-wide for `lms.manage` / `cbt.manage`).
- Fee-defaulter portal lock, payroll, or break-glass override UI.
- Renaming `subject_teacher` in the database.

## Data

- New school role row: `director_of_studies`.
- No new tenant tables. Permission keys added in config: `curriculum.manage`, `attendance.manage`, `learners.view`, `hr.view`, `self.fees.view` (plus existing keys reused).

## Permissions

See the matrix in `docs/ROLES.md`. Sensitive mutations remain on `*.manage` / `assessment.enter` / `attendance.mark` / `fees.pay`.

## Failure modes

- Missing school context → 403 (existing middleware).
- Forbidden resource or out-of-scope class/student → 403.
- Multi-role users receive the union of permissions and the widest data scope they are entitled to.

## Acceptance criteria

1. `director_of_studies` can be invited, seeded, and assigned.
2. Bursar cannot open assessment mark entry or mutate grades.
3. Director and Head Teacher can GET fees and cannot POST fee mutations.
4. Subject teacher cannot mark attendance for a class they do not teach.
5. Class teacher cannot GET `/assessment/marks`.
6. Student can GET `/portal/fees` for their linked learner and cannot POST pay.
7. DOS can manage assessment periods and cannot GET `/fees`.
8. Automated tests cover allowed, forbidden, and out-of-scope cases.

## Tests

- `tests/Unit/RolePermissionMatrixTest.php`
- `tests/Unit/AttendanceScopeTest.php`
- `tests/Unit/LearnerScopeTest.php`
- `tests/Feature/RoleAuthorizationTest.php`
- Existing invite / assessment scope tests updated for the new role and Head Teacher grade SoD.
