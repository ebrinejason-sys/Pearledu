# School roles (PearlEdu)

Durable context for tenant RBAC. Permissions are enforced on routes and in scope services, not by hiding UI.

**Source of truth**

- Catalog: `roles` table (`key`, `scope`, `label`)
- Grants: `config/permissions.php` (union of a user's active `role_assignments` in the current school)
- Hierarchy of who may invite whom: `App\Services\Authorization\InvitePolicy`
- Data scope: `AssessmentScope`, `AttendanceScope`, `LearnerScope`, `LmsScope`, `CbtScope`, `HelpdeskScope`
- Workspaces: `RoleWorkspaceService` composes My Teaching, My Class, bursar, DOS, Head Teacher, and Director dashboards from the same permission + assignment union
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
| `bursar` | Bursar | Bursar | School-wide finance |
| `deputy_head_teacher` | Deputy Head Teacher | Operational deputy | School-wide operations, no grade/finance writes, no promotions |
| `head_teacher` | Head Teacher | Headteacher / Head of School | School-wide view + staff/ops; no grade/finance writes |
| `director` | Director | Director | School-wide read; staff appointments; no grade/finance/attendance writes |
| `school_admin` | School Admin | Tenant operator (onboarding contact) | Full school operations |

`school_admin` is created at onboard and is the break-glass tenant operator. It is not one of the eight pedagogical roles; do not merge it with Head Teacher or Director.

User-facing labels may say Teacher, DOS, or Dean of Studies. Internal keys stay stable (`subject_teacher`, `director_of_studies`).

### 1. Student

Focused learner portal. Read own timetable, attendance (`self.attendance.view`), published results, LMS, CBT, announcements, and fee invoices. Cannot pay fees or change academic/financial records. URL `student_id` must match `students.user_id`.

### 2. Parent / Guardian

Portal for linked children only: results, attendance (`child.attendance.view`), timetable, announcements, fee invoices, and `fees.pay` (submissions stay pending until bursar confirmation). Selecting a child switches the whole portal. Cannot see unrelated student IDs.

### 3. Teacher (`subject_teacher`)

**My Teaching** workspace: today's lessons, assigned classes/subjects, attendance, marks, LMS, CBT authoring. Marks, LMS, and CBT writes require a current teaching assignment for that **class and subject**. Cannot edit another subject merely because they teach the class.

### 4. Class Teacher

**My Class** homeroom: roster, daily attendance, parent contacts, invite/link parents for that class (`users.invite.parent`). Cannot enter marks unless also assigned as a teacher. Staff role edits must store and preserve `role_assignments.class_id`.

### 5. Director of Studies (DOS)

Academic operating system: years, terms, classes, subjects, teaching assignments, timetable, assessment periods, marksheet verify/publish, LMS/CBT oversight, enrollments (`enrollment.manage`), learner academic view. May invite teachers (`staff.invite.teacher`) without `staff.manage`. No `learners.manage` (identity/account ops), finance, HR, or school identity.

### 6. Bursar

Finance workspace: structures, invoicing, payments, SchoolPay reconciliation, discounts, reversals, reports. Granular keys (`fees.invoice.void`, `fees.payment.reverse`, …) sit alongside `finance.manage`. High-risk actions are audited. No grades, attendance, or HR.

### 7. Head Teacher

Operational leadership dashboard: students, staff, attendance oversight, promotions, timetable, HR, school reports. **View** finance and assessment. Cannot enter marks or post fee mutations.

### 8. Director

Executive/governance dashboard: enrollment, collection, attendance, academic mean, alerts. No transactional writes (marks, attendance, payments).

### Deputy Head Teacher

Same operational shape as Head Teacher except promotions stay with the Head Teacher.

## Invitation authority

| Inviter | May invite |
|---|---|
| School Admin | Director, Head, Deputy, DOS, Bursar, Class Teacher, Teacher, Parent, Student |
| Director | Head, Deputy, DOS, Bursar, Class Teacher, Teacher, Parent, Student |
| Head Teacher | DOS, Class Teacher, Teacher, Parent, Student |
| Deputy Head | Class Teacher, Teacher, Parent, Student |
| DOS | Class Teacher, Teacher, Parent, Student |
| Class Teacher | Parent (for learners they can view) |
| Bursar / Teacher | — |

Route access matches policy: DOS can open Staff to send teacher invites; class teachers invite parents from the learner profile, not the staff directory.

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
| Teacher | assigned R | assigned CRUD | assigned W | — | — | — | — |
| Class teacher | homeroom R + parent invite | homeroom R | homeroom W | — | — | — | — |
| DOS | view + enrollment | all CRUD + verify | all W | — | invite teachers | all CRUD | — |
| Bursar | financial names on invoices | — | — | all CRUD | — | — | — |
| Head Teacher | all RW | all R | all W | all R | staff RW | — | — |
| Deputy Head | all RW | all R | all W | all R | staff RW | — | — |
| Director | all R | all R | all R | all R | staff RW | — | — |
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
| Role dashboards | `RoleWorkspaceService` |
| Nav / shortcuts | `NavigationBuilder`, `SchoolDashboardService` (not a security boundary) |

## Deferred

- Bursar portal lock for fee defaulters
- Director break-glass override with audit
- Payroll (bursar spec) — HR leave is the current HR module
- Per-tenant custom permission grants
