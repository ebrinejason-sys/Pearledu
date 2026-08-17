# Changelog

## Unreleased

### Platform staff password reset

- Platform admins can send a real password-reset email to PearlEdu staff from `/admin/operators`. The current password is not overwritten until the staff member completes the link.

### School roles

- Added `director_of_studies` (Director of Studies / DOS) for school-wide academics without finance or HR.
- Realigned Head Teacher, Deputy Head Teacher, and Director onto read-only grades and (for Director/Head/Deputy) read-only finance, matching separation of duties.
- Subject teachers may mark attendance and view learners in assigned classes only; class teachers are limited to their homeroom.
- Students can open their own fee statement in the parent/student portal (read-only; payment remains a parent permission).
- Documented the role model in `docs/ROLES.md` and `docs/DECISIONS.md`.
