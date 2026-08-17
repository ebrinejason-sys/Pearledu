# Changelog

## Unreleased

### Role workspaces and EMIS-style school UX

- Role dashboards compose from permission + assignment union (Teacher, Class Teacher, Bursar, DOS, Head Teacher, Director, Parent, Student).
- LMS and CBT writes follow teaching assignments (`LmsScope`, `CbtScope`).
- Class-teacher homeroom `class_id` is required, editable, and preserved when other roles change.
- Assessment marksheets support draft → submit → verify; teachers cannot edit submitted sheets.
- Parents and students can view linked-learner attendance (`child.attendance.view` / `self.attendance.view`).
- DOS can open Staff to invite teachers without `staff.manage` or `learners.manage`.
- Granular bursar fee permissions; void, discount, and reverse are audited.
- School shell: grouped sidebar, breadcrumbs, skip link, `<main>`, mobile drawer, term in the top bar.

### Platform staff password reset

- Platform admins can send a real password-reset email to PearlEdu staff from `/admin/operators`. The current password is not overwritten until the staff member completes the link.

### School roles

- Added `director_of_studies` (Director of Studies / DOS) for school-wide academics without finance or HR.
- Realigned Head Teacher, Deputy Head Teacher, and Director onto read-only grades and (for Director/Head/Deputy) read-only finance, matching separation of duties.
- Subject teachers may mark attendance and view learners in assigned classes only; class teachers are limited to their homeroom.
- Students can open their own fee statement in the parent/student portal (read-only; payment remains a parent permission).
- Documented the role model in `docs/ROLES.md` and `docs/DECISIONS.md`.
