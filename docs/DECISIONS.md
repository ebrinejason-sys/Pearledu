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

## 2026-08-17 — Platform admin sends password-reset links, not temporary passwords

**Problem:** The PearlEdu staff "Reset password" action set an unknown random password and emailed `/forgot-password` (no token). Staff were locked out and still had to request a reset themselves.

**Decision:** Platform admins send `ResetPasswordMail` with a broker token. The existing password stays valid until the link is used. Support agents and other operator roles cannot send staff resets (`platform.staff.manage` + `canManage`).

**Alternatives considered:** (1) Email a temporary password. (2) Invalidate the current password immediately.

**Reason:** Least surprise and recoverability if mail delivery fails. Matches the public forgot-password flow.

**Consequences:** Compromised accounts can still sign in with the old password until the staff member completes the reset (admins can Force logout separately).

**Rollback/revisit:** If recovery must lock the account immediately, invalidate sessions and rotate the password in the same action.

## 2026-08-17 — Role workspaces, scoped LMS/CBT, marksheet workflow

**Problem:** School users shared one generic dashboard. LMS/CBT were not assignment-scoped. Class-teacher homeroom could not be edited safely. Parents/students had no attendance. DOS could invite teachers in policy but could not open Staff. Assessment had period statuses but no teacher submit → DOS verify step.

**Decision:** Keep `PermissionResolver` + `config/permissions.php`. Add `LmsScope`, `CbtScope`, `MarksheetWorkflow`, `RoleWorkspaceService`. Split invite route permissions (`staff.invite.teacher`, `users.invite.parent`, `enrollment.manage`). User-facing Teacher label; internal key remains `subject_teacher`.

**Reason:** Least privilege and the existing multi-role architecture. Navigation regrouping is permission-driven, not a second RBAC system.

**Consequences:** Class teachers invite parents from the learner profile. DOS invites teachers from Staff but cannot revoke access or manage school identity. Submitted marksheets are locked for teachers.

**Rollback/revisit:** Fee granular keys still OR with `finance.manage` so bursar/school admin are not locked out. A later pass can drop the broad key once every fee route is granular-only.

## 2026-08-22 — Idle sessions and invite-only staff mutations

**Problem:** `SESSION_LIFETIME` only expired the cookie. Remember-me could silently sign someone back in on a shared staff computer. DOS (`staff.invite.teacher`) could POST `/staff/{user}/roles` and strip a Head Teacher or bursar. Class teachers had school-wide `sms.send`. Anyone with `assessment.view` could open the assessment-period admin screen.

**Decision:** Persist `users.last_seen_at`. `EnforceIdleSession` signs out after 30 idle minutes and rotates remember tokens. Heartbeat + in-app warning extend the window only after real activity. `updateRoles` / `revoke` require `staff.manage`. `StaffRoleService` refuses to remove a responsibility the actor cannot invite. Teachers lose school-wide SMS. Assessment period admin is `assessment.manage` only.

**Reason:** Least privilege and workstation lock for a school MIS. Invitation authority stays; mutation of existing staff is an HR/ops action.

**Consequences:** DOS still invites teachers from Staff. Head Teacher still cannot demote the bursar via the role checkboxes. Idle logout is user-level (activity on any device refreshes `last_seen_at`).

**Rollback/revisit:** If a school needs class-level SMS, add a scoped sender rather than restoring school-wide `sms.send` on teachers.
