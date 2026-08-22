# School roles (PearlEdu)

Durable context for tenant RBAC. Permissions are enforced on routes and in scope services, not by hiding UI.

**Source of truth**

- Catalog: `roles` table (`key`, `scope`, `label`)
- Grants: `config/permissions.php` (union of a user's active `role_assignments` in the current school)
- Hierarchy of who may invite whom: `App\Services\Authorization\InvitePolicy`
- Data scope: `AssessmentScope`, `AttendanceScope`, `LearnerScope`, `LmsScope`, `CbtScope`, `HelpdeskScope`
- Workspaces: `RoleWorkspaceService` composes **My Class**, **My classes**, bursar, DOS academic OS, Head approvals, Deputy logistics, Director governance, and School Admin hygiene from the same permission + assignment union. Primary workspace follows role-key order (homeroom → teacher → DOS → bursar → deputy → head → director → school admin). Multi-role users see the first match as the main desk and the rest as compact strips.
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
| `secretary` | Secretary | Secretary | Front office: learner lookup, staff files, printable IDs, clock |
| `deputy_head_teacher` | Deputy Head Teacher | Operational deputy | School-wide operations, no grade/finance writes, no promotions |
| `head_teacher` | Head Teacher | Headteacher / Head of School | School-wide view + staff/ops; no grade/finance writes |
| `director` | Director | Director | School-wide read; staff appointments; no grade/finance/attendance writes |
| `school_admin` | School Admin | Tenant operator (onboarding contact) | Full school operations |

`school_admin` is created at onboard and is the break-glass tenant operator. It is not one of the eight pedagogical roles; do not merge it with Head Teacher or Director.

User-facing labels may say Teacher, DOS, or Dean of Studies. Internal keys stay stable (`subject_teacher`, `director_of_studies`).

### 1. Student

Focused learner portal. Read own timetable, attendance (`self.attendance.view`), published results, LMS, CBT, announcements, and fee invoices. Cannot pay fees or change academic/financial records. URL `student_id` must match `students.user_id`. Home is photo-first; never other learners’ names, marks, or invoices.

### 2. Parent / Guardian

Portal for linked children only: results, attendance (`child.attendance.view`), timetable, announcements, fee invoices, and `fees.pay` (submissions stay pending until bursar confirmation). Selecting a child switches the whole portal. Home is photo-first with Results / Attendance / Fees / Timetable tiles. Message the **class teacher**, not a staff directory. Cannot see unrelated student IDs, classmates’ marks, or ranks. A newly invited guardian gets an **inactive** `parent` assignment until they accept the invite (status stays `invited`, so they cannot sign in).

### 3. Teacher (`subject_teacher`)

**My classes** workspace is the default home: today’s lesson timeline and class+subject cards (Attendance · Enter marks). Marks, LMS, and CBT writes require a current teaching assignment for that **class and subject**. After submit, teachers cannot silently edit (`MarksheetWorkflow`). Flag a pastoral/academic concern to the class teacher via `StaffMessageService` (not a second social graph). Inviting a Teacher, or granting Teacher to existing staff who have no current-year load, requires classified load: subject + one or more classes + `periods_per_week` (default 3). Cannot edit another subject merely because they teach the class. Cannot send school-wide SMS.

### 4. Class Teacher

**My Class** workspace is the default home: roster faces, today’s attendance ring, Take register (offline-queue), parent list, exam-set status with lock after deadline, and a **fees cleared count** (no amounts). Composite report cards **read published marks** for the homeroom — they do not write `marks`. Parents message the class teacher (helpdesk assigned server-side). Escalate to deputy / DOS / head via staff messages. Staff role edits must store and preserve `role_assignments.class_id`.

### 5. Director of Studies (DOS)

Academic operating system (home): current period funnel (draft → submitted → verified → published), occupancy heatmap preview (`TeachingLoadService::occupancy`), grade-band view of **published** marks (no pupil names), teachers with late drafts. Years, terms, classes, subjects, teaching assignments, timetable, assessment periods, marksheet verify/publish, LMS/CBT oversight, enrollments (`enrollment.manage`), learner academic view. May invite teachers (`staff.invite.teacher`) without `staff.manage`. No `learners.manage` (identity/account ops), finance, HR, or school identity.

### 6. Bursar

Finance workspace: fee types (a class amount for day and a separate amount for boarding; named extras such as van saved for a specific learner and applied on their profile), delete or archive of saved types, invoicing, payments, printable/emailable receipts, SchoolPay reconciliation, discounts, reversals, reports. Demanded, cleared, and overdue invoices are separate pages; recording a payment is a popup on the demanded ledger. **Reverse** (confirmed) and **reject** (pending parent submissions) are bursar actions: a reason of at least 8 characters is required, stored on `fee_payments.decision_reason`, and written to the audit trail (`fees.payment.reversed` / `fees.payment.rejected`). Director, head, deputy, DOS, and teachers cannot POST these routes. School admin remains break-glass via `finance.manage`. Opens learner profiles (`learners.view`, school-wide) to attach a saved fee type or a one-off extra. Granular keys (`fees.invoice.void`, `fees.payment.reverse`, …) sit alongside `finance.manage`. High-risk actions are audited. No grades, learner attendance, or learner identity writes. Salary amounts and payment history (`hr.payroll.manage`) stay with the bursar — not a full payroll engine.

### 7. Head Teacher

Approvals home: school KPIs plus promotion batches pending commit, helpdesk escalations, attendance gaps. **View** finance and assessment. Cannot enter marks or post fee mutations. `promotions.approve` stays head-only — deputy does not commit.

### 8. Director

Governance home: EMIS-style census (learners M/F, teaching vs non-teaching staff, enrollment by class and sex), collection rate, attendance %, published academic mean, staff clock **summary**. Exception alerts (read-only) link to Head / DOS / Bursar **view** routes — never to a director mutate form. No transactional writes (marks, learner attendance, fee mutations, salary changes). `hr.payroll.view` is not `finance.manage`. Does not receive the Head/Deputy operations workspace.

### 9. School Admin

Created at onboard. Break-glass tenant operator (`finance.manage`, `assessment.manage`). Home is a **setup/integrity console** (completeness ring, invited-not-activated staff, classes without a class teacher, teachers with no load, duplicate-looking learners, missing photos) — not the daily mark-entry or fee-posting desk. Do not merge with Head Teacher or Director.

### Secretary

Front office: school-wide learner directory lookup (`learners.view`, no create/archive), staff files (photo, biodata, academic documents via `staff.profile.update`), printable staff ID cards (photo, name, roles on the front; badge barcode on the back), barcode clock in/out (`staff.attendance.mark`), staff messages. No finance, payroll, grade, or staff-role writes. Not invitable by bursars or teachers.

**Login after invite:** creating the role in `config/permissions.php` is not enough. Sync the catalog, then the person must set a password.

```bash
php artisan db:seed --class=RoleSeeder
php artisan invite:activate secretary@school.test --password='Choose-a-long-password'
```

`RoleSeeder` is idempotent (adds `secretary` if the `roles` row is missing). Invited users stay `status=invited` with inactive `role_assignments` until they open the email link **or** you run `invite:activate`. Login rejects invited/disabled accounts with a generic credentials error. On a live server add `--force`. Walkthrough accounts such as `secretary@stkizito.test` are already active after `php artisan school:seed-walkthrough --password='…'`.

### Deputy Head Teacher

Same operational shape as Head Teacher except promotions stay with the Head Teacher. Home is **daily logistics**: staff clock, uncovered timetable slots (empty `teacher_id`), class absence heatmap, helpdesk queue. No fee writes, no mark entry, no promotion commit.

## Escalation

Structured, using helpdesk + staff messages (no parallel product):

1. Teacher flags a concern → class teacher (`StaffMessageService::flagConcern`)
2. Parent messages **class teacher** (helpdesk ticket; `assigned_to` set server-side from the child’s homeroom)
3. Class teacher escalates → deputy / DOS / head (staff messages)
4. Deputy / head see school-wide helpdesk (`helpdesk.manage`)
5. Director sees exception **alerts** on the governance pulse, not a task dump

Class teachers may view tickets assigned to them. That is not `helpdesk.manage`.

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
| Bursar | profile R (billing) | — | — | all CRUD | messages + payroll W | — | — |
| Secretary | all R | — | staff clock W | — | directory R + files W + ID print | — | — |
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
| Staff files | `staff.profile.update` + `staff_documents` (FORCE RLS); secretary may update any staff file; other actors only people they may invite (hierarchy) |
| Staff messages | `StaffMessageService` (`staff_conversations` / `staff_messages`) |
| Salary view/write | `StaffPayrollService` (`hr.payroll.view` / `hr.payroll.manage`) — amount on invite when the actor has payroll manage |
| Learner fees | Class day/boarding structures plus other class-wide types auto-invoice on admit/enroll from class + residence; bursar attaches any saved type (or a one-off extra) on the learner profile; bursar may delete a saved type; bursar reverses/rejects **payments** with a stored reason + audit log |
| Gender stats / EMIS census | `GenderStatsService::emisOverview` |
| Homeroom profile / restream | `LearnerScope::canEditProfile` / `canRestreamTo` (`learners.profile.update`) |
| Marks upload revoke | `MarksheetWorkflow::revokeUpload` (`assessment.lock`, after deadline) |
| Teaching load on invite | `StaffInvitationService` requires `teaching_assignments` when inviting Teacher |
| Teaching load on role grant | `StaffRoleService` + `TeachingLoadService` require classified load when granting Teacher if none exists for the current year |
| Teaching occupancy matrix | `TeachingLoadService::occupancy` (class × subject; warn when two teachers share a cell) |
| Class defaulters | `DefaulterNoticeService` (print + notify class teacher via staff messages) |
| Idle logout | `EnforceIdleSession` + `users.last_seen_at` (remember-me cannot skip) |
| Role dashboards | `RoleWorkspaceService` (primary workspace by role key; stacked strips for multi-role) |
| Action Center | `ActionCenterService` (hygiene for school admin; exceptions for director; scoped attendance gaps) |
| Parent → class teacher | Helpdesk `assigned_to` set server-side; class teacher may view assigned tickets |
| Teacher → class teacher | `StaffMessageService::flagConcern` |
| School admin hygiene | `SchoolSetupService::hygiene` |
| Nav / shortcuts | Nested EMIS-style IA in `NavigationBuilder` (Manage school data → Learners / Human Resource / Finance). `SchoolDashboardService` shortcuts. Not a security boundary. |

## Identity and demographics

Staff and parent accounts must store a NIN (encrypted). Learners may have a NIN; it is optional. Every user and learner profile shows a photo (or a placeholder). Edit of **staff details** follows the invite hierarchy: you may change people whose roles you are allowed to invite. Secretary still keeps school-wide staff files (photo, biodata, documents) without role writes. Learner profile edits stay on `LearnerScope` (school-wide `learners.manage`, or homeroom `learners.profile.update`). Own photo and details stay on Account.

Platform operators who have **entered** a school workspace may edit that school’s EMIS number and SchoolPay credentials (`platform.schools.update` + recent password). They imitate staff from the entered school’s staff list (`platform.users.impersonate`) — the existing impersonation flow, not a second one. `emis_data_entrant` can enter a workspace but cannot change integrations.

## Deferred

- Bursar portal lock that blocks parent/student access until fees clear
- Director break-glass override with audit
- Full payroll engine (leave, PAYE, statutory filings) — this pass is salary amount + payment history only
- Per-tenant custom permission grants
