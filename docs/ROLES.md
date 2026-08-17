# School roles (PearlEdu)

Durable context for tenant RBAC. Permissions are enforced on routes and in scope services, not by hiding UI.

**Source of truth**

- Catalog: `roles` table (`key`, `scope`, `label`)
- Grants: `config/permissions.php` (union of a user's active `role_assignments` in the current school)
- Hierarchy of who may invite whom: `App\Services\Authorization\InvitePolicy`
- Data scope: `AssessmentScope`, `AttendanceScope`, `LearnerScope`, `HelpdeskScope`
- Tenant isolation: Eloquent school scope + PostgreSQL `FORCE ROW LEVEL SECURITY`

There is no separate `permissions` / `role_permissions` table. Adding a SQL RBAC schema would duplicate this map.

Platform operator roles (`platform_admin`, `platform_ops`, `emis_data_entrant`, `support_agent`) are documented in `config/permissions.php` and are out of scope here.

## Design principles

1. Least privilege — each role gets only the permissions it needs.
2. Backend enforcement — `permission:…` middleware plus row/class scope. Navigation hiding is not security.
3. Multi-role union — one person may be class teacher and subject teacher; permissions and class scope combine.
4. Separation of duties — finance writes stay with bursar/school admin; grade writes stay with subject teachers, DOS, and school admin.
5. Class teacher does **not** inherit `assessment.enter`. Homeroom is pastoral/oversight. Mark entry requires a teaching assignment (usually via a second `subject_teacher` role).

## School roles

| Key | Label | Spec name | Data scope |
|---|---|---|---|
| `student` | Student | Student | Own linked `students.user_id` row |
| `parent` | Parent | Parent / Guardian | Linked children via `guardianships` |
| `subject_teacher` | Subject Teacher | Teacher | Current-year `teaching_assignments` |
| `class_teacher` | Class Teacher | Class Teacher | Homeroom `role_assignments.class_id` |
| `director_of_studies` | Director of Studies | Dean / DOS | School-wide academic |
| `bursar` | Bursar | Bursar | School-wide finance |
| `deputy_head_teacher` | Deputy Head Teacher | (operational deputy; not in the eight-role brief) | School-wide operations, no grade/finance writes |
| `head_teacher` | Head Teacher | Headteacher / Head of School | School-wide view + staff/ops; no grade/finance writes |
| `director` | Director | Director | School-wide read; staff appointments; no grade/finance/attendance writes |
| `school_admin` | School Admin | Tenant operator (onboarding contact) | Full school operations |

`school_admin` is created at onboard and is the break-glass tenant operator. It is not one of the eight pedagogical roles; do not remove it.

### 1. Student

Portal-only. Read own results, timetable, announcements, LMS, CBT, and fee invoices. Cannot pay fees or change academic/financial records.

### 2. Parent / Guardian

Portal for linked children: results, timetable, announcements, fee invoices, and `fees.pay` (submissions stay pending until bursar confirmation). Cannot see other students.

### 3. Subject Teacher (Teacher)

Assigned class+subject only: enter marks, LMS materials, mark attendance, view learner profiles in those classes. Cannot see other teachers' classes or finance.

### 4. Class Teacher

Homeroom: attendance, holistic view of the class (learners + published/viewable reports), parent SMS. Cannot enter marks unless also assigned as a subject teacher.

### 5. Director of Studies (DOS)

School-wide academics: curriculum (classes, years, subjects, teaching assignments), assessment periods and entry, attendance, timetable, LMS/CBT, learner records. No finance, HR, or school identity settings.

### 6. Bursar

Fee structures, invoices, payments, reconciliation, fee reports, SMS. No grades, attendance, or HR.

### 7. Head Teacher

Day-to-day leadership: staff accounts, learners, promotions, timetable, announcements, HR leave, school-wide attendance, **read** finance and **read** assessment reports. Cannot enter marks or post fee mutations.

### 8. Director

Governance visibility: read learners, finance, assessment, attendance, HR; appoint staff; announcements. Cannot mark attendance, enter grades, or post fee mutations.

### Deputy Head Teacher

Kept for Ugandan school practice. Same operational shape as Head Teacher except promotions stay with the Head Teacher.

## Permission matrix (school)

Keys are from `config/permissions.php`. R = view, W = mutate, scoped = assigned classes only.

| Role | Learners | Grades | Attendance | Fees | Staff | Curriculum | Settings |
|---|---|---|---|---|---|---|---|
| Student | own (portal) | own R | — | own R | — | — | — |
| Parent | children (portal) | children R | — | children R + pay | — | — | — |
| Subject teacher | assigned R | assigned CRUD | assigned W | — | — | — | — |
| Class teacher | homeroom R | homeroom R | homeroom W | — | — | — | — |
| DOS | all RW | all CRUD | all W | — | — | all CRUD | — |
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
| Attendance class list + POST | `AttendanceScope` |
| Student index/show/mutate | `LearnerScope` |
| Invite matrix | `InvitePolicy` |
| Nav / shortcuts | `NavigationBuilder`, `SchoolDashboardService` (not a security boundary) |

## Deferred

- Teacher-scoped LMS/CBT writes
- Bursar portal lock for fee defaulters
- Director break-glass override with audit
- Payroll (bursar spec) — HR leave is the current HR module
