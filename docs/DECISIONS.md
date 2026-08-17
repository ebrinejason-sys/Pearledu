# Decision log

## 2026-08-17 — School role model vs generic RBAC tables

**Problem:** A role-definition brief proposed `roles` / `permissions` / `role_permissions` / `user_roles` SQL tables and eight school roles including Dean/DOS.

**Decision:** Keep the existing catalog (`roles.key` + `config/permissions.php` + `role_assignments`). Add `director_of_studies`. Realign grants and add class-level scope services. Do not create a parallel permission table.

**Alternatives considered:** (1) New SQL RBAC matching the brief. (2) Rename `subject_teacher` → `teacher`.

**Reason:** The app already unions multi-role assignments, enforces permissions in middleware, and isolates tenants with RLS. A second RBAC schema would drift. `subject_teacher` is referenced in invites, seeds, tests, and teaching assignment filters.

**Consequences:** Agents must treat `config/permissions.php` as the grant matrix. DOS is the academic lead; Head Teacher no longer writes grades or fees.

**Rollback/revisit:** Revisit a SQL permission table only if schools need per-tenant custom grants. Revisit renaming `subject_teacher` with a data migration and invite-token compatibility plan.

## 2026-08-17 — Class teacher does not inherit mark entry

**Problem:** The brief said class teacher inherits all teacher permissions, including grade CRUD.

**Decision:** Class teacher keeps `assessment.view` only. Mark entry requires `assessment.enter` plus a current-year teaching assignment (typically a second `subject_teacher` role).

**Reason:** Academic integrity — homeroom oversight must not silently overwrite subject-teacher marks. This was already the behaviour (`AssessmentScopeTest`).

**Consequences:** A homeroom teacher who also teaches a subject must be invited with both roles.

**Rollback/revisit:** If a school requires homeroom-only mark entry, grant `subject_teacher` + teaching assignment rather than weakening `class_teacher`.

## 2026-08-17 — Head Teacher / Director separation of duties

**Problem:** Head Teacher had `assessment.enter` / `assessment.manage`. Director had `finance.manage` and attendance writes.

**Decision:** Head Teacher and Deputy: finance and assessment **view** only; they retain staff, learners, promotions (head only), timetable, and school-wide attendance. Director: finance/assessment/attendance **view** only; retains staff appointments.

**Reason:** Least privilege and SoD from the role brief (academic integrity + finance not writable by governance roles).

**Consequences:** Day-to-day fee posting is bursar/school admin. Day-to-day grading is subject teachers and DOS.

**Rollback/revisit:** If a small school has no DOS, school admin remains the academic break-glass, not the Head Teacher.
