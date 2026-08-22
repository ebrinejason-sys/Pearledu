# School roles (PearlEdu)

Durable context for tenant RBAC. Permissions are enforced on routes and in scope services, not by hiding UI.

**Source of truth**

- Catalog: `roles` table (`key`, `scope`, `label`)
- Grants: `config/permissions.php` (union of a user's active `role_assignments` in the current school)
- Hierarchy of who may invite whom: `App\Services\Authorization\InvitePolicy`
- Data scope: `AssessmentScope`, `AttendanceScope`, `LearnerScope`, `LmsScope`, `CbtScope`, `HelpdeskScope`
- Workspaces: `RoleWorkspaceService` composes My classes, My Class, bursar, DOS, Head Teacher, and Director dashboards from the same permission + assignment union
- Tenant isolation: Eloquent school scope + PostgreSQL `FORCE ROW LEVEL SECURITY`

There is no separate `permissions` / `role_permissions` table. Adding a SQL RBAC schema would duplicate this map.

Platform operator roles (`platform_admin`, `platform_ops`, `emis_data_entrant`, `support_agent`) are documented in `config/permissions.php` and are out of scope here.

## Design principles

1. Least privilege — each role gets only the permissions it needs.
2. Backend enforcement — `permission:…` middleware plus row/class/subject scope. Navigation hiding is not security.
3. Multi-role union — one person may be class teacher and subject teacher; permissions and class scope combine.
4. Separation of duties — finance writes stay with bursar/school admin; grade writes stay with subject teachers, DOS, and school admin.
5. Class teacher does **not** inherit `assessment.enter`. Homeroom is pastoral/oversight. Mark entry requires a teaching assignment (usually via a second `subject_teacher` role).
6. Permission = **what**; assignment = **where**.

```
Jane
├── Teacher (subject_teacher)
│   ├── Biology → S3 East
│   └── Chemistry → S4 East
└── Class Teacher
    └── S3 East
```

Jane has one account. Navigation and dashboards are composed from the union of those assignments. She still cannot enter Chemistry marks for S3 East.

## School roles

| Key | User-facing label | Spec name | Data scope |
|---|---|---|---|
| `student` | Student | Student | Own linked `students.user_id` row |
| `parent` | Parent | Parent / Guardian | Linked children via `guardianships` |
| `subject_teacher` | Teacher | Teacher | Current-year `teaching_assignments` (class **and** subject) |
| `class_teacher` | Class Teacher | Class Teacher | Homeroom `role_assignments.class_id` |
| `director_of_studies` | Director of Studies | DOS / Dean of Studies / Dean of Academics | School-wide academic |
| `bursar` | Bursar | Bursar | School-wide finance + salary records |
| `secretary` | Secretary | Secretary | Front office: printable staff IDs and staff clock |
| `deputy_head_teacher` | Deputy Head Teacher | Operational deputy | School-wide operations, no grade/finance writes, no promotions |
| `head_teacher` | Head Teacher | Headteacher / Head of School | School-wide view + staff/ops; no grade/finance writes |
| `director` | Director | Director | School-wide read; staff appointments; no grade/finance/attendance writes |
| `school_admin` | School Admin | Tenant operator (onboarding contact) | Full school operations |

`school_admin` is created at onboard and is the break-glass tenant operator. It is not one of the eight pedagogical roles; do not merge it with Head Teacher or Director.

User-facing labels may say Teacher, DOS, or Dean of Studies. Internal keys stay stable (`subject_teacher`, `director_of_studies`).

### 1. Student

Focused learner portal. Read own timetable, attendance (`self.attendance.view`), published results, LMS, CBT, announcements, and fee invoices. Cannot pay fees or change academic/financial records. URL `student_id` must match `students.user_id`.

### 2. Parent / Guardian

Portal for linked children only: results, attendance (`child.attendance.view`), timetable, announcements, fee invoices, and `fees.pay` (submissions stay pending until bursar confirmation). Selecting a child switches the whole portal. Cannot see unrelated student IDs. A newly invited guardian gets an **inactive** `parent` assignment until they accept the invite (status stays `invited`, so they cannot sign in).

### 3. Teacher (`subject_teacher`)

**My classes** workspace: today's lessons and which subject they teach to which class. Marks, LMS, and CBT writes require a current teaching assignment for that **class and subject**. Inviting a Teacher, or granting Teacher to existing staff who have no current-year load, requires classified load: subject + one or more classes + `periods_per_week` (default 3). One person may hold many subject→class rows — not a single entry per staff member. Those rows are the same `teaching_assignments` the timetable generator already uses (year, optional term, optional start/end dates). Cannot edit another subject merely because they teach the class. Cannot send school-wide SMS.

### 4. Class Teacher

**My Class** homeroom: roster, daily attendance, parent contacts, invite/link parents for that class (`users.invite.parent`). Sees examination sets (BOT / MOT / EOT and any custom test DOS creates) with the subjects taught in that class. After the marks-upload deadline they may revoke a subject teacher's upload (`assessment.lock`) — they still cannot enter marks. Homeroom bio, photo, and restream to a sibling stream of the same class (`learners.profile.update`) are scoped to their assigned class; they do not get school-wide `learners.manage`. Cannot send school-wide SMS. Staff role edits must store and preserve `role_assignments.class_id`.

### 5. Director of Studies (DOS)

Academic operating system: years, terms, classes, subjects, teaching assignments, timetable, assessment periods, marksheet verify/publish, LMS/CBT oversight, enrollments (`enrollment.manage`), learner academic view. May invite teachers (`staff.invite.teacher`) without `staff.manage`. No `learners.manage` (identity/account ops), finance, HR, or school identity.

### 6. Bursar

Finance workspace: fee types (tuition by day/boarding for a class, transport/van, or a custom type applied to a named group of learners), invoicing, payments, printable/emailable receipts, SchoolPay reconciliation, discounts, reversals, reports. Demanded, cleared, and overdue invoices are separate pages; recording a payment is a popup on the demanded ledger. Granular keys (`fees.invoice.void`, `fees.payment.reverse`, …) sit alongside `finance.manage`. High-risk actions are audited. No grades or learner attendance. Salary amounts and payment history (`hr.payroll.manage`) stay with the bursar — not a full payroll engine.

### 7. Head Teacher

Operational leadership dashboard: students, staff, attendance oversight, promotions, timetable, HR, school reports. **View** finance and assessment. Cannot enter marks or post fee mutations.

### 8. Director

Executive/governance dashboard: EMIS-style census (learners M/F, teaching vs non-teaching staff, enrollment by class and sex, NIN tracking, nationality), collection, attendance, academic mean, class overview (learner profiles + published performance), staff profiles, staff clock history, salary **view** and payment history. No transactional writes (marks, learner attendance, fee mutations, salary changes). `hr.payroll.view` is not `finance.manage`.

### Secretary

Front office: staff directory, printable staff ID cards (photo, name, roles on the front; badge barcode on the back), barcode clock in/out (`staff.attendance.mark`), staff messages. No finance or grade writes. Not invitable by bursars or teachers.

### Deputy Head Teacher

Same operational shape as Head Teacher except promotions stay with the Head Teacher.

## Invitation authority

| Inviter | May invite |
|---|---|
| School Admin | Director, Head, Deputy, DOS, Bursar, Secretary, Class Teacher, Teacher, Parent, Student |
| Director | Head, Deputy, DOS, Bursar, Secretary, Class Teacher, Teacher, Parent, Student |
| Head Teacher | DOS, Secretary, Class Teacher, Teacher, Parent, Student |
| Deputy Head | Class Teacher, Teacher, Parent, Student |
| DOS | Class Teacher, Teacher, Parent, Student |
| Class Teacher | Parent (for learners they can view) |
| Bursar / Secretary / Teacher | — |

Route access matches policy: DOS can open Staff to send teacher invites; class teachers invite parents from the learner profile, not the staff directory. **Editing or revoking existing staff roles requires `staff.manage`.** `staff.invite.teacher` is invite-only — DOS cannot change a Head Teacher’s responsibilities or strip a bursar.

## Assessment workflow

```
DRAFT marksheet
  → teacher enters marks (assessment.enter + assignment)
  → SUBMITTED (marksheet.submit)
  → DOS verifies (marksheet.verify)
  → VERIFIED
  → period published (assessment.manage / results.publish)
  → PUBLISHED (visible on parent/student portal)
```

Teachers cannot silently edit a submitted or verified marksheet. DOS may return it to draft while mark entry is open. Period publication remains the parent-visibility gate.

## Permission matrix (school)

Keys are from `config/permissions.php`. R = view, W = mutate, scoped = assigned classes only.

| Role | Learners | Grades | Attendance | Fees | Staff | Curriculum | Settings |
|---|---|---|---|---|---|---|---|
| Student | own (portal) | own R | own R | own R | — | — | — |
| Parent | children (portal) | children R | children R | children R + pay | — | — | — |
| Teacher | assigned R | assigned CRUD | assigned W | — | messages | — | — |
| Class teacher | homeroom R + bio/photo/restream + parent invite | homeroom R + revoke upload after deadline | homeroom W | — | messages | — | — |
| DOS | view + enrollment | all CRUD + verify | all W | — | invite teachers + messages | all CRUD | — |
| Bursar | financial names on invoices | — | — | all CRUD | messages + payroll W | — | — |
| Secretary | — | — | staff clock W | — | directory R + ID print | — | — |
| Head Teacher | all RW | all R | all W + staff clock | all R | staff RW + payroll R | — | — |
| Deputy Head | all RW | all R | all W + staff clock | all R | staff RW + payroll R | — | — |
| Director | all R | all R | learner R + staff clock R | all R + class defaulters | staff RW + payroll R | — | — |
| School admin | all | all | all | all | all | all | all |

## Implementation map

| Concern | Where |
|---|---|
| Permission union | `PermissionResolver` |
| Route guard | `RequirePermission` (`permission:a,b` is OR) |
| Mark entry / broadsheet | `AssessmentScope` |
| Marksheet submit/verify | `MarksheetWorkflow` |
| Attendance class list + POST | `AttendanceScope` |
| Student index/show/mutate | `LearnerScope` |
| LMS writes | `LmsScope` |
| CBT authoring | `CbtScope` |
| Invite matrix | `InvitePolicy` |
| Homeroom class_id sync | `StaffRoleService` |
| Staff role mutation | `staff.manage` routes + `StaffRoleService` (invite-only roles cannot sync/revoke) |
| Staff ID / clock | `StaffBadgeService`, `StaffClockService` (`staff_badges`, `staff_time_punches`) |
| Staff messages | `StaffMessageService` (`staff_conversations` / `staff_messages`) |
| Salary view/write | `StaffPayrollService` (`hr.payroll.view` / `hr.payroll.manage`) |
| Gender stats / EMIS census | `GenderStatsService::emisOverview` |
| Homeroom profile / restream | `LearnerScope::canEditProfile` / `canRestreamTo` (`learners.profile.update`) |
| Marks upload revoke | `MarksheetWorkflow::revokeUpload` (`assessment.lock`, after deadline) |
| Teaching load on invite | `StaffInvitationService` requires `teaching_assignments` when inviting Teacher |
| Teaching load on role grant | `StaffRoleService` + `TeachingLoadService` require classified load when granting Teacher if none exists for the current year |
| Teaching occupancy matrix | `TeachingLoadService::occupancy` (class × subject; warn when two teachers share a cell) |
| Class defaulters | `DefaulterNoticeService` (print + notify class teacher via staff messages) |
| Idle logout | `EnforceIdleSession` + `users.last_seen_at` (remember-me cannot skip) |
| Role dashboards | `RoleWorkspaceService` |
| Nav / shortcuts | `NavigationBuilder`, `SchoolDashboardService` (not a security boundary) |

## Identity and demographics

Staff and parent accounts must store a NIN (encrypted). Learners may have a NIN; it is optional. Gender is `male` / `female` on users and students. Leadership dashboards and class overview show M/F counts. Class teachers see M/F for their homeroom only.

Platform operators who have **entered** a school workspace may edit that school’s EMIS number and SchoolPay credentials (`platform.schools.update` + recent password). They imitate staff from the entered school’s staff list (`platform.users.impersonate`) — the existing impersonation flow, not a second one. `emis_data_entrant` can enter a workspace but cannot change integrations.

## Deferred

- Bursar portal lock that blocks parent/student access until fees clear
- Director break-glass override with audit
- Full payroll engine (leave, PAYE, statutory filings) — this pass is salary amount + payment history only
- Per-tenant custom permission grants
