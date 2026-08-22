# Role harmony — workspaces, visibility, escalation

Date: 2026-08-22

## Problem

School roles already have correct grants (`config/permissions.php` + scope services), but home screens still look like a shared admin desk. Each focused role should land on a graphical workspace that answers “what do I do in the next 10 minutes?”, with clear boundaries, visibility, and escalation — without a second RBAC system.

## Current vs target

| Role | Current home | Target |
|---|---|---|
| `school_admin` | Union of bursar + academic + generic stats (feels like Head) | Setup/integrity console: completeness ring + hygiene tiles. Break-glass grants unchanged. Not the daily mark-entry or fee-posting desk. |
| `director` | EMIS census + school KPIs; `operationsLead` already suppressed | Governance pulse (read-only KPIs) + exception cards linking to Head / DOS / Bursar **view** routes. No POST chrome for fees/marks/attendance. |
| `head_teacher` | Generic school overview + action items | Approvals board: promotions pending, helpdesk escalations, attendance gaps. Primary CTA: commit promotions. No grade/fee writes. |
| `deputy_head_teacher` | Same shape as Head minus promotions | Logistics today-board: staff clock, uncovered timetable slots, class absence heatmap, escalated tickets. No promotions. |
| `director_of_studies` | Draft/submitted/verified percents | Academic OS: period funnel, occupancy heatmap preview, grade-band view (no names), late-draft teachers. Invite-only on Staff. |
| `class_teacher` | Compact KPI strip | My Class: attendance ring, Take register CTA, roster faces, exam-set chips + lock, fees-cleared bar (no amounts). Parent funnel. No `assessment.enter`. |
| `subject_teacher` | Lesson list + generic buttons | Today timeline + class+subject cards (Attendance · Enter marks). Flag concern to homeroom via staff messages. Scoped by `teaching_assignments`. |
| `parent` | Text links | Photo-first child cards + Results / Attendance / Fees / Timetable. Message **class teacher** via helpdesk (assigned server-side). Own child only. |
| `student` | Same portal | Own timetable, CBT/LMS, results, fee statement (read). No `fees.pay`. No classmates. |

Bursar and secretary are unchanged except where Action Center copy must not leak SoD.

## Files to touch

- `app/Services/Dashboard/RoleWorkspaceService.php` — compose primary + secondary workspaces from role-key order
- `app/Services/Dashboard/ActionCenterService.php` — hygiene, director exceptions, head/deputy queues
- `app/Services/Schools/SchoolSetupService.php` — leadership + current-term hygiene checks
- `app/Services/Navigation/NavigationBuilder.php` — My Class / My classes under Home
- `app/Services/Authorization/HelpdeskScope.php` — assignees may view assigned tickets (not school-wide manage)
- `app/Http/Controllers/HelpdeskController.php` — parent tickets assigned to homeroom teacher
- `app/Services/Staff/StaffMessageService.php` — flag concern to class teacher
- `app/Services/Portal/PortalService.php` — class teacher + last attendance for portal
- `resources/views/app/home.blade.php` + `resources/views/app/partials/workspace/*`
- `resources/views/app/teaching/my-class.blade.php`, `my-teaching.blade.php`
- `resources/views/app/portal/home.blade.php`, timetable
- `resources/views/app/attendance/index.blade.php`, `resources/views/app/assessment/marks.blade.php`
- `resources/views/layouts/app.blade.php` — theme-token CSS only
- `docs/ROLES.md`, `docs/DECISIONS.md`
- Tests: Role / Workspace / Helpdesk / Invite (extend, don’t delete)

## Permission changes

**None expected.** Grants already match SoD:

- Director / head / deputy: finance and assessment **view** only
- Class teacher: `assessment.view` + `assessment.lock`, not `assessment.enter`
- Student: `self.fees.view`, not `fees.pay`
- DOS: `staff.invite.teacher`, not `staff.manage`

Helpdesk **scope** grows so a class teacher can see tickets **assigned to them** (parent funnel). That is not `helpdesk.manage`.

## Escalation (no new product)

```
Teacher  --staff message-->  Class teacher
Parent   --helpdesk, assigned-->  Class teacher
Class teacher  --helpdesk / staff message-->  Deputy or DOS
Deputy / DOS  --helpdesk.manage / staff message-->  Head
Head  --KPI / exception cards only-->  Director
```

No `concerns` table. No quiet-hours blocker. No director mutate / period-unlock.

## Non-goals

- New SQL RBAC tables (`permissions`, `role_permissions`, `user_roles`)
- Renaming `subject_teacher` / `director_of_studies` keys
- Finance or grade write grants for director, head, deputy, class teacher, parent, student
- Head break-glass, impersonation, or exam-entry unlock
- Director fee-revision approval
- Parent view of classmates, ranks, or class averages
- Learner electives / co-curricular signup
- Deputy venue-booking module
- Chart.js / Apex / D3; landing restyle; fourth theme
- Cover as a new calendar product (empty `TimetableSlot.teacher_id` is enough)

## Tests

Allowed + forbidden + out-of-scope remain in Role / AssessmentScope / AttendanceScope / LearnerScope / Invite tests. New coverage: primary workspace per role, director exception items are GET view routes, parent helpdesk assignment, class teacher sees assigned ticket and not another teacher’s, teacher flag-concern stays on staff messages, student still cannot pay.
